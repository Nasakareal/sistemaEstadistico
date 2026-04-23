<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DelegacionesActividadesPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $inicio = Carbon::now('America/Mexico_City')->startOfDay();
        $fin = Carbon::create(2026, 5, 10, 23, 59, 59, 'America/Mexico_City');

        $delegaciones = DB::table('delegaciones')
            ->where('activa', 1)
            ->orderBy('id')
            ->get();

        $subcategorias = DB::table('actividad_subcategorias')
            ->where('activo', 1)
            ->where(function ($query) {
                $query->whereNull('unidad_id')
                    ->orWhere('unidad_id', 2);
            })
            ->orderBy('actividad_categoria_id')
            ->orderBy('id')
            ->get();

        if ($delegaciones->isEmpty() || $subcategorias->isEmpty()) {
            return;
        }

        $patrullas = [
            '3190', '3223', '3252', '3257', '3286',
            '04-174', '25-1119', '06-246', '26-3343',
            '22-4926', '221107', 'C1028', 'C1030',
            '209237', '04-151', '16-872', '16-852',
            'C1023', '16-888', '25-1169', '222591',
        ];

        $fecha = $inicio->copy();

        while ($fecha->lte($fin)) {
            foreach ($delegaciones as $delegacion) {
                $esPadre = is_null($delegacion->delegacion_padre_id);
                $cantidad = $esPadre ? rand(6, 12) : rand(3, 7);

                for ($i = 0; $i < $cantidad; $i++) {
                    $subcategoria = $subcategorias->random();
                    $municipio = $delegacion->municipio ?: $delegacion->nombre;

                    $hora = Carbon::create(
                        $fecha->year,
                        $fecha->month,
                        $fecha->day,
                        rand(7, 20),
                        rand(0, 59),
                        rand(0, 59),
                        'America/Mexico_City'
                    );

                    $elementos = rand(1, 8);
                    $personasAlcanzadas = $this->personasAlcanzadas($subcategoria->nombre);
                    $patrullasTexto = $this->patrullasTexto($patrullas, $esPadre);

                    DB::table('actividades')->insert([
                        'client_uuid' => (string) Str::uuid(),
                        'sync_status' => 'local',
                        'sync_error' => null,
                        'synced_at' => null,
                        'actividad_categoria_id' => $subcategoria->actividad_categoria_id,
                        'actividad_subcategoria_id' => $subcategoria->id,
                        'nombre' => 'Seeder Delegaciones',
                        'cantidad' => 1,
                        'foto_path' => null,
                        'foto_nombre_original' => null,
                        'foto_hash' => null,
                        'created_by' => null,
                        'updated_by' => null,
                        'estado_revision' => 'pendiente',
                        'revisado_por' => null,
                        'revisado_at' => null,
                        'observacion_revision' => null,
                        'unidad_org_id' => 2,
                        'delegacion_id' => $delegacion->id,
                        'destacamento_id' => null,
                        'fecha' => $fecha->format('Y-m-d'),
                        'hora' => $hora->format('H:i:s'),
                        'lugar' => 'ZONA OPERATIVA DE ' . mb_strtoupper($municipio),
                        'municipio' => mb_strtoupper($municipio),
                        'carretera' => $this->esCarretera($subcategoria->nombre) ? 'CARRETERA ' . mb_strtoupper($delegacion->nombre) : null,
                        'tramo' => $this->esCarretera($subcategoria->nombre) ? 'TRAMO ' . mb_strtoupper($delegacion->nombre) : null,
                        'kilometro' => $this->esCarretera($subcategoria->nombre) ? (string) rand(1, 180) : null,
                        'lat' => null,
                        'lng' => null,
                        'coordenadas_texto' => null,
                        'fuente_ubicacion' => null,
                        'nota_geo' => null,
                        'motivo' => mb_strtoupper($subcategoria->nombre) . ' EN ' . mb_strtoupper($delegacion->nombre),
                        'narrativa' => 'REGISTRO DE PRUEBA PARA ACTIVIDADES DE DELEGACIONES.',
                        'acciones_realizadas' => 'ACTIVIDAD OPERATIVA',
                        'observaciones' => 'REGISTRO DE PRUEBA PARA EXCEL DE DELEGACIONES',
                        'personas_alcanzadas' => $personasAlcanzadas,
                        'personas_participantes' => $elementos,
                        'personas_detenidas' => rand(0, 1),
                        'elementos_participantes_texto' => $this->elementosTexto($elementos),
                        'patrullas_participantes_texto' => $patrullasTexto,
                        'created_at' => $hora->format('Y-m-d H:i:s'),
                        'updated_at' => $hora->format('Y-m-d H:i:s'),
                    ]);
                }
            }

            $fecha->addDay();
        }
    }

    protected function personasAlcanzadas(string $subcategoria): int
    {
        $nombre = mb_strtoupper($subcategoria);

        if (str_contains($nombre, 'ESCUELA') || str_contains($nombre, 'CAPACITACION') || str_contains($nombre, 'CAMPAÑA') || str_contains($nombre, 'TALLER')) {
            return rand(30, 400);
        }

        if (str_contains($nombre, 'EVENTO') || str_contains($nombre, 'MANIFESTACION') || str_contains($nombre, 'MITIN')) {
            return rand(80, 800);
        }

        return rand(0, 120);
    }

    protected function esCarretera(string $subcategoria): bool
    {
        $nombre = mb_strtoupper($subcategoria);

        return str_contains($nombre, 'CARRETERA')
            || str_contains($nombre, 'PATRULLAJE')
            || str_contains($nombre, 'RECORRIDO');
    }

    protected function patrullasTexto(array $catalogo, bool $esPadre): string
    {
        shuffle($catalogo);

        return implode(', ', array_slice($catalogo, 0, $esPadre ? rand(1, 4) : rand(1, 2)));
    }

    protected function elementosTexto(int $cantidad): string
    {
        $elementos = [];

        for ($i = 1; $i <= $cantidad; $i++) {
            $elementos[] = 'ELEMENTO ' . $i;
        }

        return implode(', ', $elementos);
    }
}
