<?php

namespace App\Http\Controllers;

use App\Models\Delegacion;

class MapaDelegacionesController extends Controller
{
    public function index()
    {
        return view('admin.settings.delegaciones.mapa');
    }

    public function data()
    {
        $delegaciones = Delegacion::query()
            ->where('activa', 1)
            ->orderBy('delegacion_padre_id')
            ->orderBy('clave')
            ->get();

        $padres = $delegaciones
            ->whereNull('delegacion_padre_id')
            ->values();

        $colores = [
            '#2563eb',
            '#16a34a',
            '#dc2626',
            '#9333ea',
            '#ea580c',
            '#0891b2',
            '#be123c',
            '#4f46e5',
            '#65a30d',
            '#ca8a04',
            '#0f766e',
            '#7c3aed',
        ];

        $colorPorPadre = [];

        foreach ($padres as $index => $padre) {
            $colorPorPadre[$padre->id] = $colores[$index % count($colores)];
        }

        $data = $delegaciones->map(function ($delegacion) use ($colorPorPadre) {
            $padreId = $delegacion->delegacion_padre_id ?: $delegacion->id;

            return [
                'id' => $delegacion->id,
                'delegacion_padre_id' => $delegacion->delegacion_padre_id,
                'clave' => $delegacion->clave,
                'nombre' => $delegacion->nombre,
                'municipio' => $delegacion->municipio,
                'lat' => $delegacion->lat,
                'lng' => $delegacion->lng,
                'color' => $colorPorPadre[$padreId] ?? '#64748b',
            ];
        });

        return response()->json($data);
    }
}
