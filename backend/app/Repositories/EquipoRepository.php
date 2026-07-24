<?php

namespace App\Repositories;

use App\Models\Equipo;

class EquipoRepository
{
    public function buscar(int $id): ?Equipo
    {
        return Equipo::find($id);
    }

    public function guardar(Equipo $equipo): void
    {
        $equipo->save();
    }
}
