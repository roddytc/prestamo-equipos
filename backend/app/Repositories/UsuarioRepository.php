<?php

namespace App\Repositories;

use App\Models\Usuario;

class UsuarioRepository
{
    public function buscar(int $id): ?Usuario
    {
        return Usuario::find($id);
    }

    public function guardar(Usuario $usuario): void
    {
        $usuario->save();
    }
}
