@extends('adminlte::page')

@section('title', 'Reconstructor de Tránsito 2D')

@section('content_header')
    <div class="rt-heading">
        <div>
            <div class="rt-eyebrow"><span></span> Laboratorio de reconstrucción ilustrativa</div>
            <h1>Reconstructor de hechos de tránsito <strong>2D</strong></h1>
            <p>Crea trayectorias, ubica momentos técnicos y reproduce la secuencia del hecho.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-light rt-back">
            <i class="fas fa-arrow-left mr-1"></i> Configuraciones
        </a>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('css/reconstructor-transito.css') }}?v={{ filemtime(public_path('css/reconstructor-transito.css')) }}">
@stop

@section('content')
    <div id="reconstructorTransito" class="rt-app">
        <div class="rt-notice">
            <i class="fas fa-flask"></i>
            <div>
                <strong>Motor físico experimental para reconstrucción 2D.</strong>
                Simula dinámica y entorno para contrastar hipótesis; los resultados requieren validación pericial antes de usarse como conclusión técnica.
            </div>
            <span id="rtSaveStatus" class="rt-save-status"><i class="fas fa-circle"></i> Borrador local</span>
        </div>

        <section class="rt-projectbar">
            <label class="rt-field rt-field--grow">
                <span>Nombre del proyecto</span>
                <input id="rtProjectName" type="text" class="form-control" value="Hecho de tránsito sin título" maxlength="120">
            </label>
            <label class="rt-field">
                <span>Hipótesis</span>
                <input id="rtHypothesis" type="text" class="form-control" value="Hipótesis A" maxlength="80">
            </label>
            <label class="rt-field rt-field--short">
                <span>Duración</span>
                <div class="input-group">
                    <input id="rtDuration" type="number" class="form-control" value="10" min="2" max="120" step="1">
                    <div class="input-group-append"><span class="input-group-text">s</span></div>
                </div>
            </label>
            <label class="rt-field rt-field--short">
                <span>Escala</span>
                <div class="input-group">
                    <input id="rtScale" type="number" class="form-control" value="20" min="2" max="100" step="1">
                    <div class="input-group-append"><span class="input-group-text">px/m</span></div>
                </div>
            </label>
            <label class="rt-physics-switch" title="Calcula movimiento, colisiones, gravedad, vuelcos y agua">
                <input id="rtPhysicsEnabled" type="checkbox" checked>
                <span><i class="fas fa-atom"></i></span>
                <b>Físicas</b>
            </label>
            <div class="rt-file-actions">
                <button id="rtNewProject" type="button" class="btn btn-outline-light" title="Iniciar un proyecto limpio">
                    <i class="fas fa-file"></i><span>Nuevo</span>
                </button>
                <button id="rtSaveProject" type="button" class="btn btn-primary">
                    <i class="fas fa-save"></i><span>Guardar</span>
                </button>
                <button id="rtExportProject" type="button" class="btn btn-outline-info">
                    <i class="fas fa-file-export"></i><span>JSON</span>
                </button>
                <button id="rtExportVideo" type="button" class="btn btn-outline-danger">
                    <i class="fas fa-video"></i><span>Video</span>
                </button>
                <button id="rtFullscreen" type="button" class="btn btn-outline-warning">
                    <i class="fas fa-expand-arrows-alt"></i><span>Pantalla completa</span>
                </button>
                <button id="rtImportProject" type="button" class="btn btn-outline-light">
                    <i class="fas fa-file-import"></i><span>Importar</span>
                </button>
                <input id="rtImportInput" type="file" accept="application/json,.json" hidden>
            </div>
        </section>

        <div class="rt-workspace">
            <aside class="rt-panel rt-library">
                <div class="rt-panel__head">
                    <div><span class="rt-step">1</span><strong>Objetos</strong></div>
                    <small>Agrega participantes</small>
                </div>

                <div class="rt-library__group">
                    <h3>Participantes</h3>
                    <div class="rt-object-grid">
                        <button type="button" class="rt-object" data-add-actor="automovil">
                            <span class="rt-object__preview"><img src="{{ asset('img/croquis/vehiculos/automovil/Sedán_Red.png') }}" alt=""></span>
                            <span>Automóvil</span>
                        </button>
                        <button type="button" class="rt-object" data-add-actor="motocicleta">
                            <span class="rt-object__preview"><img src="{{ asset('img/croquis/vehiculos/motocicleta/DeportivaAc.png') }}" alt=""></span>
                            <span>Motocicleta</span>
                        </button>
                        <button type="button" class="rt-object" data-add-actor="camioneta">
                            <span class="rt-object__preview"><img src="{{ asset('img/croquis/vehiculos/camioneta/pickup.png') }}" alt=""></span>
                            <span>Camioneta</span>
                        </button>
                        <button type="button" class="rt-object" data-add-actor="camion">
                            <span class="rt-object__preview"><img src="{{ asset('img/croquis/vehiculos/camion/Camión.png') }}" alt=""></span>
                            <span>Camión</span>
                        </button>
                        <button type="button" class="rt-object" data-add-actor="bicicleta">
                            <span class="rt-object__preview"><img src="{{ asset('img/croquis/vehiculos/bicicleta/Imagen4.png') }}" alt=""></span>
                            <span>Bicicleta</span>
                        </button>
                        <button type="button" class="rt-object" data-add-actor="peaton">
                            <span class="rt-object__preview"><img src="{{ asset('img/croquis/vehiculos/peatones/peaton_1.png') }}" alt=""></span>
                            <span>Peatón</span>
                        </button>
                    </div>
                </div>

                <div class="rt-library__group">
                    <h3>Puntos técnicos</h3>
                    <div class="rt-tech-grid">
                        <button type="button" data-add-event="PR"><b>PR</b><span>Reacción</span></button>
                        <button type="button" data-add-event="IF"><b>IF</b><span>Frenado</span></button>
                        <button type="button" data-add-event="PE"><b>PE</b><span>Evasión</span></button>
                        <button type="button" data-add-event="PMC"><b>PMC</b><span>Conflicto</span></button>
                        <button type="button" data-add-event="PI"><b>PI</b><span>Impacto</span></button>
                        <button type="button" data-add-event="PF"><b>PF</b><span>Final</span></button>
                    </div>
                    <p class="rt-hint">Selecciona un punto y después ubícalo en la escena.</p>
                </div>

                <div class="rt-library__group">
                    <h3>Escena</h3>
                    <div class="rt-scene-buttons">
                        <button type="button" data-add-road="recta"><i class="fas fa-road"></i> Añadir calle recta</button>
                        <button type="button" data-add-road="curva"><i class="fas fa-bezier-curve"></i> Añadir calle curva</button>
                        <button type="button" data-add-road="puente"><i class="fas fa-archway"></i> Añadir puente</button>
                        <button type="button" data-road-preset="cruce"><i class="fas fa-plus"></i> Crear cruce editable</button>
                        <button type="button" data-add-zone="water"><i class="fas fa-water"></i> Añadir agua</button>
                        <button type="button" data-add-zone="slope"><i class="fas fa-mountain"></i> Añadir talud</button>
                        <button type="button" data-clear-roads><i class="far fa-square"></i> Quitar todas las calles</button>
                    </div>
                </div>

                <div class="rt-library__group rt-layers">
                    <h3>Capas visibles</h3>
                    <label><input type="checkbox" data-layer="road" checked> <span>Geometría vial</span></label>
                    <label><input type="checkbox" data-layer="environment" checked> <span>Agua, taludes y desniveles</span></label>
                    <label><input type="checkbox" data-layer="actors" checked> <span>Participantes</span></label>
                    <label><input type="checkbox" data-layer="paths" checked> <span>Trayectorias</span></label>
                    <label><input type="checkbox" data-layer="events" checked> <span>Puntos técnicos</span></label>
                    <label><input type="checkbox" data-layer="grid" checked> <span>Cuadrícula y escala</span></label>
                </div>
            </aside>

            <main class="rt-stage-column">
                <div class="rt-stage-toolbar">
                    <div class="rt-tool-group">
                        <button type="button" class="rt-tool active" data-tool="select" title="Seleccionar y mover">
                            <i class="fas fa-mouse-pointer"></i><span>Seleccionar</span>
                        </button>
                        <button type="button" class="rt-tool" data-tool="path" title="Agregar fotogramas a la trayectoria">
                            <i class="fas fa-route"></i><span>Trazar ruta</span>
                        </button>
                        <button type="button" class="rt-tool" data-tool="pan" title="Desplazar la cámara">
                            <i class="fas fa-hand-paper"></i><span>Mover vista</span>
                        </button>
                    </div>
                    <div id="rtModeHelp" class="rt-mode-help">
                        <i class="fas fa-info-circle"></i> Selecciona y arrastra participantes, calles o puntos técnicos.
                    </div>
                    <div class="rt-canvas-actions">
                        <button id="rtZoomOut" type="button" title="Alejar"><i class="fas fa-search-minus"></i></button>
                        <span id="rtZoomLabel" class="rt-zoom-label">100%</span>
                        <button id="rtZoomIn" type="button" title="Acercar"><i class="fas fa-search-plus"></i></button>
                        <button id="rtUndo" type="button" title="Deshacer (Ctrl+Z)"><i class="fas fa-undo"></i></button>
                        <button id="rtDelete" type="button" title="Eliminar selección"><i class="fas fa-trash"></i></button>
                        <button id="rtFit" type="button" title="Ajustar toda la escena"><i class="fas fa-expand"></i></button>
                    </div>
                </div>

                <div id="rtCanvasShell" class="rt-canvas-shell">
                    <canvas id="rtCanvas" width="1200" height="700" aria-label="Escena de reconstrucción 2D"></canvas>
                    <div class="rt-canvas-badge rt-canvas-badge--time">
                        <span id="rtCanvasTime">00:00.0</span>
                        <small id="rtPlaybackBadge">PAUSA</small>
                    </div>
                    <div id="rtPlacementHint" class="rt-placement-hint" hidden></div>
                    <div class="rt-coordinate"><span id="rtCoordinate">x: — · y: —</span></div>
                    <div id="rtEmptyState" class="rt-empty-state" hidden>
                        <i class="fas fa-route"></i>
                        <strong>Comienza agregando un participante</strong>
                        <span>Después selecciona “Trazar ruta” para marcar su recorrido.</span>
                    </div>
                </div>

                <section class="rt-timeline">
                    <div class="rt-playback">
                        <button id="rtReset" type="button" title="Volver al inicio"><i class="fas fa-step-backward"></i></button>
                        <button id="rtPrevFrame" type="button" title="Retroceder cuadro"><i class="fas fa-backward"></i></button>
                        <button id="rtPlay" type="button" class="rt-play" title="Reproducir"><i class="fas fa-play"></i></button>
                        <button id="rtNextFrame" type="button" title="Avanzar cuadro"><i class="fas fa-forward"></i></button>
                        <label class="rt-speed">
                            <span>Velocidad</span>
                            <select id="rtPlaybackSpeed" class="form-control form-control-sm">
                                <option value="0.25">0.25×</option>
                                <option value="0.5">0.5×</option>
                                <option value="1" selected>1×</option>
                                <option value="2">2×</option>
                            </select>
                        </label>
                    </div>
                    <div class="rt-scrubber">
                        <div class="rt-time-labels"><span>00:00</span><span id="rtDurationLabel">00:10</span></div>
                        <div class="rt-track-wrap">
                            <div id="rtEventTicks" class="rt-event-ticks"></div>
                            <input id="rtTimeline" type="range" min="0" max="10" value="0" step="0.01">
                        </div>
                        <div class="rt-current-time"><input id="rtCurrentTime" type="number" min="0" max="10" value="0" step="0.1"><span>s</span></div>
                    </div>
                </section>
            </main>

            <aside class="rt-panel rt-inspector">
                <div class="rt-panel__head">
                    <div><span class="rt-step">2</span><strong>Propiedades</strong></div>
                    <small>Edita la selección</small>
                </div>

                <div id="rtNoSelection" class="rt-no-selection">
                    <i class="fas fa-hand-pointer"></i>
                    <strong>Sin selección</strong>
                    <span>Haz clic sobre un participante, calle o punto técnico.</span>
                </div>

                <div id="rtActorInspector" class="rt-inspector-section" hidden>
                    <div class="rt-selection-title">
                        <span id="rtActorSwatch"></span>
                        <div><small>Participante seleccionado</small><strong id="rtActorTitle">Vehículo 1</strong></div>
                    </div>
                    <label class="rt-field"><span>Nombre</span><input id="rtActorName" type="text" class="form-control form-control-sm" maxlength="80"></label>
                    <div class="rt-field-row">
                        <label class="rt-field"><span>Color de ruta</span><input id="rtActorColor" type="color" class="form-control form-control-sm"></label>
                        <label class="rt-field"><span>Velocidad inicial</span><div class="input-group input-group-sm"><input id="rtActorSpeed" type="number" class="form-control" min="0" max="300" step="1"><div class="input-group-append"><span class="input-group-text">km/h</span></div></div></label>
                    </div>
                    <div class="rt-physics-card">
                        <strong><i class="fas fa-atom"></i> Comportamiento físico</strong>
                        <div class="rt-field-row">
                            <label class="rt-field"><span>Masa</span><div class="input-group input-group-sm"><input id="rtActorMass" type="number" class="form-control" min="40" max="50000" step="50"><div class="input-group-append"><span class="input-group-text">kg</span></div></div></label>
                            <label class="rt-field"><span>Altura C.G.</span><div class="input-group input-group-sm"><input id="rtActorCgHeight" type="number" class="form-control" min="0.2" max="3" step="0.1"><div class="input-group-append"><span class="input-group-text">m</span></div></div></label>
                        </div>
                        <label class="rt-field"><span>Agarre de neumáticos</span><input id="rtActorGrip" type="range" min="0.15" max="1.3" step="0.05"></label>
                        <small>La trayectoria actúa como intención del conductor; la física limita cuánto puede acelerar, girar y recuperar el control.</small>
                    </div>
                    <div class="rt-field-row">
                        <label class="rt-field"><span>Largo</span><div class="input-group input-group-sm"><input id="rtActorLength" type="number" class="form-control" min="40" max="400" step="5"><div class="input-group-append"><span class="input-group-text">%</span></div></div></label>
                        <label class="rt-field"><span>Ancho</span><div class="input-group input-group-sm"><input id="rtActorWidth" type="number" class="form-control" min="40" max="400" step="5"><div class="input-group-append"><span class="input-group-text">%</span></div></div></label>
                    </div>
                    <div class="rt-rotation-control">
                        <div class="rt-rotation-control__head">
                            <span>Orientación en el tiempo actual</span>
                            <div class="input-group input-group-sm">
                                <input id="rtActorRotation" type="number" class="form-control" min="-180" max="180" step="1">
                                <div class="input-group-append"><span class="input-group-text">°</span></div>
                            </div>
                        </div>
                        <div class="rt-rotation-control__body">
                            <button type="button" data-rotate-actor="-15" title="Girar 15° a la izquierda"><i class="fas fa-undo"></i> 15°</button>
                            <input id="rtActorRotationRange" type="range" min="-180" max="180" step="1" value="0">
                            <button type="button" data-rotate-actor="15" title="Girar 15° a la derecha">15° <i class="fas fa-redo"></i></button>
                        </div>
                        <small>Usa los cuadros laterales para alargar o ensanchar; las esquinas ajustan ambas dimensiones. El círculo sirve para girar.</small>
                    </div>
                    <button id="rtStartPath" type="button" class="btn btn-sm btn-block btn-outline-info"><i class="fas fa-route mr-1"></i> Trazar trayectoria</button>
                    <small class="d-block mt-2 text-muted">También puedes hacer clic sobre una trayectoria existente y arrastrar sus puntos numerados para corregirla.</small>
                    <div class="rt-subhead"><span>Fotogramas clave</span><button id="rtAddKeyframe" type="button"><i class="fas fa-plus"></i> En tiempo actual</button></div>
                    <div id="rtKeyframeList" class="rt-keyframes"></div>
                </div>

                <div id="rtRoadInspector" class="rt-inspector-section" hidden>
                    <div class="rt-selection-title rt-selection-title--road">
                        <span><i class="fas fa-road"></i></span>
                        <div><small>Calle seleccionada</small><strong id="rtRoadTitle">Calle recta</strong></div>
                    </div>
                    <label class="rt-field"><span>Nombre</span><input id="rtRoadName" type="text" class="form-control form-control-sm" maxlength="80"></label>
                    <label class="rt-field">
                        <span>Superficie de rodamiento</span>
                        <div class="rt-surface-control">
                            <span id="rtSurfacePreview" class="rt-surface-preview" data-surface="asphalt"></span>
                            <select id="rtRoadSurface" class="form-control form-control-sm">
                                <option value="asphalt">Asfalto</option>
                                <option value="concrete">Concreto hidráulico</option>
                                <option value="pavers">Adoquín</option>
                                <option value="cobblestone">Empedrado</option>
                                <option value="dirt">Terracería</option>
                                <option value="gravel">Grava</option>
                                <option value="natural">Brecha / suelo natural</option>
                            </select>
                        </div>
                    </label>
                    <div class="rt-road-lanes">
                        <span id="rtRoadLanesLabel">Carriles</span>
                        <div>
                            <button id="rtRemoveLane" type="button" title="Quitar un carril"><i class="fas fa-minus"></i></button>
                            <strong id="rtRoadLaneCount">2</strong>
                            <button id="rtAddLane" type="button" title="Añadir un carril"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="rt-field-row rt-field-row--road">
                        <label class="rt-field">
                            <span>Circulación</span>
                            <select id="rtRoadDirection" class="form-control form-control-sm">
                                <option value="one_way">Un solo sentido</option>
                                <option value="two_way">Doble sentido</option>
                            </select>
                        </label>
                        <label id="rtCenterLineField" class="rt-field">
                            <span>División central</span>
                            <select id="rtRoadCenterLine" class="form-control form-control-sm">
                                <option value="solid">Amarilla continua</option>
                                <option value="dashed">Amarilla discontinua</option>
                                <option value="double_solid">Doble amarilla continua</option>
                            </select>
                        </label>
                    </div>
                    <div class="rt-field-row rt-field-row--road">
                        <label id="rtRoadLengthField" class="rt-field"><span>Longitud</span><div class="input-group input-group-sm"><input id="rtRoadLength" type="number" class="form-control" min="3" max="200" step="1"><div class="input-group-append"><span class="input-group-text">m</span></div></div></label>
                        <label class="rt-field"><span>Ancho de carril</span><div class="input-group input-group-sm"><input id="rtRoadLaneWidth" type="number" class="form-control" min="2" max="8" step="0.1"><div class="input-group-append"><span class="input-group-text">m</span></div></div></label>
                    </div>
                    <div id="rtCurveHelp" class="rt-curve-help" hidden>
                        <div><i class="fas fa-bezier-curve"></i><span>Arrastra los cuatro nodos sobre la curva: extremos morados y controles verdes.</span></div>
                        <button id="rtResetCurve" type="button"><i class="fas fa-undo"></i> Restablecer forma</button>
                    </div>
                    <label class="rt-field"><span>Orientación</span><div class="input-group input-group-sm"><input id="rtRoadRotation" type="number" class="form-control" min="-180" max="180" step="1"><div class="input-group-append"><span class="input-group-text">°</span></div></div></label>
                    <div class="rt-road-sides">
                        <strong>Elementos laterales</strong>
                        <div class="rt-field-row rt-field-row--road">
                            <label class="rt-field">
                                <span>Costado izquierdo</span>
                                <select id="rtRoadLeftEdge" class="form-control form-control-sm">
                                    <option value="none">Sin elemento</option>
                                    <option value="sidewalk">Banqueta</option>
                                    <option value="median">Camellón</option>
                                </select>
                            </label>
                            <label class="rt-field">
                                <span>Costado derecho</span>
                                <select id="rtRoadRightEdge" class="form-control form-control-sm">
                                    <option value="none">Sin elemento</option>
                                    <option value="sidewalk">Banqueta</option>
                                    <option value="median">Camellón</option>
                                </select>
                            </label>
                        </div>
                    </div>
                    <div class="rt-physics-card">
                        <label class="rt-check"><input id="rtRoadBridge" type="checkbox"> <span>Este tramo es un puente elevado</span></label>
                        <label id="rtRoadElevationField" class="rt-field"><span>Altura del tablero</span><div class="input-group input-group-sm"><input id="rtRoadElevation" type="number" class="form-control" min="0.5" max="60" step="0.5"><div class="input-group-append"><span class="input-group-text">m</span></div></div></label>
                        <small>Al abandonar el tablero, el vehículo queda en caída libre hasta el terreno o el agua.</small>
                    </div>
                    <p class="rt-hint">Arrastra la calle para moverla. Las líneas punteadas se distribuyen según los carriles.</p>
                </div>

                <div id="rtZoneInspector" class="rt-inspector-section" hidden>
                    <div class="rt-selection-title rt-selection-title--zone">
                        <span><i class="fas fa-water"></i></span>
                        <div><small>Entorno seleccionado</small><strong id="rtZoneTitle">Cuerpo de agua</strong></div>
                    </div>
                    <label class="rt-field"><span>Nombre</span><input id="rtZoneName" type="text" class="form-control form-control-sm" maxlength="80"></label>
                    <div class="rt-field-row">
                        <label class="rt-field"><span>Ancho</span><div class="input-group input-group-sm"><input id="rtZoneWidth" type="number" class="form-control" min="2" max="200" step="1"><div class="input-group-append"><span class="input-group-text">m</span></div></div></label>
                        <label class="rt-field"><span>Alto</span><div class="input-group input-group-sm"><input id="rtZoneHeight" type="number" class="form-control" min="2" max="200" step="1"><div class="input-group-append"><span class="input-group-text">m</span></div></div></label>
                    </div>
                    <label class="rt-field"><span id="rtZoneDepthLabel">Profundidad</span><div class="input-group input-group-sm"><input id="rtZoneDepth" type="number" class="form-control" min="0.5" max="100" step="0.5"><div class="input-group-append"><span class="input-group-text">m</span></div></div></label>
                    <p class="rt-hint">Arrastra el área para moverla. Agua frena y hunde al vehículo; un talud altera el apoyo y puede provocar un vuelco.</p>
                </div>

                <div id="rtEventInspector" class="rt-inspector-section" hidden>
                    <div class="rt-selection-title rt-selection-title--event">
                        <span id="rtEventCode">PI</span>
                        <div><small>Punto técnico seleccionado</small><strong id="rtEventTitle">Punto de impacto</strong></div>
                    </div>
                    <label class="rt-field"><span>Momento</span><div class="input-group input-group-sm"><input id="rtEventTime" type="number" class="form-control" min="0" step="0.1"><div class="input-group-append"><span class="input-group-text">s</span></div></div></label>
                    <label class="rt-field"><span>Descripción</span><textarea id="rtEventDescription" class="form-control form-control-sm" rows="3" maxlength="240"></textarea></label>
                </div>

                <div class="rt-actors-section">
                    <div class="rt-subhead"><span>Participantes</span><small id="rtActorCount">0</small></div>
                    <div id="rtActorList" class="rt-actor-list"></div>
                </div>

                <div class="rt-summary">
                    <div><span>Estado físico</span><strong id="rtPhysicsStatus">LISTO</strong></div>
                    <div><span>Distancia seleccionada</span><strong id="rtDistance">—</strong></div>
                    <div><span>Fotogramas</span><strong id="rtKeyframeCount">0</strong></div>
                    <div><span>Puntos técnicos</span><strong id="rtEventCount">0</strong></div>
                </div>
            </aside>
        </div>
    </div>
@stop

@section('js')
    <script>
        window.ReconstructorTransitoConfig = {
            storageKey: 'sistemaEstadistico.reconstructorTransito.v1',
            actorImages: {
                automovil: @json(asset('img/croquis/vehiculos/automovil/Sedán_Red.png')),
                motocicleta: @json(asset('img/croquis/vehiculos/motocicleta/DeportivaAc.png')),
                camioneta: @json(asset('img/croquis/vehiculos/camioneta/pickup.png')),
                camion: @json(asset('img/croquis/vehiculos/camion/Camión.png')),
                bicicleta: @json(asset('img/croquis/vehiculos/bicicleta/Imagen4.png')),
                peaton: @json(asset('img/croquis/vehiculos/peatones/peaton_1.png'))
            }
        };
    </script>
    <script src="{{ asset('js/reconstructor-transito.js') }}?v={{ filemtime(public_path('js/reconstructor-transito.js')) }}"></script>
@stop
