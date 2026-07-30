<?php

namespace App\Services;

final class SolicitudPrestamo
{
    public function __construct(
        public readonly int $usuarioId,
        public readonly int $equipoId,
        public readonly int $dias,
    ) {
    }
}
