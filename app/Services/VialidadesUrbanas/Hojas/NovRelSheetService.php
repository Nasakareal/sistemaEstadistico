<?php

namespace App\Services\VialidadesUrbanas\Hojas;

use App\Models\Actividad;
use App\Models\PuestaDisposicion;
use App\Models\Unidad;
use App\Models\VialidadDispositivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NovRelSheetService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;

    public function generar(Worksheet $sheet, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $this->render($sheet, $this->novedadesRelevantes($inicio, $fin));
    }

    private function render(Worksheet $sheet, array $rows): void
    {
        $this->aplicarFormatoBase($sheet);

        $fila = 2;

        if (empty($rows)) {
            $this->aplicarEstiloFila($sheet, $fila);
            $sheet->mergeCells("B{$fila}:I{$fila}");
            $sheet->setCellValue("A{$fila}", '-');
            $sheet->setCellValue("B{$fila}", 'SIN NOVEDADES RELEVANTES EN EL PERIODO.');
        }

        foreach ($rows as $index => $row) {
            $this->aplicarEstiloFila($sheet, $fila);

            $sheet->setCellValue("A{$fila}", $index + 1);
            $sheet->setCellValue("B{$fila}", $row['hora'] ?? '');
            $sheet->setCellValue("C{$fila}", $row['lugar'] ?? '');
            $sheet->setCellValue("D{$fila}", $row['asunto'] ?? '');
            $sheet->setCellValue("E{$fila}", $row['resolucion'] ?? '');
            $sheet->setCellValue("F{$fila}", $row['generales'] ?? 'Sin novedad');

            foreach (array_slice($row['fotos'] ?? [], 0, 3) as $fotoIndex => $foto) {
                $this->insertarImagen($sheet, $foto, ['G', 'H', 'I'][$fotoIndex], $fila);
            }

            $fila++;
        }

        $sheet->freezePane('A2');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
    }

    private function aplicarFormatoBase(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(false);
        $sheet->getSheetView()->setZoomScale(85);

        $headers = [
            'A' => 'No.',
            'B' => 'HORA',
            'C' => 'LUGAR',
            'D' => 'ASUNTO',
            'E' => 'RESOLUCIÓN',
            'F' => "VEHÍCULOS TURNADOS, PERSONAS\nDETENIDAS, VEHÍCULOS RECUPERADOS\n(CANTIDAD Y DATOS GENERALES)",
            'G' => 'GRAFICA 1',
            'H' => 'GRAFICA 2',
            'I' => 'Grafica 3',
        ];

        foreach ($headers as $column => $header) {
            $sheet->setCellValue("{$column}1", $header);
        }

        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(38);
        $sheet->getColumnDimension('E')->setWidth(44);
        $sheet->getColumnDimension('F')->setWidth(38);
        $sheet->getColumnDimension('G')->setWidth(34);
        $sheet->getColumnDimension('H')->setWidth(34);
        $sheet->getColumnDimension('I')->setWidth(34);
        $sheet->getRowDimension(1)->setRowHeight(52);

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    private function aplicarEstiloFila(Worksheet $sheet, int $fila): void
    {
        $sheet->getRowDimension($fila)->setRowHeight(262);
        $sheet->getStyle("A{$fila}:I{$fila}")->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle("E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function novedadesRelevantes(Carbon $inicio, Carbon $fin): array
    {
        $actividades = $this->actividadesVialidadesUrbanas($inicio, $fin)
            ->map(fn ($actividad) => $this->filaActividad($actividad));

        $dispositivos = $this->dispositivosVialidadesUrbanas($inicio, $fin)
            ->map(fn ($dispositivo) => $this->filaDispositivo($dispositivo));

        $puestas = $this->puestasDisposicionVialidadesUrbanas($inicio, $fin)
            ->map(fn ($puesta) => $this->filaPuestaDisposicion($puesta));

        return $actividades
            ->merge($dispositivos)
            ->merge($puestas)
            ->filter(fn (array $row) => $this->tieneContenidoRelevante($row))
            ->sortBy([
                ['fecha_hora', 'asc'],
                ['orden_tipo', 'asc'],
            ])
            ->values()
            ->map(function (array $row): array {
                unset($row['fecha_hora'], $row['orden_tipo']);

                return $row;
            })
            ->all();
    }

    private function actividadesVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return Actividad::query()
            ->with(['categoria', 'subcategoria', 'fotos', 'vehiculos'])
            ->whereIn('unidad_org_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function dispositivosVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return VialidadDispositivo::query()
            ->with(['catalogo', 'detalles', 'fotos'])
            ->whereIn('unidad_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function puestasDisposicionVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return PuestaDisposicion::query()
            ->with(['hecho', 'personas', 'vehiculos.vehiculo', 'objetos'])
            ->whereIn('unidad_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha_puesta), COALESCE(hora_puesta, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha_puesta), COALESCE(hora_puesta, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function filaActividad($actividad): array
    {
        return [
            'fecha_hora' => $this->fechaHoraOrden($actividad->fecha ?? null, $actividad->hora ?? null),
            'orden_tipo' => 1,
            'hora' => $this->horaTexto($actividad->hora ?? null),
            'lugar' => $this->lugarTexto($actividad->municipio ?? null, $actividad->lugar ?? null),
            'asunto' => $this->asuntoActividad($actividad),
            'resolucion' => $this->textoConSaltos([
                $actividad->acciones_realizadas ?? null,
                $actividad->narrativa ?? null,
                $actividad->observaciones ?? null,
                $actividad->motivo ?? null,
            ]),
            'generales' => $this->generalesActividad($actividad),
            'fotos' => $this->rutasFotosActividad($actividad),
        ];
    }

    private function filaDispositivo($dispositivo): array
    {
        return [
            'fecha_hora' => $this->fechaHoraOrden($dispositivo->fecha ?? null, $dispositivo->hora ?? null),
            'orden_tipo' => 2,
            'hora' => $this->horaTexto($dispositivo->hora ?? null),
            'lugar' => $this->lugarTexto($dispositivo->municipio ?? null, $dispositivo->lugar ?? null),
            'asunto' => $this->asuntoDispositivo($dispositivo),
            'resolucion' => $this->textoConSaltos(array_merge([
                $dispositivo->descripcion ?? null,
                $dispositivo->narrativa ?? null,
                $dispositivo->acciones_realizadas ?? null,
                $dispositivo->observaciones ?? null,
            ], $this->textosDetalleDispositivo($dispositivo))),
            'generales' => 'Sin novedad',
            'fotos' => $this->rutasFotosDispositivo($dispositivo),
        ];
    }

    private function filaPuestaDisposicion($puesta): array
    {
        return [
            'fecha_hora' => $this->fechaHoraOrden($puesta->fecha_puesta ?? null, $puesta->hora_puesta ?? null),
            'orden_tipo' => 3,
            'forzar' => true,
            'hora' => $this->horaTexto($puesta->hora_puesta ?? null),
            'lugar' => $this->lugarPuesta($puesta),
            'asunto' => $this->asuntoPuestaDisposicion($puesta),
            'resolucion' => $this->textoConSaltos([
                $puesta->narrativa ?? null,
                $puesta->observaciones ?? null,
            ]),
            'generales' => $this->generalesPuestaDisposicion($puesta),
            'fotos' => [],
        ];
    }

    private function tieneContenidoRelevante(array $row): bool
    {
        return !empty($row['forzar'])
            || trim((string) ($row['resolucion'] ?? '')) !== ''
            || !empty($row['fotos']);
    }

    private function asuntoActividad($actividad): string
    {
        $categoria = trim((string) (optional($actividad->categoria)->nombre ?? ''));
        $subcategoria = trim((string) (optional($actividad->subcategoria)->nombre ?? ''));
        $nombre = trim((string) ($actividad->nombre ?? ''));

        $base = $categoria !== '' ? $categoria : ($nombre !== '' ? $nombre : 'ACTIVIDAD');
        $detalle = $subcategoria !== '' ? $subcategoria : $nombre;

        if ($detalle !== '' && $this->normalizar($detalle) !== $this->normalizar($base)) {
            return $this->mayusculas($base . "\n(" . $detalle . ')');
        }

        return $this->mayusculas($base);
    }

    private function asuntoDispositivo($dispositivo): string
    {
        $catalogo = trim((string) (optional($dispositivo->catalogo)->nombre ?? ''));
        $asunto = trim((string) ($dispositivo->asunto ?? ''));

        $base = $catalogo !== '' ? $catalogo : ($asunto !== '' ? $asunto : 'DISPOSITIVO');

        if ($asunto !== '' && $this->normalizar($asunto) !== $this->normalizar($base)) {
            return $this->mayusculas($base . "\n(" . $asunto . ')');
        }

        return $this->mayusculas($base);
    }

    private function asuntoPuestaDisposicion($puesta): string
    {
        $folio = trim(implode('/', array_filter([
            $puesta->numero_puesta ?? null,
            $puesta->anio ?? null,
        ])));
        $tipo = trim((string) ($puesta->tipo_puesta ?? ''));
        $motivo = trim((string) ($puesta->motivo ?? ''));
        $partes = ['PUESTA A DISPOSICIÓN'];

        if ($folio !== '') {
            $partes[] = $folio;
        }

        if ($tipo !== '') {
            $partes[] = $tipo;
        }

        if ($motivo !== '') {
            $partes[] = '(' . $motivo . ')';
        }

        return $this->mayusculas(implode("\n", $partes));
    }

    private function generalesActividad($actividad): string
    {
        $partes = [];

        $detenidas = (int) ($actividad->personas_detenidas ?? 0);

        if ($detenidas > 0) {
            $partes[] = $detenidas . ' personas detenidas';
        }

        $vehiculos = $actividad->relationLoaded('vehiculos') ? $actividad->vehiculos : collect();

        if ($vehiculos->count() > 0) {
            $partes[] = $this->vehiculosTexto($vehiculos);
        }

        return $partes ? $this->mayusculas(implode("\n", $partes)) : 'Sin novedad';
    }

    private function generalesPuestaDisposicion($puesta): string
    {
        $partes = [];
        $personas = $puesta->relationLoaded('personas') ? $puesta->personas : collect();
        $vehiculos = $puesta->relationLoaded('vehiculos') ? $puesta->vehiculos : collect();
        $objetos = $puesta->relationLoaded('objetos') ? $puesta->objetos : collect();

        if ($personas->count() > 0) {
            $partes[] = $this->personasPuestaTexto($personas);
        }

        if ($vehiculos->count() > 0) {
            $partes[] = $this->vehiculosPuestaTexto($vehiculos);
        }

        if ($objetos->count() > 0) {
            $partes[] = $this->objetosPuestaTexto($objetos);
        }

        foreach ([
            'AUTORIDAD RECEPTORA' => $puesta->autoridad_receptora ?? null,
            'MP' => $puesta->nombre_mp ?? null,
            'CARPETA' => $puesta->carpeta_investigacion ?? null,
            'OFICIO' => $puesta->oficio ?? null,
        ] as $label => $valor) {
            $valor = trim((string) $valor);

            if ($valor !== '') {
                $partes[] = $label . ': ' . $valor;
            }
        }

        return $partes ? $this->mayusculas(implode("\n", $partes)) : 'Sin novedad';
    }

    private function personasPuestaTexto(Collection $personas): string
    {
        $total = $personas->count();
        $nombres = $personas
            ->map(function ($persona) {
                return trim(implode(' ', array_filter([
                    $persona->nombre_completo ?? null,
                    !empty($persona->alias) ? 'alias ' . $persona->alias : null,
                    $persona->calidad ?? null,
                ])));
            })
            ->filter()
            ->values()
            ->all();

        return $total . ($total === 1 ? ' persona detenida' : ' personas detenidas')
            . ($nombres ? ': ' . implode('; ', $nombres) : '');
    }

    private function vehiculosTexto(Collection $vehiculos): string
    {
        $items = [];

        foreach ($vehiculos as $vehiculo) {
            $partes = array_filter([
                trim((string) ($vehiculo->tipo ?? '')),
                trim((string) ($vehiculo->marca ?? '')),
                trim((string) ($vehiculo->linea ?? '')),
                trim((string) ($vehiculo->placas ?? '')),
            ]);

            if (!empty($partes)) {
                $items[] = implode(' ', $partes);
            }
        }

        if (empty($items)) {
            return $vehiculos->count() . ' vehículos relacionados';
        }

        return $vehiculos->count() . ' vehículos relacionados: ' . implode('; ', $items);
    }

    private function vehiculosPuestaTexto(Collection $vehiculos): string
    {
        $total = $vehiculos->count();
        $items = [];

        foreach ($vehiculos as $vehiculo) {
            $base = $vehiculo->vehiculo ?? null;
            $partes = array_filter([
                trim((string) (($vehiculo->tipo ?? null) ?: ($base->tipo ?? null))),
                trim((string) (($vehiculo->marca ?? null) ?: ($base->marca ?? null))),
                trim((string) (($vehiculo->submarca ?? null) ?: ($base->linea ?? null))),
                trim((string) (($vehiculo->modelo ?? null) ?: ($base->modelo ?? null))),
                trim((string) (($vehiculo->color ?? null) ?: ($base->color ?? null))),
                trim((string) (($vehiculo->placas ?? null) ?: ($base->placas ?? null))),
                trim((string) (($vehiculo->serie ?? null) ?: ($base->serie ?? null))),
                trim((string) ($vehiculo->calidad ?? '')),
            ]);

            if (!empty($partes)) {
                $items[] = implode(' ', $partes);
            }
        }

        if (empty($items)) {
            return $total . ($total === 1 ? ' vehículo turnado' : ' vehículos turnados');
        }

        return $total . ($total === 1 ? ' vehículo turnado: ' : ' vehículos turnados: ') . implode('; ', $items);
    }

    private function objetosPuestaTexto(Collection $objetos): string
    {
        $total = $objetos->count();
        $items = $objetos
            ->map(function ($objeto) {
                return trim(implode(' ', array_filter([
                    $objeto->cantidad ?? null,
                    $objeto->unidad_medida ?? null,
                    $objeto->tipo_objeto ?? null,
                    $objeto->descripcion ?? null,
                ])));
            })
            ->filter()
            ->values()
            ->all();

        return $total . ($total === 1 ? ' objeto asegurado' : ' objetos asegurados')
            . ($items ? ': ' . implode('; ', $items) : '');
    }

    private function textosDetalleDispositivo($dispositivo): array
    {
        if (!$dispositivo->relationLoaded('detalles')) {
            return [];
        }

        return $dispositivo->detalles
            ->map(function ($detalle) {
                return $this->textoConSaltos([
                    $detalle->titulo ?? null,
                    $detalle->ubicacion ?? null,
                    $detalle->contenido ?? null,
                ]);
            })
            ->filter()
            ->values()
            ->all();
    }

    private function rutasFotosActividad($actividad): array
    {
        $rutas = [];

        if ($actividad->relationLoaded('fotos')) {
            foreach ($actividad->fotos as $foto) {
                foreach ([$foto->foto_thumbnail_path ?? null, $foto->foto_path ?? null] as $ruta) {
                    if ($ruta) {
                        $rutas[] = $ruta;
                        break;
                    }
                }
            }
        }

        foreach ([
            $actividad->foto_thumbnail_path ?? null,
            $actividad->foto_path ?? null,
            $actividad->foto_thumbnail_blob_path ?? null,
            $actividad->foto_blob_path ?? null,
        ] as $ruta) {
            if ($ruta) {
                $rutas[] = $ruta;
            }
        }

        return array_values(array_unique(array_filter($rutas)));
    }

    private function rutasFotosDispositivo($dispositivo): array
    {
        if (!$dispositivo->relationLoaded('fotos')) {
            return [];
        }

        return $dispositivo->fotos
            ->sortBy([
                ['portada', 'desc'],
                ['orden', 'asc'],
                ['id', 'asc'],
            ])
            ->pluck('ruta')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function insertarImagen(Worksheet $sheet, string $ruta, string $columna, int $fila): void
    {
        $path = $this->resolverRutaImagen($ruta);

        if (!$path) {
            return;
        }

        $path = $this->rutaCompatibleExcel($path);

        if (!$path) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setPath($path);
        $drawing->setCoordinates($columna . $fila);
        $drawing->setOffsetX(8);
        $drawing->setOffsetY(12);
        $drawing->setResizeProportional(true);
        $drawing->setWidthAndHeight(230, 250);
        $drawing->setWorksheet($sheet);
    }

    private function resolverRutaImagen(string $ruta): ?string
    {
        if (is_file($ruta)) {
            return $ruta;
        }

        $ruta = ltrim($ruta, '/');

        foreach ([
            storage_path('app/public/' . $ruta),
            storage_path('app/' . $ruta),
            public_path('storage/' . $ruta),
            public_path($ruta),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function rutaCompatibleExcel(string $path): ?string
    {
        $tipo = @exif_imagetype($path);

        $tiposCompatibles = array_filter([
            defined('IMAGETYPE_GIF') ? IMAGETYPE_GIF : null,
            defined('IMAGETYPE_JPEG') ? IMAGETYPE_JPEG : null,
            defined('IMAGETYPE_PNG') ? IMAGETYPE_PNG : null,
            defined('IMAGETYPE_BMP') ? IMAGETYPE_BMP : null,
        ]);

        if (in_array($tipo, $tiposCompatibles, true)) {
            return $path;
        }

        if (!defined('IMAGETYPE_WEBP') || $tipo !== IMAGETYPE_WEBP || !function_exists('imagecreatefromwebp')) {
            return null;
        }

        $directorio = storage_path('app/temp_vialidades_nov_rel');

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $destino = $directorio . DIRECTORY_SEPARATOR . md5($path . filemtime($path)) . '.jpg';

        if (is_file($destino)) {
            return $destino;
        }

        $origen = @imagecreatefromwebp($path);

        if (!$origen) {
            return null;
        }

        $guardado = imagejpeg($origen, $destino, 88);
        imagedestroy($origen);

        return $guardado ? $destino : null;
    }

    private function fechaHoraOrden($fecha, $hora): string
    {
        $fechaTexto = $fecha instanceof \DateTimeInterface
            ? $fecha->format('Y-m-d')
            : trim((string) $fecha);
        $horaTexto = $hora instanceof \DateTimeInterface
            ? $hora->format('H:i:s')
            : trim((string) $hora);

        if ($fechaTexto === '') {
            $fechaTexto = '1900-01-01';
        }

        if ($horaTexto === '') {
            $horaTexto = '00:00:00';
        }

        return $fechaTexto . ' ' . substr($horaTexto, 0, 8);
    }

    private function horaTexto($hora): string
    {
        if ($hora instanceof \DateTimeInterface) {
            return $hora->format('H:i') . "\nhrs.";
        }

        $hora = trim((string) $hora);

        return $hora !== '' ? substr($hora, 0, 5) . "\nhrs." : '';
    }

    private function lugarTexto($municipio, $lugar): string
    {
        foreach ([$municipio, $lugar] as $valor) {
            $valor = trim((string) $valor);

            if ($valor !== '') {
                return $this->mayusculasIniciales($valor);
            }
        }

        return 'Morelia';
    }

    private function lugarPuesta($puesta): string
    {
        $hecho = $puesta->relationLoaded('hecho') ? $puesta->hecho : null;

        foreach ([
            $puesta->lugar_puesta ?? null,
            $hecho->municipio ?? null,
            $hecho->calle ?? null,
        ] as $valor) {
            $valor = trim((string) $valor);

            if ($valor !== '') {
                return $this->mayusculasIniciales($valor);
            }
        }

        return 'Morelia';
    }

    private function textoConSaltos(array $partes): string
    {
        return implode("\n\n", array_values(array_filter(array_map(function ($parte) {
            return trim(preg_replace('/\s+/', ' ', (string) $parte) ?? '');
        }, $partes))));
    }

    private function unidadVialidadesUrbanasIds(): array
    {
        $ids = Unidad::query()
            ->where('id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->orWhere(function ($query) {
                $query->where('nombre', 'like', '%VIALIDADES%')
                    ->where('nombre', 'like', '%URBANAS%');
            })
            ->orWhere('slug', 'like', '%vialidades%')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $ids ?: [self::UNIDAD_VIALIDADES_URBANAS_ID];
    }

    private function mayusculas(string $texto): string
    {
        $texto = trim($texto);

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($texto, 'UTF-8');
        }

        return strtoupper($texto);
    }

    private function mayusculasIniciales(string $texto): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto) ?? '');

        if ($texto === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($texto));
    }

    private function normalizar($texto): string
    {
        $texto = mb_strtoupper((string) $texto, 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/', ' ', trim($texto));
    }
}
