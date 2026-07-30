<?php

namespace App\Services;

use App\Enums\EstadoEquipo;
use App\Enums\EstadoPrestamo;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Usuario;
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

        $this->rechazarSi(! $usuario || ! $equipo, 'Usuario o equipo no encontrado.');
        $this->rechazarSi(! $usuario->puedeSolicitarPrestamo(), 'El usuario no esta habilitado para solicitar prestamos.');
        $this->rechazarSi(! $equipo->estaDisponible(), 'El equipo no esta disponible.');

        return $this->crearPrestamo($usuario, $equipo, $dias);
    }

    private function crearPrestamo(Usuario $usuario, Equipo $equipo, int $dias): Prestamo
    {
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

    public function registrarDevolucionOk(int $prestamoId): Prestamo
    {
        return $this->procesarDevolucion($prestamoId, danado: false);
    }

    public function registrarDevolucionConDano(int $prestamoId): Prestamo
    {
        return $this->procesarDevolucion($prestamoId, danado: true);
    }

    private function procesarDevolucion(int $prestamoId, bool $danado): Prestamo
    {
        $prestamo = $this->prestamos->buscar($prestamoId);

        $this->rechazarSi(! $prestamo || $prestamo->estado !== EstadoPrestamo::ACTIVO, 'El prestamo no existe o ya fue cerrado.');

        $prestamo->registrarDevolucion();

        $prestamo->equipo->registrarDevolucion($danado);

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

    private function rechazarSi(bool $condicion, string $mensaje): void
    {
        if ($condicion) {
            throw new OperacionInvalidaException($mensaje);
        }
    }
}
