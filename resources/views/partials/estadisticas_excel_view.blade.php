@php
    $excelMode = $excelMode ?? 'actividades';
    $excelViewId = 'sv_excel_view_' . $excelMode;
@endphp

<section
    id="{{ $excelViewId }}"
    class="sv-excel-view d-none"
    data-excel-mode="{{ $excelMode }}"
    aria-label="Vista Excel de la hoja Total"
>
    <div class="sv-excel-app">
        <header class="sv-excel-titlebar">
            <div class="sv-excel-titlebar__brand">
                <span class="sv-excel-logo" aria-hidden="true">X</span>
                <span>Estadísticas de Seguridad Vial</span>
            </div>
            <div class="sv-excel-titlebar__file">Vista protegida · Hoja TOTAL</div>
            <button class="sv-excel-window-button" type="button" data-excel-action="close" title="Volver al tablero" aria-label="Volver al tablero">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>

        <nav class="sv-excel-tabs" aria-label="Cinta de opciones">
            <button type="button" class="sv-excel-tab sv-excel-tab--file">Archivo</button>
            <button type="button" class="sv-excel-tab sv-excel-tab--active">Inicio</button>
            <button type="button" class="sv-excel-tab">Insertar</button>
            <button type="button" class="sv-excel-tab">Diseño de página</button>
            <button type="button" class="sv-excel-tab">Fórmulas</button>
            <button type="button" class="sv-excel-tab">Datos</button>
            <button type="button" class="sv-excel-tab">Revisar</button>
            <button type="button" class="sv-excel-tab">Vista</button>
        </nav>

        <div class="sv-excel-ribbon">
            <div class="sv-excel-ribbon__group sv-excel-ribbon__group--actions">
                <button type="button" class="sv-excel-ribbon-button" data-excel-action="close">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span>Tablero</span>
                </button>
                <button type="button" class="sv-excel-ribbon-button" data-excel-action="refresh">
                    <i class="fas fa-sync-alt" aria-hidden="true"></i>
                    <span>Actualizar</span>
                </button>
                @if ($excelMode === 'actividades')
                    <button type="button" class="sv-excel-ribbon-button" data-excel-action="download">
                        <i class="fas fa-file-excel" aria-hidden="true"></i>
                        <span>Descargar</span>
                    </button>
                @endif
                <button type="button" class="sv-excel-ribbon-button" data-excel-action="print">
                    <i class="fas fa-print" aria-hidden="true"></i>
                    <span>Imprimir</span>
                </button>
            </div>
            <div class="sv-excel-ribbon__group">
                <div class="sv-excel-clipboard" aria-hidden="true">
                    <i class="far fa-clipboard"></i>
                </div>
                <div class="sv-excel-ribbon-lines">
                    <span><b>N</b> <em>K</em> <u>S</u></span>
                    <span>Calibri &nbsp; 11</span>
                </div>
                <small>Fuente</small>
            </div>
            <div class="sv-excel-ribbon__group sv-excel-ribbon__group--format" aria-hidden="true">
                <span><i class="fas fa-align-left"></i></span>
                <span><i class="fas fa-align-center"></i></span>
                <span><i class="fas fa-align-right"></i></span>
                <span><i class="fas fa-border-all"></i></span>
                <small>Alineación</small>
            </div>
            <div class="sv-excel-ribbon__context">
                <span class="sv-excel-ribbon__context-label">Mostrando</span>
                <strong data-excel-period>Periodo seleccionado</strong>
            </div>
        </div>

        <div class="sv-excel-formula">
            <div class="sv-excel-name-box" data-excel-name-box>C1</div>
            <div class="sv-excel-fx" aria-hidden="true">fx</div>
            <div class="sv-excel-formula-input" data-excel-formula aria-live="polite"></div>
        </div>

        <div class="sv-excel-sheet-wrap">
            <div class="sv-excel-loading" data-excel-loading>
                <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
                <span>Preparando hoja TOTAL…</span>
            </div>
            <div class="sv-excel-sheet" data-excel-sheet tabindex="0"></div>
        </div>

        <footer class="sv-excel-statusbar">
            <div class="sv-excel-sheet-tabs">
                <button type="button" class="sv-excel-sheet-nav" aria-label="Navegar hojas"><i class="fas fa-chevron-left"></i></button>
                <button type="button" class="sv-excel-sheet-tab sv-excel-sheet-tab--active">TOTAL</button>
                <span class="sv-excel-add-sheet" aria-hidden="true"><i class="fas fa-plus-circle"></i></span>
            </div>
            <div class="sv-excel-status">Listo</div>
            <div class="sv-excel-zoom">
                <button type="button" data-excel-action="zoom-out" aria-label="Alejar">−</button>
                <input type="range" min="70" max="120" value="90" step="5" data-excel-zoom aria-label="Zoom de la hoja">
                <button type="button" data-excel-action="zoom-in" aria-label="Acercar">+</button>
                <span data-excel-zoom-label>90%</span>
            </div>
        </footer>
    </div>
</section>
