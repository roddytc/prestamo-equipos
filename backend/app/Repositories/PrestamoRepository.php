<?php

namespace App\Repositories;

use App\Enums\EstadoPrestamo;
use App\Models\Prestamo;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Repository<Prestamo>
 */
class PrestamoRepository extends Repository
{
    protected function modelo(): string
    {
        return Prestamo::class;
    }

    public function listar(): Collection
    {
        return Prestamo::all();
    }

    public function listarActivos(): Collection
    {
        return Prestamo::where('estado', EstadoPrestamo::ACTIVO)->get();
    }
}
