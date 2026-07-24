<?php

namespace App\Notifiers;

use App\Models\Prestamo;

class ConsolaNotificador implements NotificadorAtraso
{
    public function notificar(Prestamo $prestamo): void
    {
        echo sprintf(
            "[ATRASO] Prestamo #%d del equipo '%s' vencio el %s.\n",
            $prestamo->id,
            $prestamo->equipo->nombre,
            $prestamo->fecha_dev_esperada->toDateString(),
        );
    }
}
