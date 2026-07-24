<?php

namespace Database\Seeders;

use App\Enums\EstadoEquipo;
use App\Enums\RolUsuario;
use App\Models\Equipo;
use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database con datos de ejemplo para probar la API.
     */
    public function run(): void
    {
        Usuario::create(['nombre' => 'Ana Torres', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => true]);
        Usuario::create(['nombre' => 'Luis Vera', 'rol' => RolUsuario::DOCENTE, 'habilitado' => true]);
        Usuario::create(['nombre' => 'Carlos Mora', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => false]);

        Equipo::create(['nombre' => 'Laptop Dell Latitude', 'categoria' => 'Laptop', 'estado' => EstadoEquipo::DISPONIBLE]);
        Equipo::create(['nombre' => 'Proyector Epson X05', 'categoria' => 'Proyector', 'estado' => EstadoEquipo::DISPONIBLE]);
        Equipo::create(['nombre' => 'Camara Canon EOS', 'categoria' => 'Camara', 'estado' => EstadoEquipo::PRESTADO]);
    }
}
