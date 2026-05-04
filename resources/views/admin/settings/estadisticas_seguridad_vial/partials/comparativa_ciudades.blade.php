<div class="ppt-card svial-compare-card">
    <div class="ppt-card__header svial-card-header">
        <div>
            <div class="ppt-eyebrow">Primera hoja de análisis</div>
            <h2 class="ppt-title">Comparativa de siniestros por municipio</h2>
            <div class="svial-period" id="svialComparePeriod">
                {{ $reporte['periodo']['texto'] ?? 'Periodo seleccionado' }}
            </div>
        </div>

        <div class="svial-chip">
            <i class="fa-solid fa-map-location-dot"></i>
            <span id="svialMunicipiosCount">{{ $reporte['kpis']['municipios_con_hechos'] ?? 0 }}</span>
            municipios
        </div>
    </div>

    <div class="svial-compare-grid">
        <div class="svial-chart-panel">
            <div id="chart_municipios" class="svial-chart"></div>
        </div>

        <div class="svial-ranking-panel">
            <div class="svial-ranking-head">
                <div>
                    <div class="svial-panel-kicker">Ranking</div>
                    <h3>Municipios con mayor incidencia</h3>
                </div>
                <div class="svial-total-pill">
                    <span id="svialTotalHechosCompare">{{ $reporte['kpis']['total_hechos'] ?? 0 }}</span>
                    hechos
                </div>
            </div>

            <div class="svial-ranking-list" id="svialRankingMunicipios">
                <div class="svial-empty">Cargando comparativa municipal...</div>
            </div>
        </div>
    </div>
</div>
