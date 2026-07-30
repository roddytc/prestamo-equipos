<?php

namespace App\Repositories;

use App\Models\Usuario;

/**
 * @extends Repository<Usuario>
 */
class UsuarioRepository extends Repository
{
    protected function modelo(): string
    {
        return Usuario::class;
    }
}
