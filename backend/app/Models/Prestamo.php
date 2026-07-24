<?php

namespace App\Models;

use App\Enums\EstadoPrestamo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prestamo extends Model
{
    protected $fillable = [
        'usuario_id',
        'equipo_id',
        'fecha_prestamo',
        'fecha_dev_esperada',
        'fecha_dev_real',
        'estado',
    ];

    protected $casts = [
        'fecha_prestamo' => 'date',
        'fecha_dev_esperada' => 'date',
        'fecha_dev_real' => 'date',
        'estado' => EstadoPrestamo::class,
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function estaAtrasado(): bool
    {
        $referencia = $this->fecha_dev_real ?? now();

        return $referencia->greaterThan($this->fecha_dev_esperada);
    }

    public function registrarDevolucion(): void
    {
        $this->fecha_dev_real = now();
        $this->estado = EstadoPrestamo::DEVUELTO;
        $this->save();
    }
}
