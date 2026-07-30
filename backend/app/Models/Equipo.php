<?php

namespace App\Models;

use App\Enums\EstadoEquipo;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $fillable = ['nombre', 'categoria', 'estado'];

    protected $casts = [
        'estado' => EstadoEquipo::class,
    ];

    public function estaDisponible(): bool
    {
        return $this->estado === EstadoEquipo::DISPONIBLE;
    }

    public function cambiarEstado(EstadoEquipo $estado): void
    {
        $this->estado = $estado;
        $this->save();
    }

    public function registrarDevolucion(bool $danado): void
    {
        $this->cambiarEstado($danado ? EstadoEquipo::DANADO : EstadoEquipo::DISPONIBLE);
    }
}
