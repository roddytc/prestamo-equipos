<?php

namespace Tests\Feature;

use App\Enums\EstadoEquipo;
use App\Enums\EstadoPrestamo;
use App\Enums\RolUsuario;
use App\Models\Equipo;
use App\Models\Usuario;
use App\Repositories\EquipoRepository;
use App\Repositories\PrestamoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\GestorPrestamos;
use App\Services\OperacionInvalidaException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RegistrarPrestamoTest extends TestCase
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

    public function test_crea_el_prestamo_cuando_el_usuario_esta_habilitado_y_el_equipo_disponible(): void
    {
        $usuario = Usuario::create(['nombre' => 'Ana', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => true]);
        $equipo = Equipo::create(['nombre' => 'Proyector Epson', 'categoria' => 'Proyector', 'estado' => EstadoEquipo::DISPONIBLE]);

        $prestamo = $this->gestor->registrarPrestamo($usuario->id, $equipo->id, 7);

        $this->assertSame(EstadoPrestamo::ACTIVO, $prestamo->estado);
        $this->assertSame(EstadoEquipo::PRESTADO, $equipo->fresh()->estado);
    }

    public function test_rechaza_si_el_usuario_no_esta_habilitado(): void
    {
        $usuario = Usuario::create(['nombre' => 'Luis', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => false]);
        $equipo = Equipo::create(['nombre' => 'Laptop Dell', 'categoria' => 'Laptop', 'estado' => EstadoEquipo::DISPONIBLE]);

        $this->expectException(OperacionInvalidaException::class);

        try {
            $this->gestor->registrarPrestamo($usuario->id, $equipo->id, 7);
        } finally {
            $this->assertSame(0, \App\Models\Prestamo::count());
            $this->assertSame(EstadoEquipo::DISPONIBLE, $equipo->fresh()->estado);
        }
    }

    public function test_rechaza_si_el_equipo_ya_esta_prestado(): void
    {
        $usuario = Usuario::create(['nombre' => 'Ana', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => true]);
        $equipo = Equipo::create(['nombre' => 'Camara Canon', 'categoria' => 'Camara', 'estado' => EstadoEquipo::PRESTADO]);

        $this->expectException(OperacionInvalidaException::class);

        $this->gestor->registrarPrestamo($usuario->id, $equipo->id, 7);
    }

    public function test_rechaza_si_el_equipo_esta_danado(): void
    {
        $usuario = Usuario::create(['nombre' => 'Ana', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => true]);
        $equipo = Equipo::create(['nombre' => 'Tablet Samsung', 'categoria' => 'Tablet', 'estado' => EstadoEquipo::DANADO]);

        $this->expectException(OperacionInvalidaException::class);

        $this->gestor->registrarPrestamo($usuario->id, $equipo->id, 7);
    }

    public function test_calcula_la_fecha_de_devolucion_esperada_segun_los_dias_indicados(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24'));

        $usuario = Usuario::create(['nombre' => 'Ana', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => true]);
        $equipo = Equipo::create(['nombre' => 'Laptop HP', 'categoria' => 'Laptop', 'estado' => EstadoEquipo::DISPONIBLE]);

        $prestamo = $this->gestor->registrarPrestamo($usuario->id, $equipo->id, 5);

        $this->assertTrue($prestamo->fecha_dev_esperada->isSameDay(Carbon::parse('2026-07-29')));

        Carbon::setTestNow();
    }

    public function test_rechaza_si_el_usuario_o_el_equipo_no_existen(): void
    {
        $this->expectException(OperacionInvalidaException::class);

        $this->gestor->registrarPrestamo(999, 999, 7);
    }
}
