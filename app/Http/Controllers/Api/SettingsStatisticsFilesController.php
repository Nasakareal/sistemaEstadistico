<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patrulla;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsStatisticsFilesController extends Controller
{
    private const MODULES = [
        'siniestros' => [
            'title' => 'Estadisticas Siniestros',
            'subtitle' => 'Parte de novedades, bitacora, mini parte y cortes generados.',
            'unit_ids' => [1],
            'reports' => [
                'parte_novedades' => [
                    'title' => 'Parte de Novedades',
                    'subtitle' => 'Documentos generados por fecha.',
                    'directory' => 'cortes/parte_novedades',
                    'pattern' => '/^parte_novedades_(\d{4}-\d{2}-\d{2})\.docx$/',
                    'filename' => 'parte_novedades_{date}.docx',
                    'extension' => 'docx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
                'bitacora' => [
                    'title' => 'Bitacora',
                    'subtitle' => 'Bitacoras por periodo.',
                    'directory' => 'cortes/bitacora',
                    'pattern' => '/^bitacora_(\d{4}-\d{2}-\d{2})\.docx$/',
                    'filename' => 'bitacora_{date}.docx',
                    'extension' => 'docx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
                'mini_parte' => [
                    'title' => 'Mini Parte',
                    'subtitle' => 'Reportes compactos generados por fecha.',
                    'directory' => 'cortes/mini_parte',
                    'pattern' => '/^mini_parte_(\d{4}-\d{2}-\d{2})\.docx$/',
                    'filename' => 'mini_parte_{date}.docx',
                    'extension' => 'docx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
                'excel_novedades' => [
                    'title' => 'Excel Novedades',
                    'subtitle' => 'Estado de fuerza y novedades en Excel.',
                    'directory' => 'cortes/excel_novedades',
                    'pattern' => '/^excel_novedades_(\d{4}-\d{2}-\d{2})\.xlsx$/',
                    'filename' => 'excel_novedades_{date}.xlsx',
                    'extension' => 'xlsx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                'actividades' => [
                    'title' => 'Actividades',
                    'subtitle' => 'Informe diario de actividades en PDF.',
                    'directory' => 'cortes/actividades',
                    'pattern' => '/^actividades_(\d{4}-\d{2}-\d{2})\.pdf$/',
                    'filename' => 'actividades_{date}.pdf',
                    'extension' => 'pdf',
                    'mime' => 'application/pdf',
                ],
                'sectorizaciones' => [
                    'title' => 'Sectorizaciones',
                    'subtitle' => 'Archivos de sectorizacion por fecha.',
                    'directory' => 'cortes/sectorizaciones',
                    'pattern' => '/^sectorizacion_(\d{4}-\d{2}-\d{2})\.json$/',
                    'filename' => 'sectorizacion_{date}.json',
                    'extension' => 'json',
                    'mime' => 'application/json',
                ],
            ],
        ],
        'delegaciones' => [
            'title' => 'Estadisticas Delegaciones',
            'subtitle' => 'Excel diario y mensual de Delegaciones.',
            'unit_ids' => [2],
            'reports' => [
                'excel_diario' => [
                    'title' => 'Excel Diario',
                    'subtitle' => 'Reportes diarios generados por fecha.',
                    'directory' => 'cortes/excel_delegaciones',
                    'pattern' => '/^excel_delegaciones_(\d{4}-\d{2}-\d{2})\.xlsx$/',
                    'filename' => 'excel_delegaciones_{date}.xlsx',
                    'extension' => 'xlsx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                'excel_mensual' => [
                    'title' => 'Excel Mensual INEGI',
                    'subtitle' => 'Reportes mensuales generados para INEGI.',
                    'directory' => 'cortes/excel_delegaciones_mensual',
                    'pattern' => '/^excel_delegaciones_(\d{4}-\d{2})\.xlsx$/',
                    'filename' => 'excel_delegaciones_{date}.xlsx',
                    'extension' => 'xlsx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'date_pattern' => '/^\d{4}-\d{2}$/',
                ],
            ],
        ],
        'vialidades' => [
            'title' => 'Estadisticas Vialidades Urbanas',
            'subtitle' => 'Excel diario de Vialidades Urbanas.',
            'unit_ids' => [5],
            'reports' => [
                'excel_diario' => [
                    'title' => 'Excel Diario',
                    'subtitle' => 'Corte diario de Vialidades Urbanas.',
                    'directory' => 'cortes/excel_vialidades_urbanas',
                    'pattern' => '/^excel_vialidades_urbanas_(\d{4}-\d{2}-\d{2})\.xlsx$/',
                    'filename' => 'excel_vialidades_urbanas_{date}.xlsx',
                    'extension' => 'xlsx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
            ],
        ],
        'fomento' => [
            'title' => 'Estadisticas Fomento',
            'subtitle' => 'Excel diario de Fomento a la Cultura Vial.',
            'unit_ids' => [6],
            'reports' => [
                'excel_diario' => [
                    'title' => 'Excel Diario',
                    'subtitle' => 'Corte diario de Fomento.',
                    'directory' => 'cortes/excel_fomento',
                    'pattern' => '/^excel_fomento_(\d{4}-\d{2}-\d{2})\.xlsx$/',
                    'filename' => 'excel_fomento_{date}.xlsx',
                    'extension' => 'xlsx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
            ],
        ],
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($this->hasAdministrativeSettingsRole($user), 403);

        $modules = collect(self::MODULES)
            ->filter(fn (array $module, string $moduleId) => $this->canSeeModule($user, $moduleId))
            ->map(fn (array $module, string $moduleId) => $this->modulePayload($moduleId, $module))
            ->values();

        return response()->json([
            'modules' => $modules,
        ]);
    }

    public function download(Request $request, string $module, string $report, string $date)
    {
        $user = $request->user();

        abort_unless($this->hasAdministrativeSettingsRole($user), 403);
        abort_unless(isset(self::MODULES[$module]), 404);
        abort_unless($this->canSeeModule($user, $module), 403);
        abort_unless(isset(self::MODULES[$module]['reports'][$report]), 404);

        $config = self::MODULES[$module]['reports'][$report];
        $datePattern = $config['date_pattern'] ?? '/^\d{4}-\d{2}-\d{2}$/';

        abort_unless(preg_match($datePattern, $date), 404);

        $fileName = str_replace('{date}', $date, $config['filename']);
        $path = storage_path('app/' . $config['directory'] . '/' . $fileName);

        abort_unless(file_exists($path), 404);

        return response()->download($path, $fileName, [
            'Content-Type' => $config['mime'],
        ]);
    }

    private function modulePayload(string $moduleId, array $module): array
    {
        $reports = collect($module['reports'])
            ->map(fn (array $report, string $reportId) => $this->reportPayload($moduleId, $reportId, $report))
            ->values();

        $payload = [
            'id' => $moduleId,
            'title' => $module['title'],
            'subtitle' => $module['subtitle'],
            'reports' => $reports,
        ];

        if ($moduleId === 'siniestros') {
            $payload['patrullas'] = $this->siniestrosPatrolAssignments();
        }

        return $payload;
    }

    private function siniestrosPatrolAssignments(): array
    {
        return Patrulla::query()
            ->whereIn('unidad_id', self::MODULES['siniestros']['unit_ids'])
            ->with(['usuarios' => function ($query) {
                $query->whereIn('unidad_id', self::MODULES['siniestros']['unit_ids'])
                    ->with('turno:id,nombre')
                    ->orderBy('name');
            }])
            ->get()
            ->sort(function (Patrulla $left, Patrulla $right) {
                return strnatcasecmp(
                    (string) $left->numero_economico,
                    (string) $right->numero_economico
                );
            })
            ->values()
            ->map(function (Patrulla $patrulla) {
                return [
                    'id' => $patrulla->id,
                    'numero_economico' => $patrulla->numero_economico,
                    'activa' => (bool) $patrulla->activa,
                    'tipo' => $patrulla->tipo,
                    'marca' => $patrulla->marca,
                    'linea' => $patrulla->linea,
                    'modelo' => $patrulla->modelo,
                    'placas' => $patrulla->placas,
                    'usuarios' => $patrulla->usuarios
                        ->sortBy(function ($user) {
                            return sprintf(
                                '%s|%s',
                                optional($user->turno)->nombre ?: 'ZZZ',
                                $user->nombre_completo
                            );
                        }, SORT_NATURAL | SORT_FLAG_CASE)
                        ->values()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'nombre' => $user->nombre_completo,
                                'estado' => $user->estado,
                                'turno_id' => $user->turno_id,
                                'turno' => optional($user->turno)->nombre,
                            ];
                        })
                        ->all(),
                ];
            })
            ->all();
    }

    private function reportPayload(string $moduleId, string $reportId, array $report): array
    {
        return [
            'id' => $reportId,
            'title' => $report['title'],
            'subtitle' => $report['subtitle'],
            'extension' => $report['extension'],
            'files' => $this->filesForReport($moduleId, $reportId, $report),
        ];
    }

    private function filesForReport(string $moduleId, string $reportId, array $report): array
    {
        $disk = Storage::disk('local');
        $directory = $report['directory'];

        if (!$disk->exists($directory)) {
            return [];
        }

        return collect($disk->files($directory))
            ->map(function (string $path) use ($disk, $moduleId, $reportId, $report) {
                $name = basename($path);

                if (!preg_match($report['pattern'], $name, $matches)) {
                    return null;
                }

                $updatedAt = null;
                try {
                    $updatedAt = Carbon::createFromTimestamp($disk->lastModified($path), 'America/Mexico_City')
                        ->toIso8601String();
                } catch (\Throwable $e) {
                    $updatedAt = null;
                }

                $size = null;
                try {
                    $size = $disk->size($path);
                } catch (\Throwable $e) {
                    $size = null;
                }

                $date = $matches[1] ?? '';

                return [
                    'file_name' => $name,
                    'date' => $date,
                    'extension' => $report['extension'],
                    'size_bytes' => $size,
                    'updated_at' => $updatedAt,
                    'download_endpoint' => "settings/statistics-files/{$moduleId}/{$reportId}/{$date}/download",
                ];
            })
            ->filter()
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    private function canSeeModule($user, string $moduleId): bool
    {
        if (!$user || !isset(self::MODULES[$moduleId])) {
            return false;
        }

        if ($user->isSuperadmin()) {
            return true;
        }

        $unitId = (int) ($user->unidad_id ?? 0);
        if ($unitId === 3) {
            return true;
        }

        return in_array($unitId, self::MODULES[$moduleId]['unit_ids'], true);
    }

    private function hasAdministrativeSettingsRole($user): bool
    {
        return $user && $user->hasAnyRole([
            'Superadmin',
            'Subdirector',
            'Administrador',
            'Administrativo',
        ]);
    }
}
