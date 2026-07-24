<?php

namespace Tests\Feature;

use App\Enums\EstadoEquipo;
use App\Enums\EstadoPrestamo;
use App\Enums\RolUsuario;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Usuario;
use App\Notifiers\ConsolaNotificador;
use App\Notifiers\NotificadorAtraso;
use App\Repositories\EquipoRepository;
use App\Repositories\PrestamoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\GestorPrestamos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class ObserverNotificacionAtrasoTest extends TestCase
{
    use RefreshDatabase;

    private GestorPrestamos $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor = new GestorPrestamos(
            new UsuarioRepository(),
            new EquipoRepository(),
            new PrestamoRepository(),
        );
    }

    private function crearPrestamoActivo(Carbon $fechaDevEsperada): Prestamo
    {
        $usuario = Usuario::create(['nombre' => 'Ana', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => true]);
        $equipo = Equipo::create(['nombre' => 'Equipo', 'categoria' => 'Categoria', 'estado' => EstadoEquipo::PRESTADO]);

        return Prestamo::create([
            'usuario_id' => $usuario->id,
            'equipo_id' => $equipo->id,
            'fecha_prestamo' => $fechaDevEsperada->copy()->subDays(7),
            'fecha_dev_esperada' => $fechaDevEsperada,
            'estado' => EstadoPrestamo::ACTIVO,
        ]);
    }

    public function test_verificar_atrasos_notifica_solo_los_prestamos_activos_vencidos(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24'));

        $this->crearPrestamoActivo(Carbon::parse('2026-07-10')); // atrasado
        $this->crearPrestamoActivo(Carbon::parse('2026-08-01')); // al dia

        $observador = Mockery::mock(NotificadorAtraso::class);
        $observador->shouldReceive('notificar')->once();
        $this->gestor->agregarObservador($observador);

        $this->gestor->verificarAtrasos();

        Carbon::setTestNow();
    }

    public function test_gestor_no_conoce_la_implementacion_concreta_del_notificador(): void
    {
        // GestorPrestamos solo depende de la interfaz NotificadorAtraso (DIP). Con
        // ConsolaNotificador real (no un mock) demostramos que cualquier implementacion
        // que cumpla el contrato funciona sin cambiar una sola linea de GestorPrestamos.
        Carbon::setTestNow(Carbon::parse('2026-07-24'));
        $prestamo = $this->crearPrestamoActivo(Carbon::parse('2026-07-10'));

        $this->gestor->agregarObservador(new ConsolaNotificador());

        $this->expectOutputRegex('/ATRASO.*Prestamo #'.$prestamo->id.'/');
        $this->gestor->verificarAtrasos();

        Carbon::setTestNow();
    }
}
