<?php

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $fillable = ['nombre', 'rol', 'habilitado'];

    protected $casts = [
        'rol' => RolUsuario::class,
        'habilitado' => 'boolean',
    ];

    public function puedeSolicitarPrestamo(): bool
    {
        return $this->habilitado;
    }
}
