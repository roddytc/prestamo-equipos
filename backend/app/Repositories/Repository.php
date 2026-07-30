<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
abstract class Repository
{
    /**
     * @return class-string<TModel>
     */
    abstract protected function modelo(): string;

    /**
     * @return TModel|null
     */
    public function buscar(int $id): ?Model
    {
        return $this->modelo()::find($id);
    }

    /**
     * @param  TModel  $entidad
     */
    public function guardar(Model $entidad): void
    {
        $entidad->save();
    }
}
