<?php

namespace App\Repositories;

use App\Models\Equipo;

/**
 * @extends Repository<Equipo>
 */
class EquipoRepository extends Repository
{
    protected function modelo(): string
    {
        return Equipo::class;
    }
}
