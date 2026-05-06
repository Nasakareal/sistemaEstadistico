<div class="ppt-card svial-map-card">
    <div class="ppt-card__header svial-card-header svial-map-header">
        <div>
            <div class="ppt-eyebrow">Lectura territorial</div>
            <h2 class="ppt-title">Mapa de calor Morelia</h2>
            <div class="svial-period" id="svialHeatPeriod">
                {{ $reporte['periodo']['texto'] ?? 'Periodo seleccionado' }}
            </div>
            <div class="svial-map-scope">Morelia con coordenadas capturadas</div>
        </div>

        <div class="svial-map-badges">
            <div class="svial-map-badge svial-map-badge--fatal">
                <span id="svialHeatFatal">0</span>
                fallecidos en zonas
            </div>
            <div class="svial-map-badge svial-map-badge--injured">
                <span id="svialHeatInjured">0</span>
                lesionados en zonas
            </div>
            <div class="svial-map-badge svial-map-badge--crash">
                <span id="svialHeatCrashes">0</span>
                sin víctimas en zonas
            </div>
        </div>
    </div>

    <div class="svial-heat-grid">
        <div class="svial-heat-map-wrap">
            <div id="svialHeatMap" class="svial-heat-map"></div>
        </div>

        <aside class="svial-heat-panel">
            <div class="svial-panel-kicker">QGIS visual</div>
            <h3>Zonas con más siniestros</h3>

            <div class="svial-heat-controls">
                <label class="svial-layer-option svial-layer-option--fatal">
                    <input type="checkbox" class="svial-layer-toggle" data-layer="fallecidos" checked>
                    <span>Fallecidos</span>
                </label>

                <label class="svial-layer-option svial-layer-option--injured">
                    <input type="checkbox" class="svial-layer-toggle" data-layer="lesionados" checked>
                    <span>Lesionados</span>
                </label>

                <label class="svial-layer-option svial-layer-option--crash">
                    <input type="checkbox" class="svial-layer-toggle" data-layer="choques" checked>
                    <span>Choques sin víctimas</span>
                </label>
            </div>

            <label class="svial-heat-field">
                <span>Precisión</span>
                <select id="svialHeatPrecision">
                    <option value="2" selected>Zonas conflictivas</option>
                    <option value="3">Colonias / corredores</option>
                    <option value="4">Cruces exactos</option>
                    <option value="5">Punto exacto</option>
                </select>
            </label>

            <button type="button" class="svial-heat-refresh" id="svialHeatRefresh">
                <i class="fa-solid fa-rotate-right"></i>
                <span>Actualizar mapa</span>
            </button>

            <div class="svial-heat-metrics">
                <div>
                    <span>Zonas</span>
                    <strong id="svialHeatPoints">0</strong>
                </div>
                <div>
                    <span>En zonas</span>
                    <strong id="svialHeatTotal">0</strong>
                </div>
            </div>

            <div class="svial-heat-status" id="svialHeatStatus">Cargando mapa...</div>
        </aside>
    </div>
</div>
