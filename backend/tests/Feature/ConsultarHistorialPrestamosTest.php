<?php

namespace Tests\Feature;

use App\Enums\EstadoEquipo;
use App\Enums\EstadoPrestamo;
use App\Enums\RolUsuario;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Usuario;
use App\Repositories\EquipoRepository;
use App\Repositories\PrestamoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\GestorPrestamos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConsultarHistorialPrestamosTest extends TestCase
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

    private function crearPrestamo(EstadoPrestamo $estado, Carbon $fechaDevEsperada): Prestamo
    {
        $usuario = Usuario::create(['nombre' => 'Ana', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => true]);
        $equipo = Equipo::create(['nombre' => 'Equipo', 'categoria' => 'Categoria', 'estado' => EstadoEquipo::PRESTADO]);

        return Prestamo::create([
            'usuario_id' => $usuario->id,
            'equipo_id' => $equipo->id,
            'fecha_prestamo' => $fechaDevEsperada->copy()->subDays(7),
            'fecha_dev_esperada' => $fechaDevEsperada,
            'fecha_dev_real' => $estado === EstadoPrestamo::DEVUELTO ? Carbon::now() : null,
            'estado' => $estado,
        ]);
    }

    public function test_retorna_vacio_cuando_no_hay_prestamos(): void
    {
        $this->assertCount(0, $this->gestor->listarPrestamosActivos());
    }

    public function test_lista_solo_los_activos_e_ignora_los_devueltos(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24'));

        $activo = $this->crearPrestamo(EstadoPrestamo::ACTIVO, Carbon::parse('2026-07-30'));
        $this->crearPrestamo(EstadoPrestamo::DEVUELTO, Carbon::parse('2026-07-20'));

        $activos = $this->gestor->listarPrestamosActivos();

        $this->assertCount(1, $activos);
        $this->assertSame($activo->id, $activos->first()->id);

        Carbon::setTestNow();
    }

    public function test_indica_correctamente_cuales_activos_estan_atrasados(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24'));

        $atrasado = $this->crearPrestamo(EstadoPrestamo::ACTIVO, Carbon::parse('2026-07-10'));
        $alDia = $this->crearPrestamo(EstadoPrestamo::ACTIVO, Carbon::parse('2026-08-01'));

        $activos = $this->gestor->listarPrestamosActivos()->keyBy('id');

        $this->assertTrue($activos->get($atrasado->id)->estaAtrasado());
        $this->assertFalse($activos->get($alDia->id)->estaAtrasado());

        Carbon::setTestNow();
    }
}
