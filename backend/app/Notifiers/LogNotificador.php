<?php

namespace App\Notifiers;

use App\Models\Prestamo;
use Illuminate\Support\Facades\Log;

class LogNotificador implements NotificadorAtraso
{
    public function notificar(Prestamo $prestamo): void
    {
        Log::warning(sprintf(
            'Prestamo #%d del equipo "%s" esta atrasado (vencio el %s).',
            $prestamo->id,
            $prestamo->equipo->nombre,
            $prestamo->fecha_dev_esperada->toDateString(),
        ));
    }
}
