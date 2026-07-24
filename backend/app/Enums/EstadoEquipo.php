<?php

namespace App\Enums;

enum EstadoEquipo: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case PRESTADO = 'PRESTADO';
    case DANADO = 'DANADO';
}
