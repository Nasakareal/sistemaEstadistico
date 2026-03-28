<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use Illuminate\Http\Request;

class ZonaMapaController extends Controller
{
    public function index()
    {
        return view('hechos.zonas.index');
    }

    public function hechosEnGeometria(Request $request)
    {
        $request->validate([
            'geometry' => ['required', 'array'],
            'geometry.type' => ['required', 'string'],
            'geometry.coordinates' => ['required', 'array'],
            'bounds' => ['nullable', 'array'],
            'bounds.north' => ['nullable', 'numeric'],
            'bounds.south' => ['nullable', 'numeric'],
            'bounds.east' => ['nullable', 'numeric'],
            'bounds.west' => ['nullable', 'numeric'],
            'buffer_metros' => ['nullable', 'numeric', 'min:0'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
        ]);

        $geometry = $request->input('geometry');
        $bufferMetros = (float) ($request->input('buffer_metros', 50));

        $bounds = $request->filled('bounds')
            ? [
                'north' => (float) $request->input('bounds.north'),
                'south' => (float) $request->input('bounds.south'),
                'east'  => (float) $request->input('bounds.east'),
                'west'  => (float) $request->input('bounds.west'),
            ]
            : $this->extraerBoundsDesdeGeometry($geometry, $bufferMetros);

        $query = Hechos::query()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween('lat', [$bounds['south'], $bounds['north']])
            ->whereBetween('lng', [$bounds['west'], $bounds['east']]);

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->fecha_fin);
        }

        $hechos = $query
            ->select('id', 'folio_c5i', 'fecha', 'hora', 'calle', 'colonia', 'municipio', 'lat', 'lng', 'tipo_hecho', 'situacion')
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get()
            ->filter(function ($hecho) use ($geometry, $bufferMetros) {
                return $this->puntoDentroDeGeometria(
                    (float) $hecho->lat,
                    (float) $hecho->lng,
                    $geometry,
                    $bufferMetros
                );
            })
            ->values();

        return response()->json([
            'total' => $hechos->count(),
            'hechos' => $hechos,
        ]);
    }

    protected function extraerBoundsDesdeGeometry(array $geometry, float $bufferMetros = 0): array
    {
        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? [];
        $coords = [];

        if ($type === 'Polygon') {
            $coords = $coordinates[0] ?? [];
        } elseif ($type === 'MultiPolygon') {
            $coords = $coordinates[0][0] ?? [];
        } elseif ($type === 'LineString') {
            $coords = $coordinates;
        }

        $lats = [];
        $lngs = [];

        foreach ($coords as $point) {
            if (is_array($point) && count($point) >= 2) {
                $lngs[] = (float) $point[0];
                $lats[] = (float) $point[1];
            }
        }

        if (empty($lats) || empty($lngs)) {
            return [
                'north' => 90,
                'south' => -90,
                'east' => 180,
                'west' => -180,
            ];
        }

        $north = max($lats);
        $south = min($lats);
        $east = max($lngs);
        $west = min($lngs);

        if ($type === 'LineString' && $bufferMetros > 0) {
            $latCentro = ($north + $south) / 2;
            $deltaLat = $bufferMetros / 111320;
            $deltaLng = $bufferMetros / (111320 * max(cos(deg2rad($latCentro)), 0.00001));

            $north += $deltaLat;
            $south -= $deltaLat;
            $east += $deltaLng;
            $west -= $deltaLng;
        }

        return [
            'north' => $north,
            'south' => $south,
            'east' => $east,
            'west' => $west,
        ];
    }

    protected function puntoDentroDeGeometria(float $lat, float $lng, array $geometry, float $bufferMetros = 0): bool
    {
        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? [];

        if ($type === 'Polygon') {
            $ring = $coordinates[0] ?? [];
            return $this->pointInPolygonOrOnEdge($lng, $lat, $ring);
        }

        if ($type === 'MultiPolygon') {
            foreach ($coordinates as $polygon) {
                $ring = $polygon[0] ?? [];
                if ($this->pointInPolygonOrOnEdge($lng, $lat, $ring)) {
                    return true;
                }
            }
            return false;
        }

        if ($type === 'LineString') {
            return $this->pointNearLineString($lat, $lng, $coordinates, $bufferMetros);
        }

        return false;
    }

    protected function pointInPolygonOrOnEdge(float $x, float $y, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);

        if ($count < 3) {
            return false;
        }

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $polygon[$i][0];
            $yi = (float) $polygon[$i][1];
            $xj = (float) $polygon[$j][0];
            $yj = (float) $polygon[$j][1];

            if ($this->pointOnSegment($x, $y, $xi, $yi, $xj, $yj)) {
                return true;
            }

            $intersects = (($yi > $y) !== ($yj > $y))
                && ($x < (($xj - $xi) * ($y - $yi)) / (($yj - $yi) ?: 0.0000000001) + $xi);

            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    protected function pointOnSegment(float $px, float $py, float $x1, float $y1, float $x2, float $y2): bool
    {
        $cross = ($py - $y1) * ($x2 - $x1) - ($px - $x1) * ($y2 - $y1);

        if (abs($cross) > 0.0000001) {
            return false;
        }

        $dot = ($px - $x1) * ($x2 - $x1) + ($py - $y1) * ($y2 - $y1);

        if ($dot < 0) {
            return false;
        }

        $lenSq = ($x2 - $x1) * ($x2 - $x1) + ($y2 - $y1) * ($y2 - $y1);

        if ($dot > $lenSq) {
            return false;
        }

        return true;
    }

    protected function pointNearLineString(float $lat, float $lng, array $lineCoords, float $bufferMetros): bool
    {
        if (count($lineCoords) < 2) {
            return false;
        }

        for ($i = 0; $i < count($lineCoords) - 1; $i++) {
            $a = $lineCoords[$i];
            $b = $lineCoords[$i + 1];

            $dist = $this->distancePointToSegmentMeters(
                $lat,
                $lng,
                (float) $a[1],
                (float) $a[0],
                (float) $b[1],
                (float) $b[0]
            );

            if ($dist <= $bufferMetros) {
                return true;
            }
        }

        return false;
    }

    protected function distancePointToSegmentMeters(
        float $pLat,
        float $pLng,
        float $aLat,
        float $aLng,
        float $bLat,
        float $bLng
    ): float {
        $latFactor = 111320.0;
        $lngFactor = 111320.0 * cos(deg2rad(($aLat + $bLat + $pLat) / 3));

        $px = $pLng * $lngFactor;
        $py = $pLat * $latFactor;
        $ax = $aLng * $lngFactor;
        $ay = $aLat * $latFactor;
        $bx = $bLng * $lngFactor;
        $by = $bLat * $latFactor;

        $dx = $bx - $ax;
        $dy = $by - $ay;

        if ($dx == 0.0 && $dy == 0.0) {
            return sqrt(($px - $ax) ** 2 + ($py - $ay) ** 2);
        }

        $t = (($px - $ax) * $dx + ($py - $ay) * $dy) / (($dx * $dx) + ($dy * $dy));
        $t = max(0, min(1, $t));

        $projX = $ax + $t * $dx;
        $projY = $ay + $t * $dy;

        return sqrt(($px - $projX) ** 2 + ($py - $projY) ** 2);
    }
}
