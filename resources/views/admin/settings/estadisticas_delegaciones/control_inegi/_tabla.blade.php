<div class="table-responsive">
    <table class="table table-hover inegi-table mb-0">
        <thead>
            <tr>
                <th>Hecho</th>
                <th>Fecha y hora</th>
                <th>Regional / Delegación</th>
                <th>Tipo y ubicación</th>
                <th>Captura</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $registro)
                @php
                    $faltantes = [];
                    foreach (['vehiculos' => 'vehículo(s)', 'conductores' => 'conductor(es)', 'lesionados' => 'lesionado(s)'] as $campo => $label) {
                        $esperados = (int) ($registro->{$campo . '_esperados'} ?? 0);
                        $capturados = (int) ($registro->{$campo . '_capturados'} ?? 0);
                        if ($capturados < $esperados) {
                            $faltantes[] = ($esperados - $capturados) . ' ' . $label . ' (' . $capturados . '/' . $esperados . ')';
                        }
                    }
                @endphp
                <tr>
                    <td class="text-nowrap">
                        @can('ver hechos')
                            <a href="{{ route('hechos.show', $registro->id) }}" class="inegi-link">
                                #{{ $registro->id }}
                            </a>
                        @else
                            <strong>#{{ $registro->id }}</strong>
                        @endcan
                        <div class="inegi-muted">{{ $registro->folio_c5i ?: 'Sin folio C5i' }}</div>
                    </td>
                    <td class="text-nowrap">
                        <strong>{{ \Carbon\Carbon::parse($registro->fecha)->format('d/m/Y') }}</strong>
                        <div class="inegi-muted">{{ $registro->hora ? substr((string) $registro->hora, 0, 5) . ' h' : 'Sin hora' }}</div>
                    </td>
                    <td>
                        <strong>{{ $registro->regional_nombre }}</strong>
                        <div class="inegi-muted">{{ $registro->delegacion_nombre }}</div>
                    </td>
                    <td>
                        <strong>{{ $registro->tipo_hecho ?: 'Sin tipo de hecho' }}</strong>
                        <div class="inegi-muted">
                            {{ collect([$registro->calle, $registro->colonia, $registro->municipio])->filter()->implode(' · ') ?: 'Sin ubicación' }}
                        </div>
                    </td>
                    <td style="min-width: 210px">
                        @if($mostrarFaltantes && count($faltantes))
                            <span class="badge badge-warning">No entra todavía</span>
                            <div class="inegi-missing">Falta: {{ implode(', ', $faltantes) }}</div>
                        @else
                            <span class="badge badge-success">Contemplado</span>
                            <div class="inegi-muted">
                                Veh. {{ (int) $registro->vehiculos_capturados }}/{{ (int) $registro->vehiculos_esperados }} ·
                                Cond. {{ (int) $registro->conductores_capturados }}/{{ (int) $registro->conductores_esperados }} ·
                                Les. {{ (int) $registro->lesionados_capturados }}/{{ (int) $registro->lesionados_esperados }}
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <i class="fas fa-inbox fa-2x mb-2 d-block inegi-muted"></i>
                        No hay hechos en esta sección con los filtros actuales.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($registros->hasPages())
    <div class="inegi-pagination">
        {{ $registros->links() }}
    </div>
@endif
