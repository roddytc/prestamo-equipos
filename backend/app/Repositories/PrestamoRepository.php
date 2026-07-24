<?php

namespace App\Repositories;

use App\Enums\EstadoPrestamo;
use App\Models\Prestamo;
use Illuminate\Database\Eloquent\Collection;

class PrestamoRepository
{
    public function buscar(int $id): ?Prestamo
    {
        return Prestamo::find($id);
    }

    public function guardar(Prestamo $prestamo): void
    {
        $prestamo->save();
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
