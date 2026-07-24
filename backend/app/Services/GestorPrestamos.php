<?php

namespace App\Services;

use App\Enums\EstadoEquipo;
use App\Enums\EstadoPrestamo;
use App\Models\Prestamo;
use App\Notifiers\NotificadorAtraso;
use App\Repositories\EquipoRepository;
use App\Repositories\PrestamoRepository;
use App\Repositories\UsuarioRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class GestorPrestamos
{
    /** @var NotificadorAtraso[] */
    private array $observadores = [];

    public function __construct(
        private UsuarioRepository $usuarios,
        private EquipoRepository $equipos,
        private PrestamoRepository $prestamos,
    ) {
    }

    public function agregarObservador(NotificadorAtraso $notificador): void
    {
        $this->observadores[] = $notificador;
    }

    public function registrarPrestamo(int $usuarioId, int $equipoId, int $dias): Prestamo
    {
        $usuario = $this->usuarios->buscar($usuarioId);
        $equipo = $this->equipos->buscar($equipoId);

        if (! $usuario || ! $equipo) {
            throw new OperacionInvalidaException('Usuario o equipo no encontrado.');
        }

        if (! $usuario->puedeSolicitarPrestamo()) {
            throw new OperacionInvalidaException('El usuario no esta habilitado para solicitar prestamos.');
        }

        if (! $equipo->estaDisponible()) {
            throw new OperacionInvalidaException('El equipo no esta disponible.');
        }

        $fechaPrestamo = Carbon::today();

        $prestamo = new Prestamo([
            'usuario_id' => $usuario->id,
            'equipo_id' => $equipo->id,
            'fecha_prestamo' => $fechaPrestamo,
            'fecha_dev_esperada' => $fechaPrestamo->copy()->addDays($dias),
            'estado' => EstadoPrestamo::ACTIVO,
        ]);
        $this->prestamos->guardar($prestamo);

        $equipo->cambiarEstado(EstadoEquipo::PRESTADO);

        return $prestamo;
    }

    public function registrarDevolucion(int $prestamoId, bool $danado = false): Prestamo
    {
        $prestamo = $this->prestamos->buscar($prestamoId);

        if (! $prestamo || $prestamo->estado !== EstadoPrestamo::ACTIVO) {
            throw new OperacionInvalidaException('El prestamo no existe o ya fue cerrado.');
        }

        $prestamo->registrarDevolucion();

        $prestamo->equipo->cambiarEstado($danado ? EstadoEquipo::DANADO : EstadoEquipo::DISPONIBLE);

        if ($prestamo->estaAtrasado()) {
            $this->notificarAtraso($prestamo);
        }

        return $prestamo;
    }

    public function listarPrestamosActivos(): Collection
    {
        return $this->prestamos->listarActivos();
    }

    public function verificarAtrasos(): void
    {
        foreach ($this->prestamos->listarActivos() as $prestamo) {
            if ($prestamo->estaAtrasado()) {
                $this->notificarAtraso($prestamo);
            }
        }
    }

    private function notificarAtraso(Prestamo $prestamo): void
    {
        foreach ($this->observadores as $observador) {
            $observador->notificar($prestamo);
        }
    }
}
