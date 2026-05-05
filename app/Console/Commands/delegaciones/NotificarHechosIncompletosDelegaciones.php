<?php

namespace App\Console\Commands\delegaciones;

use App\Models\Hechos;
use App\Services\DelegacionesWhatsAppAlertService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class NotificarHechosIncompletosDelegaciones extends Command
{
    protected $signature = 'delegaciones:notificar-hechos-incompletos
        {--dry-run : Muestra que alertas se enviarian sin mandar WhatsApp}
        {--hecho_id= : Limita la revision a un hecho especifico}
        {--horas= : Horas minimas para considerar incompleto}
        {--dias= : Dias hacia atras que se revisan}
        {--force : Ignora el bloqueo de una alerta por hora}';

    protected $description = 'Notifica por WhatsApp hechos de delegaciones con captura incompleta por mas de 3 horas';

    private const UNIDAD_DELEGACIONES_ID = 2;

    public function handle(DelegacionesWhatsAppAlertService $alertas): int
    {
        $tz = 'America/Mexico_City';
        $now = Carbon::now($tz);
        $minHours = $this->positiveIntOption(
            'horas',
            (int) config('services.whatsapp.delegaciones.incompletos_min_hours', 3)
        );
        $lookbackDays = $this->positiveIntOption(
            'dias',
            (int) config('services.whatsapp.delegaciones.incompletos_lookback_days', 3)
        );
        $threshold = $now->copy()->subHours($minHours);
        $hechoId = $this->option('hecho_id');

        $query = Hechos::query()
            ->with(['creator', 'unidadOrganizacional', 'delegacion'])
            ->where('unidad_org_id', self::UNIDAD_DELEGACIONES_ID);

        $this->aplicarFiltroCapturaIncompleta($query);

        if ($hechoId) {
            $query->whereKey($hechoId);
        } else {
            $query->whereDate('fecha', '>=', $now->copy()->subDays($lookbackDays)->toDateString())
                ->whereDate('fecha', '<=', $threshold->toDateString());
        }

        $hechos = $query
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->map(function (Hechos $hecho) use ($tz, $now) {
                $eventoAt = $this->fechaHoraHecho($hecho, $tz);

                if (!$eventoAt) {
                    return null;
                }

                $hecho->alerta_delegaciones_evento_at = $eventoAt;
                $hecho->alerta_delegaciones_minutos = (int) $eventoAt->diffInMinutes($now);

                return $hecho;
            })
            ->filter(function ($hecho) use ($threshold) {
                if (!$hecho instanceof Hechos) {
                    return false;
                }

                return !$hecho->capturaCompletaCalculada()
                    && $hecho->alerta_delegaciones_evento_at->lessThanOrEqualTo($threshold);
            })
            ->values();

        if ($hechos->isEmpty()) {
            $this->info('No hay hechos incompletos de delegaciones para alertar.');

            return Command::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->table(
                ['Hecho', 'Fecha/hora', 'Pendiente', 'Faltantes', 'Capturo'],
                $hechos->map(function (Hechos $hecho) {
                    return [
                        $hecho->id,
                        $hecho->alerta_delegaciones_evento_at->format('Y-m-d H:i'),
                        $this->formatoDuracion($hecho->alerta_delegaciones_minutos),
                        implode(', ', $hecho->faltantesCapturaTexto()),
                        optional($hecho->creator)->name ?: 'No especificado',
                    ];
                })->all()
            );

            $this->info('Dry-run: no se envio WhatsApp. Candidatos: ' . $hechos->count());

            return Command::SUCCESS;
        }

        $enviados = 0;
        $omitidos = 0;

        foreach ($hechos as $hecho) {
            if (!$this->option('force') && !$this->marcarAlertaPorHora($hecho, $now)) {
                $omitidos++;
                continue;
            }

            $alertas->notificarHechoIncompleto($hecho, $hecho->alerta_delegaciones_minutos);
            $enviados++;
        }

        $this->info("Alertas procesadas: {$enviados}. Omitidas por frecuencia: {$omitidos}.");

        return Command::SUCCESS;
    }

    private function aplicarFiltroCapturaIncompleta(Builder $query): void
    {
        $query->where(function ($q) {
            $q->where('captura_completa', false)
                ->orWhereNull('captura_completa')
                ->orWhereRaw('COALESCE(vehiculos_capturados, 0) < COALESCE(vehiculos_esperados, 0)')
                ->orWhereRaw('COALESCE(conductores_capturados, 0) < COALESCE(conductores_esperados, 0)')
                ->orWhereRaw('COALESCE(lesionados_capturados, 0) < COALESCE(lesionados_esperados, 0)');
        });
    }

    private function fechaHoraHecho(Hechos $hecho, string $tz): ?Carbon
    {
        if (empty($hecho->fecha)) {
            return null;
        }

        try {
            $fechaTexto = $hecho->fecha instanceof \DateTimeInterface
                ? $hecho->fecha->format('Y-m-d')
                : Carbon::parse($hecho->fecha, $tz)->format('Y-m-d');
        } catch (\Throwable $e) {
            $fechaTexto = substr((string) $hecho->fecha, 0, 10);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaTexto)) {
            return null;
        }

        $horaTexto = '00:00:00';

        if (!empty($hecho->hora)) {
            if ($hecho->hora instanceof \DateTimeInterface) {
                $horaTexto = $hecho->hora->format('H:i:s');
            } elseif (preg_match('/\b(\d{1,2}):(\d{2})(?::(\d{2}))?/', (string) $hecho->hora, $match)) {
                $horaTexto = sprintf(
                    '%02d:%02d:%02d',
                    (int) $match[1],
                    (int) $match[2],
                    isset($match[3]) ? (int) $match[3] : 0
                );
            }
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $fechaTexto . ' ' . $horaTexto, $tz);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function marcarAlertaPorHora(Hechos $hecho, Carbon $now): bool
    {
        $key = 'delegaciones:hecho_incompleto_alertado:' . $hecho->id;

        return Cache::add($key, true, $now->copy()->addMinutes(55));
    }

    private function positiveIntOption(string $name, int $default): int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return max(1, $default);
        }

        return max(1, (int) $value);
    }

    private function formatoDuracion(int $minutos): string
    {
        $horas = intdiv(max(0, $minutos), 60);
        $resto = max(0, $minutos) % 60;

        if ($horas <= 0) {
            return $resto . ' min';
        }

        if ($resto <= 0) {
            return $horas . ' h';
        }

        return $horas . ' h ' . $resto . ' min';
    }
}
