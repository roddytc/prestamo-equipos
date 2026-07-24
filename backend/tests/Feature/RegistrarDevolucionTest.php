<?php

namespace Tests\Feature;

use App\Enums\EstadoEquipo;
use App\Enums\EstadoPrestamo;
use App\Enums\RolUsuario;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Usuario;
use App\Notifiers\NotificadorAtraso;
use App\Repositories\EquipoRepository;
use App\Repositories\PrestamoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\GestorPrestamos;
use App\Services\OperacionInvalidaException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class RegistrarDevolucionTest extends TestCase
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
        $equipo = Equipo::create(['nombre' => 'Laptop Dell', 'categoria' => 'Laptop', 'estado' => EstadoEquipo::PRESTADO]);

        return Prestamo::create([
            'usuario_id' => $usuario->id,
            'equipo_id' => $equipo->id,
            'fecha_prestamo' => $fechaDevEsperada->copy()->subDays(7),
            'fecha_dev_esperada' => $fechaDevEsperada,
            'estado' => EstadoPrestamo::ACTIVO,
        ]);
    }

    public function test_devolucion_sin_dano_y_sin_atraso_libera_el_equipo_y_no_notifica(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24'));
        $prestamo = $this->crearPrestamoActivo(Carbon::parse('2026-07-31'));

        $observador = Mockery::mock(NotificadorAtraso::class);
        $observador->shouldNotReceive('notificar');
        $this->gestor->agregarObservador($observador);

        $resultado = $this->gestor->registrarDevolucion($prestamo->id, danado: false);

        $this->assertSame(EstadoPrestamo::DEVUELTO, $resultado->estado);
        $this->assertSame(EstadoEquipo::DISPONIBLE, $prestamo->equipo->fresh()->estado);

        Carbon::setTestNow();
    }

    public function test_devolucion_marcando_el_equipo_danado_no_lo_deja_disponible(): void
    {
        $prestamo = $this->crearPrestamoActivo(Carbon::now()->addDays(3));

        $this->gestor->registrarDevolucion($prestamo->id, danado: true);

        $this->assertSame(EstadoEquipo::DANADO, $prestamo->equipo->fresh()->estado);
    }

    public function test_devolucion_atrasada_notifica_a_todos_los_observadores(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24'));
        $prestamo = $this->crearPrestamoActivo(Carbon::parse('2026-07-10'));

        $observadorUno = Mockery::mock(NotificadorAtraso::class);
        $observadorUno->shouldReceive('notificar')->once();
        $observadorDos = Mockery::mock(NotificadorAtraso::class);
        $observadorDos->shouldReceive('notificar')->once();

        $this->gestor->agregarObservador($observadorUno);
        $this->gestor->agregarObservador($observadorDos);

        $this->gestor->registrarDevolucion($prestamo->id);

        Carbon::setTestNow();
    }

    public function test_rechaza_si_el_prestamo_no_existe_o_ya_fue_devuelto(): void
    {
        $prestamo = $this->crearPrestamoActivo(Carbon::now()->addDays(3));
        $prestamo->registrarDevolucion();

        $this->expectException(OperacionInvalidaException::class);

        $this->gestor->registrarDevolucion($prestamo->id);
    }
}
