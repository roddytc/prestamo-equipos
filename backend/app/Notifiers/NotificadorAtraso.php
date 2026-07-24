<?php

namespace App\Notifiers;

use App\Models\Prestamo;

interface NotificadorAtraso
{
    public function notificar(Prestamo $prestamo): void;
}
