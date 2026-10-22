<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HoneypotRequestAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeceptionApiController extends Controller
{
    public function handle(Request $request, HoneypotRequestAnalyzer $analyzer, string $path = ''): JsonResponse
    {
        $path = trim($path, '/');
        $analysis = $analyzer->inspect($request, $path);

        if ($analysis['restricted']) {
            return $this->reply([
                'error' => 'restricted_resource',
                'message' => 'This resource requires an elevated service token.',
                'request_id' => $this->requestId($request),
            ], 403);
        }

        $profile = $this->profileFor($path);

        return $this->reply($profile['body'], $profile['status']);
    }

    private function profileFor(string $path): array
    {
        if ($path === '') {
            return ['status' => 200, 'body' => [
                'service' => 'records-legacy',
                'version' => '1.7.4',
                'status' => 'operational',
                'resources' => ['personnel', 'incidents', 'reports', 'audit'],
            ]];
        }

        if (preg_match('#^personnel(?:/[^/]+)?$#i', $path) === 1) {
            return ['status' => 200, 'body' => [
                'data' => [
                    ['id' => 'EMP-90017', 'name' => 'Adriana Muestra', 'unit' => 'Operaciones Norte', 'status' => 'active'],
                    ['id' => 'EMP-90031', 'name' => 'Bruno Ejemplo', 'unit' => 'Enlace Regional', 'status' => 'active'],
                    ['id' => 'EMP-90058', 'name' => 'Carla Demostración', 'unit' => 'Archivo Central', 'status' => 'leave'],
                ],
                'page' => 1,
                'per_page' => 25,
                'total' => 3,
            ]];
        }

        if (preg_match('#^incidents(?:/[^/]+)?$#i', $path) === 1) {
            return ['status' => 200, 'body' => [
                'data' => [
                    ['folio' => 'LEG-2024-0917', 'classification' => 'reserved', 'status' => 'closed'],
                    ['folio' => 'LEG-2024-1042', 'classification' => 'internal', 'status' => 'review'],
                ],
                'total' => 2,
            ]];
        }

        if (preg_match('#^reports(?:/[^/]+)?$#i', $path) === 1) {
            return ['status' => 200, 'body' => [
                'data' => [
                    ['id' => 'RPT-0038', 'title' => 'Consolidated operations archive', 'format' => 'csv'],
                    ['id' => 'RPT-0041', 'title' => 'Personnel assignment history', 'format' => 'xlsx'],
                ],
                'total' => 2,
            ]];
        }

        return ['status' => 404, 'body' => [
            'error' => 'resource_not_found',
            'message' => 'The requested legacy resource was not found.',
        ]];
    }

    private function reply(array $body, int $status): JsonResponse
    {
        return response()->json($body, $status, [
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function requestId(Request $request): string
    {
        return substr(hash('sha256', (string) $request->ip() . '|' . microtime(true)), 0, 16);
    }
}
