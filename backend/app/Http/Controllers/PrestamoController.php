<?php

namespace App\Http\Controllers;

use App\Notifiers\LogNotificador;
use App\Services\GestorPrestamos;
use App\Services\OperacionInvalidaException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrestamoController extends Controller
{
    public function __construct(private GestorPrestamos $gestor)
    {
        $this->gestor->agregarObservador(new LogNotificador());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'usuario_id' => 'required|integer',
            'equipo_id' => 'required|integer',
            'dias' => 'required|integer|min:1',
        ]);

        try {
            $prestamo = $this->gestor->registrarPrestamo($data['usuario_id'], $data['equipo_id'], $data['dias']);
        } catch (OperacionInvalidaException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($prestamo->load('usuario', 'equipo'), 201);
    }

    public function devolucion(Request $request, int $id): JsonResponse
    {
        $danado = $request->boolean('danado');

        try {
            $prestamo = $danado
                ? $this->gestor->registrarDevolucionConDano($id)
                : $this->gestor->registrarDevolucionOk($id);
        } catch (OperacionInvalidaException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($prestamo->load('usuario', 'equipo'));
    }

    public function activos(): JsonResponse
    {
        $resultado = $this->gestor->listarPrestamosActivos()->load('usuario', 'equipo')->map(fn ($p) => [
            'id' => $p->id,
            'usuario' => $p->usuario->nombre,
            'equipo' => $p->equipo->nombre,
            'fecha_prestamo' => $p->fecha_prestamo->toDateString(),
            'fecha_dev_esperada' => $p->fecha_dev_esperada->toDateString(),
            'atrasado' => $p->estaAtrasado(),
        ]);

        return response()->json($resultado);
    }
}
