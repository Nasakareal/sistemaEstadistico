(function () {
    'use strict';

    const root = document.getElementById('reconstructorTransito');
    if (!root) return;

    const config = window.ReconstructorTransitoConfig || {};
    const storageKey = config.storageKey || 'reconstructorTransito.v1';
    const canvas = document.getElementById('rtCanvas');
    const ctx = canvas.getContext('2d');

    const EVENT_META = {
        PR: { label: 'Punto de reacción', color: '#38bdf8' },
        IF: { label: 'Inicio de frenado', color: '#f59e0b' },
        PE: { label: 'Punto de evasión', color: '#a78bfa' },
        PMC: { label: 'Punto máximo de conflicto', color: '#fb4d63' },
        PI: { label: 'Punto de impacto', color: '#ef233c' },
        PF: { label: 'Posición final', color: '#22c58b' }
    };

    const ACTOR_META = {
        automovil: { label: 'Automóvil', color: '#ef4444', width: 72, height: 38 },
        motocicleta: { label: 'Motocicleta', color: '#38bdf8', width: 58, height: 32 },
        camioneta: { label: 'Camioneta', color: '#f59e0b', width: 78, height: 42 },
        camion: { label: 'Camión', color: '#a78bfa', width: 94, height: 43 },
        bicicleta: { label: 'Bicicleta', color: '#22c58b', width: 48, height: 30 },
        peaton: { label: 'Peatón', color: '#f97316', width: 30, height: 30 }
    };

    const el = {};
    [
        'rtProjectName', 'rtHypothesis', 'rtDuration', 'rtScale', 'rtSaveStatus',
        'rtNewProject', 'rtSaveProject', 'rtExportProject', 'rtExportVideo',
        'rtFullscreen', 'rtImportProject', 'rtImportInput', 'rtCanvasTime', 'rtPlaybackBadge',
        'rtPlacementHint', 'rtCoordinate', 'rtEmptyState', 'rtModeHelp', 'rtUndo',
        'rtDelete', 'rtFit', 'rtZoomOut', 'rtZoomLabel', 'rtZoomIn',
        'rtReset', 'rtPrevFrame', 'rtPlay', 'rtNextFrame',
        'rtPlaybackSpeed', 'rtTimeline', 'rtCurrentTime', 'rtDurationLabel',
        'rtEventTicks', 'rtNoSelection', 'rtActorInspector', 'rtEventInspector',
        'rtActorSwatch', 'rtActorTitle', 'rtActorName', 'rtActorColor', 'rtActorSpeed',
        'rtActorLength', 'rtActorWidth',
        'rtActorRotation', 'rtActorRotationRange',
        'rtStartPath', 'rtAddKeyframe', 'rtKeyframeList', 'rtEventCode', 'rtEventTitle',
        'rtEventTime', 'rtEventDescription', 'rtActorCount', 'rtActorList',
        'rtRoadInspector', 'rtRoadTitle', 'rtRoadName', 'rtRoadSurface',
        'rtSurfacePreview', 'rtRoadLaneCount',
        'rtRoadLanesLabel', 'rtRemoveLane', 'rtAddLane', 'rtRoadDirection',
        'rtCenterLineField', 'rtRoadCenterLine', 'rtRoadLength', 'rtRoadLaneWidth',
        'rtRoadLengthField', 'rtCurveHelp', 'rtResetCurve', 'rtRoadRotation',
        'rtRoadLeftEdge', 'rtRoadRightEdge',
        'rtDistance', 'rtKeyframeCount', 'rtEventCount'
    ].forEach(function (id) { el[id] = document.getElementById(id); });

    const imageCache = {};
    const surfacePatternCache = {};
    const history = [];
    let project = loadProject() || demoProject();
    let currentTime = 0;
    let selected = null;
    let tool = 'select';
    let pendingEvent = null;
    let dragging = null;
    let playing = false;
    let playbackRate = 1;
    let lastFrameAt = 0;
    let animationId = null;
    let dirtyTimer = null;
    let recording = null;
    let recordingCameraBackup = null;
    let fullscreenFallback = false;
    const camera = { zoom: 1, panX: 0, panY: 0 };

    function uid(prefix) {
        return prefix + '_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 7);
    }

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, Number(value) || 0));
    }

    function normalizeAngle(value) {
        const angle = Number(value) || 0;
        return ((angle + 180) % 360 + 360) % 360 - 180;
    }

    function actorScale(actor, axis) {
        const legacyScale = Number(actor && actor.scale) || 1;
        const value = axis === 'x' ? actor && actor.scaleX : actor && actor.scaleY;
        return clamp(value || legacyScale, .4, 4);
    }

    function deepClone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function demoProject() {
        return {
            version: 1,
            metadata: {
                name: 'Ejemplo · Cruce con punto de conflicto',
                hypothesis: 'Hipótesis A',
                duration: 10,
                pixelsPerMeter: 20,
                mode: 'illustrative'
            },
            scene: {
                roads: [
                    { id: uid('road'), type: 'straight', name: 'Avenida principal', x: 600, y: 420, lengthMeters: 60, laneWidthMeters: 3.5, lanes: 2, rotation: 0, direction: 'two_way', centerLine: 'solid', leftEdge: 'sidewalk', rightEdge: 'sidewalk', surface: 'asphalt' },
                    { id: uid('road'), type: 'straight', name: 'Calle transversal', x: 650, y: 350, lengthMeters: 38, laneWidthMeters: 3.5, lanes: 2, rotation: 90, direction: 'two_way', centerLine: 'dashed', leftEdge: 'none', rightEdge: 'none', surface: 'concrete' }
                ]
            },
            layers: { road: true, actors: true, paths: true, events: true, grid: true },
            actors: [
                {
                    id: uid('actor'), type: 'automovil', name: 'Vehículo 1',
                    image: (config.actorImages || {}).automovil || '', color: '#ef4444', speedKmh: 48,
                    keyframes: [
                        { time: 0, x: 120, y: 420, rotation: 0 },
                        { time: 2.4, x: 360, y: 420, rotation: 0 },
                        { time: 4.4, x: 570, y: 420, rotation: 0 },
                        { time: 5, x: 650, y: 410, rotation: -8 },
                        { time: 7.4, x: 850, y: 340, rotation: -28 }
                    ]
                },
                {
                    id: uid('actor'), type: 'motocicleta', name: 'Vehículo 2',
                    image: (config.actorImages || {}).motocicleta || '', color: '#38bdf8', speedKmh: 38,
                    keyframes: [
                        { time: 0, x: 650, y: 650, rotation: -90 },
                        { time: 3.2, x: 650, y: 525, rotation: -90 },
                        { time: 5, x: 650, y: 410, rotation: -90 },
                        { time: 6.8, x: 705, y: 310, rotation: -48 },
                        { time: 8.5, x: 775, y: 270, rotation: -18 }
                    ]
                }
            ],
            events: [
                { id: uid('event'), code: 'PR', x: 360, y: 420, time: 2.4, description: 'El conductor identifica el riesgo.' },
                { id: uid('event'), code: 'IF', x: 470, y: 420, time: 3.4, description: 'Inicio estimado de frenado.' },
                { id: uid('event'), code: 'PMC', x: 650, y: 410, time: 4.8, description: 'Convergencia máxima de trayectorias.' },
                { id: uid('event'), code: 'PI', x: 650, y: 410, time: 5, description: 'Punto de impacto ilustrativo.' },
                { id: uid('event'), code: 'PF', x: 850, y: 340, time: 7.4, description: 'Posición final del vehículo 1.' }
            ]
        };
    }

    function blankProject() {
        return {
            version: 1,
            metadata: { name: 'Hecho de tránsito sin título', hypothesis: 'Hipótesis A', duration: 10, pixelsPerMeter: 20, mode: 'illustrative' },
            scene: { roads: [] },
            layers: { road: true, actors: true, paths: true, events: true, grid: true },
            actors: [],
            events: []
        };
    }

    function normalizeProject(raw) {
        if (!raw || typeof raw !== 'object') throw new Error('El archivo no contiene un proyecto válido.');
        const normalized = blankProject();
        normalized.metadata = Object.assign(normalized.metadata, raw.metadata || {});
        normalized.metadata.duration = clamp(normalized.metadata.duration, 2, 120);
        normalized.metadata.pixelsPerMeter = clamp(normalized.metadata.pixelsPerMeter, 2, 100);
        const rawScene = raw.scene || {};
        const rawRoads = Array.isArray(rawScene.roads)
            ? rawScene.roads
            : legacyRoads(rawScene.template, normalized.metadata.pixelsPerMeter);
        normalized.scene = {
            roads: rawRoads.map(function (road, index) {
                return {
                    id: road.id || uid('road'),
                    type: road.type === 'curve' ? 'curve' : 'straight',
                    name: String(road.name || ('Calle ' + (index + 1))).slice(0, 80),
                    x: clamp(road.x, -10000, 10000),
                    y: clamp(road.y, -10000, 10000),
                    lengthMeters: clamp(road.lengthMeters, 3, 200),
                    laneWidthMeters: clamp(road.laneWidthMeters || 3.5, 2, 8),
                    lanes: Math.round(clamp(road.lanes || 2, 1, 12)),
                    rotation: normalizeAngle(road.rotation),
                    direction: road.direction === 'two_way' ? 'two_way' : 'one_way',
                    centerLine: ['solid', 'dashed', 'double_solid'].includes(road.centerLine) ? road.centerLine : 'solid',
                    leftEdge: ['none', 'sidewalk', 'median'].includes(road.leftEdge) ? road.leftEdge : 'none',
                    rightEdge: ['none', 'sidewalk', 'median'].includes(road.rightEdge) ? road.rightEdge : 'none',
                    surface: ['asphalt', 'concrete', 'pavers', 'cobblestone', 'dirt', 'gravel', 'natural'].includes(road.surface) ? road.surface : 'asphalt',
                    curve: normalizeCurve(road.curve)
                };
            }).map(function (road) {
                if (road.direction === 'two_way') {
                    road.lanes = Math.max(2, road.lanes + (road.lanes % 2));
                }
                return road;
            })
        };
        normalized.layers = Object.assign(normalized.layers, raw.layers || {});
        normalized.actors = Array.isArray(raw.actors) ? raw.actors.map(function (actor, index) {
            const type = ACTOR_META[actor.type] ? actor.type : 'automovil';
            const meta = ACTOR_META[type];
            const frames = Array.isArray(actor.keyframes) ? actor.keyframes.map(function (frame) {
                return {
                    time: clamp(frame.time, 0, normalized.metadata.duration),
                    x: clamp(frame.x, -10000, 10000),
                    y: clamp(frame.y, -10000, 10000),
                    rotation: Number(frame.rotation) || 0,
                    rotationManual: Boolean(frame.rotationManual)
                };
            }).sort(function (a, b) { return a.time - b.time; }) : [];
            return {
                id: actor.id || uid('actor'), type: type,
                name: String(actor.name || (meta.label + ' ' + (index + 1))).slice(0, 80),
                image: actor.image || (config.actorImages || {})[type] || '',
                color: /^#[0-9a-f]{6}$/i.test(actor.color || '') ? actor.color : meta.color,
                speedKmh: clamp(actor.speedKmh, 0, 300),
                scaleX: actorScale(actor, 'x'),
                scaleY: actorScale(actor, 'y'),
                keyframes: frames
            };
        }) : [];
        normalized.events = Array.isArray(raw.events) ? raw.events.filter(function (event) {
            return EVENT_META[event.code];
        }).map(function (event) {
            return {
                id: event.id || uid('event'), code: event.code,
                x: clamp(event.x, -10000, 10000), y: clamp(event.y, -10000, 10000),
                time: clamp(event.time, 0, normalized.metadata.duration),
                description: String(event.description || EVENT_META[event.code].label).slice(0, 240)
            };
        }) : [];
        return normalized;
    }

    function legacyRoads(template) {
        if (template === 'limpia') return [];
        if (template === 'recta') {
            return [{ id: uid('road'), type: 'straight', name: 'Calle principal', x: 600, y: 395, lengthMeters: 60, laneWidthMeters: 3.5, lanes: 2, rotation: 0, direction: 'one_way', centerLine: 'solid', leftEdge: 'none', rightEdge: 'none', surface: 'asphalt' }];
        }
        return [
            { id: uid('road'), type: 'straight', name: 'Avenida principal', x: 600, y: 420, lengthMeters: 60, laneWidthMeters: 3.5, lanes: 2, rotation: 0, direction: 'one_way', centerLine: 'solid', leftEdge: 'none', rightEdge: 'none', surface: 'asphalt' },
            { id: uid('road'), type: 'straight', name: 'Calle transversal', x: 650, y: 350, lengthMeters: 38, laneWidthMeters: 3.5, lanes: 2, rotation: 90, direction: 'one_way', centerLine: 'solid', leftEdge: 'none', rightEdge: 'none', surface: 'asphalt' }
        ];
    }

    function defaultCurve() {
        return {
            startX: -15, startY: 4,
            control1X: -10, control1Y: -10,
            control2X: 10, control2Y: -10,
            endX: 15, endY: 4
        };
    }

    function normalizeCurve(raw) {
        const defaults = defaultCurve();
        raw = raw || {};
        Object.keys(defaults).forEach(function (key) {
            const value = Number(raw[key]);
            defaults[key] = Number.isFinite(value) ? clamp(value, -200, 200) : defaults[key];
        });
        return defaults;
    }

    function loadProject() {
        try {
            const raw = localStorage.getItem(storageKey);
            return raw ? normalizeProject(JSON.parse(raw)) : null;
        } catch (error) {
            console.warn('No fue posible recuperar el borrador del reconstructor.', error);
            return null;
        }
    }

    function saveProject(showMessage) {
        syncMetadataFromInputs();
        localStorage.setItem(storageKey, JSON.stringify(project));
        clearTimeout(dirtyTimer);
        el.rtSaveStatus.classList.add('is-saved');
        el.rtSaveStatus.classList.remove('is-recording');
        el.rtSaveStatus.innerHTML = '<i class="fas fa-circle"></i> Guardado local';
        if (showMessage) toast('Borrador guardado en este navegador.');
    }

    function markDirty() {
        el.rtSaveStatus.classList.remove('is-saved');
        el.rtSaveStatus.innerHTML = '<i class="fas fa-circle"></i> Cambios pendientes';
        clearTimeout(dirtyTimer);
        dirtyTimer = setTimeout(function () { saveProject(false); }, 1200);
    }

    function pushHistory() {
        history.push(JSON.stringify(project));
        if (history.length > 35) history.shift();
        el.rtUndo.disabled = false;
    }

    function undo() {
        if (!history.length) return;
        pause();
        project = normalizeProject(JSON.parse(history.pop()));
        currentTime = clamp(currentTime, 0, project.metadata.duration);
        selected = null;
        syncInputsFromProject();
        renderAll();
        markDirty();
        el.rtUndo.disabled = history.length === 0;
    }

    function syncMetadataFromInputs() {
        project.metadata.name = (el.rtProjectName.value || 'Hecho de tránsito sin título').trim();
        project.metadata.hypothesis = (el.rtHypothesis.value || 'Hipótesis A').trim();
        project.metadata.duration = clamp(el.rtDuration.value, 2, 120);
        project.metadata.pixelsPerMeter = clamp(el.rtScale.value, 2, 100);
    }

    function syncInputsFromProject() {
        el.rtProjectName.value = project.metadata.name;
        el.rtHypothesis.value = project.metadata.hypothesis;
        el.rtDuration.value = project.metadata.duration;
        el.rtScale.value = project.metadata.pixelsPerMeter;
        el.rtTimeline.max = project.metadata.duration;
        el.rtCurrentTime.max = project.metadata.duration;
        el.rtDurationLabel.textContent = formatClock(project.metadata.duration, false);
        document.querySelectorAll('[data-layer]').forEach(function (input) {
            input.checked = project.layers[input.dataset.layer] !== false;
        });
    }

    function actorPosition(actor, time) {
        const frames = actor.keyframes.slice().sort(function (a, b) { return a.time - b.time; });
        if (!frames.length) return null;
        if (time <= frames[0].time) return Object.assign({}, frames[0]);
        if (time >= frames[frames.length - 1].time) return Object.assign({}, frames[frames.length - 1]);
        for (let i = 0; i < frames.length - 1; i++) {
            const from = frames[i];
            const to = frames[i + 1];
            if (time >= from.time && time <= to.time) {
                const progress = (time - from.time) / Math.max(.001, to.time - from.time);
                let rotationDelta = ((to.rotation - from.rotation + 540) % 360) - 180;
                return {
                    time: time,
                    x: from.x + ((to.x - from.x) * progress),
                    y: from.y + ((to.y - from.y) * progress),
                    rotation: from.rotation + (rotationDelta * progress)
                };
            }
        }
        return Object.assign({}, frames[0]);
    }

    function upsertKeyframe(actor, time, x, y, rotation, options) {
        options = options || {};
        const rounded = Math.round(clamp(time, 0, project.metadata.duration) * 100) / 100;
        let frame = actor.keyframes.find(function (item) { return Math.abs(item.time - rounded) < .015; });
        if (!frame) {
            frame = { time: rounded, x: x, y: y, rotation: rotation || 0, rotationManual: Boolean(options.manualRotation) };
            actor.keyframes.push(frame);
        } else {
            frame.x = x;
            frame.y = y;
            if (!frame.rotationManual || options.manualRotation) frame.rotation = rotation || 0;
            if (options.manualRotation) frame.rotationManual = true;
        }
        actor.keyframes.sort(function (a, b) { return a.time - b.time; });
        return frame;
    }

    function recalculateRotations(actor) {
        const frames = actor.keyframes;
        for (let i = 0; i < frames.length; i++) {
            if (frames[i].rotationManual) continue;
            const from = i < frames.length - 1 ? frames[i] : frames[i - 1];
            const to = i < frames.length - 1 ? frames[i + 1] : frames[i];
            if (from && to && (from.x !== to.x || from.y !== to.y)) {
                frames[i].rotation = Math.atan2(to.y - from.y, to.x - from.x) * 180 / Math.PI;
            }
        }
    }

    function preloadImages() {
        Object.keys(config.actorImages || {}).forEach(function (type) { getImage(config.actorImages[type]); });
        project.actors.forEach(function (actor) { getImage(actor.image); });
    }

    function getImage(src) {
        if (!src) return null;
        if (!imageCache[src]) {
            const image = new Image();
            image.onload = draw;
            image.src = src;
            imageCache[src] = image;
        }
        return imageCache[src];
    }

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawBackground();
        ctx.save();
        ctx.translate(camera.panX, camera.panY);
        ctx.scale(camera.zoom, camera.zoom);
        if (project.layers.grid !== false) drawGrid();
        if (project.layers.road !== false) {
            project.scene.roads.forEach(drawRoadFill);
            project.scene.roads.forEach(drawRoadMarkings);
        }
        if (project.layers.paths !== false) project.actors.forEach(drawActorPath);
        if (project.layers.events !== false) project.events.forEach(drawEvent);
        if (project.layers.actors !== false) project.actors.forEach(drawActor);
        ctx.restore();
        drawScaleAndLegend();
        drawVideoHeader();
        el.rtCanvasTime.textContent = formatClock(currentTime, true);
        el.rtZoomLabel.textContent = Math.round(camera.zoom * 100) + '%';
    }

    function drawBackground() {
        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
        gradient.addColorStop(0, '#152235');
        gradient.addColorStop(1, '#101a29');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    function drawGrid() {
        const step = Math.max(10, project.metadata.pixelsPerMeter || 20);
        const left = (-camera.panX / camera.zoom) - step;
        const right = ((canvas.width - camera.panX) / camera.zoom) + step;
        const top = (-camera.panY / camera.zoom) - step;
        const bottom = ((canvas.height - camera.panY) / camera.zoom) + step;
        ctx.save();
        ctx.strokeStyle = 'rgba(148, 163, 184, .08)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        for (let x = Math.floor(left / step) * step; x <= right; x += step) { ctx.moveTo(x, top); ctx.lineTo(x, bottom); }
        for (let y = Math.floor(top / step) * step; y <= bottom; y += step) { ctx.moveTo(left, y); ctx.lineTo(right, y); }
        ctx.stroke();
        ctx.restore();
    }

    function roadGeometry(road) {
        const pixelsPerMeter = project.metadata.pixelsPerMeter || 20;
        const edgeWidth = function (type) {
            if (type === 'sidewalk') return 1.8 * pixelsPerMeter;
            if (type === 'median') return 2.5 * pixelsPerMeter;
            return 0;
        };
        return {
            length: road.lengthMeters * pixelsPerMeter,
            laneWidth: road.laneWidthMeters * pixelsPerMeter,
            width: road.lanes * road.laneWidthMeters * pixelsPerMeter,
            leftEdgeWidth: edgeWidth(road.leftEdge),
            rightEdgeWidth: edgeWidth(road.rightEdge)
        };
    }

    function roadWorldPoint(road, localX, localY) {
        const angle = road.rotation * Math.PI / 180;
        return {
            x: road.x + (localX * Math.cos(angle)) - (localY * Math.sin(angle)),
            y: road.y + (localX * Math.sin(angle)) + (localY * Math.cos(angle))
        };
    }

    function roadCurvePointLocal(road, t, offset) {
        const pixelsPerMeter = project.metadata.pixelsPerMeter || 20;
        const curve = road.curve || defaultCurve();
        const p0 = { x: curve.startX * pixelsPerMeter, y: curve.startY * pixelsPerMeter };
        const p1 = { x: curve.control1X * pixelsPerMeter, y: curve.control1Y * pixelsPerMeter };
        const p2 = { x: curve.control2X * pixelsPerMeter, y: curve.control2Y * pixelsPerMeter };
        const p3 = { x: curve.endX * pixelsPerMeter, y: curve.endY * pixelsPerMeter };
        const u = 1 - t;
        const x = (u * u * u * p0.x) + (3 * u * u * t * p1.x) + (3 * u * t * t * p2.x) + (t * t * t * p3.x);
        const y = (u * u * u * p0.y) + (3 * u * u * t * p1.y) + (3 * u * t * t * p2.y) + (t * t * t * p3.y);
        const dx = (3 * u * u * (p1.x - p0.x)) + (6 * u * t * (p2.x - p1.x)) + (3 * t * t * (p3.x - p2.x));
        const dy = (3 * u * u * (p1.y - p0.y)) + (6 * u * t * (p2.y - p1.y)) + (3 * t * t * (p3.y - p2.y));
        const length = Math.sqrt((dx * dx) + (dy * dy)) || 1;
        const normalOffset = Number(offset) || 0;
        return {
            x: x + ((-dy / length) * normalOffset),
            y: y + ((dx / length) * normalOffset),
            angle: Math.atan2(dy, dx)
        };
    }

    function roadCurvePolyline(road, offset, steps) {
        const points = [];
        const total = steps || 80;
        for (let index = 0; index <= total; index++) points.push(roadCurvePointLocal(road, index / total, offset || 0));
        return points;
    }

    function traceRoadPolyline(points) {
        if (!points.length) return;
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (let index = 1; index < points.length; index++) ctx.lineTo(points[index].x, points[index].y);
    }

    function roadCurveBounds(road) {
        const geometry = roadGeometry(road);
        const marginTop = (geometry.width / 2) + geometry.leftEdgeWidth;
        const marginBottom = (geometry.width / 2) + geometry.rightEdgeWidth;
        const left = roadCurvePolyline(road, -marginTop, 60);
        const right = roadCurvePolyline(road, marginBottom, 60);
        const points = left.concat(right);
        return {
            minX: Math.min.apply(null, points.map(function (point) { return point.x; })),
            maxX: Math.max.apply(null, points.map(function (point) { return point.x; })),
            minY: Math.min.apply(null, points.map(function (point) { return point.y; })),
            maxY: Math.max.apply(null, points.map(function (point) { return point.y; }))
        };
    }

    function roadCurveHandles(road) {
        const pixelsPerMeter = project.metadata.pixelsPerMeter || 20;
        const curve = road.curve || defaultCurve();
        const fields = {
            start: ['startX', 'startY'],
            control1: ['control1X', 'control1Y'],
            control2: ['control2X', 'control2Y'],
            end: ['endX', 'endY']
        };
        const handles = {};
        Object.keys(fields).forEach(function (name) {
            const pair = fields[name];
            const localX = curve[pair[0]] * pixelsPerMeter;
            const localY = curve[pair[1]] * pixelsPerMeter;
            const world = roadWorldPoint(road, localX, localY);
            handles[name] = { x: world.x, y: world.y, localX: localX, localY: localY, fields: pair };
        });
        return handles;
    }

    function roadRotationHandle(road) {
        const geometry = roadGeometry(road);
        let localX = 0;
        let localY = -(geometry.width / 2) - geometry.leftEdgeWidth - 30;
        if (road.type === 'curve') {
            const bounds = roadCurveBounds(road);
            localX = (bounds.minX + bounds.maxX) / 2;
            localY = bounds.minY - 30;
        }
        const point = roadWorldPoint(road, localX, localY);
        point.centerX = road.x;
        point.centerY = road.y;
        return point;
    }

    function roadSurfacePattern(surface) {
        const type = surface || 'asphalt';
        if (surfacePatternCache[type]) return surfacePatternCache[type];
        const tile = document.createElement('canvas');
        tile.width = 64; tile.height = 64;
        const patternContext = tile.getContext('2d');
        const specks = function (colors, count, radius) {
            for (let index = 0; index < count; index++) {
                const x = (index * 19 + 7) % 64;
                const y = (index * 31 + 11) % 64;
                patternContext.fillStyle = colors[index % colors.length];
                patternContext.beginPath();
                patternContext.arc(x, y, radius + (index % 2), 0, Math.PI * 2);
                patternContext.fill();
            }
        };

        if (type === 'concrete') {
            patternContext.fillStyle = '#b8bcbd'; patternContext.fillRect(0, 0, 64, 64);
            patternContext.strokeStyle = 'rgba(82,88,91,.32)'; patternContext.lineWidth = 1;
            patternContext.beginPath(); patternContext.moveTo(32, 0); patternContext.lineTo(32, 64); patternContext.moveTo(0, 32); patternContext.lineTo(64, 32); patternContext.stroke();
            specks(['rgba(255,255,255,.16)', 'rgba(68,73,75,.14)'], 12, .7);
        } else if (type === 'pavers') {
            patternContext.fillStyle = '#b77b61'; patternContext.fillRect(0, 0, 64, 64);
            patternContext.strokeStyle = '#805443'; patternContext.lineWidth = 1;
            for (let y = 0; y <= 64; y += 12) {
                patternContext.beginPath(); patternContext.moveTo(0, y); patternContext.lineTo(64, y); patternContext.stroke();
                const offset = (y / 12) % 2 ? 8 : 0;
                for (let x = offset; x <= 64; x += 16) { patternContext.beginPath(); patternContext.moveTo(x, y); patternContext.lineTo(x, y + 12); patternContext.stroke(); }
            }
        } else if (type === 'cobblestone') {
            patternContext.fillStyle = '#747976'; patternContext.fillRect(0, 0, 64, 64);
            for (let row = 0; row < 7; row++) {
                for (let column = 0; column < 7; column++) {
                    const x = column * 10 + (row % 2 ? 4 : -1);
                    const y = row * 9 + 3;
                    patternContext.fillStyle = (row + column) % 3 === 0 ? '#929590' : '#858985';
                    patternContext.strokeStyle = '#555b58'; patternContext.lineWidth = 1;
                    patternContext.beginPath(); patternContext.ellipse(x, y, 4.5, 3.3, ((row + column) % 4) * .12, 0, Math.PI * 2); patternContext.fill(); patternContext.stroke();
                }
            }
        } else if (type === 'dirt') {
            patternContext.fillStyle = '#9d794f'; patternContext.fillRect(0, 0, 64, 64);
            specks(['#765739', '#b28b5c', 'rgba(226,196,151,.45)'], 30, .8);
        } else if (type === 'gravel') {
            patternContext.fillStyle = '#7c796f'; patternContext.fillRect(0, 0, 64, 64);
            specks(['#515550', '#a49e8e', '#696d68', '#bbb29e'], 42, 1);
        } else if (type === 'natural') {
            patternContext.fillStyle = '#927045'; patternContext.fillRect(0, 0, 64, 64);
            patternContext.fillStyle = 'rgba(85,58,33,.4)'; patternContext.fillRect(13, 0, 8, 64); patternContext.fillRect(43, 0, 8, 64);
            specks(['rgba(73,104,53,.42)', 'rgba(69,48,28,.5)', 'rgba(190,155,99,.35)'], 20, 1);
        } else {
            patternContext.fillStyle = '#343e49'; patternContext.fillRect(0, 0, 64, 64);
            specks(['rgba(255,255,255,.12)', 'rgba(5,10,15,.22)', 'rgba(148,163,184,.12)'], 28, .55);
        }
        surfacePatternCache[type] = ctx.createPattern(tile, 'repeat') || '#343e49';
        return surfacePatternCache[type];
    }

    function drawRoadFill(road) {
        const geometry = roadGeometry(road);
        ctx.save();
        ctx.translate(road.x, road.y);
        ctx.rotate(road.rotation * Math.PI / 180);
        if (road.type === 'curve') {
            [
                { type: road.leftEdge, side: -1, width: geometry.leftEdgeWidth },
                { type: road.rightEdge, side: 1, width: geometry.rightEdgeWidth }
            ].forEach(function (edge) {
                if (!edge.width) return;
                const offset = edge.side * ((geometry.width / 2) + (edge.width / 2));
                ctx.lineCap = 'butt'; ctx.lineJoin = 'round';
                ctx.strokeStyle = '#e5e7eb'; ctx.lineWidth = edge.width + 3;
                traceRoadPolyline(roadCurvePolyline(road, offset)); ctx.stroke();
                ctx.strokeStyle = edge.type === 'median' ? '#638d52' : '#b6bbc1';
                ctx.lineWidth = Math.max(2, edge.width - (edge.type === 'median' ? 10 : 2));
                traceRoadPolyline(roadCurvePolyline(road, offset)); ctx.stroke();
            });
            ctx.strokeStyle = roadSurfacePattern(road.surface); ctx.lineWidth = geometry.width; ctx.lineCap = 'butt'; ctx.lineJoin = 'round';
            traceRoadPolyline(roadCurvePolyline(road, 0)); ctx.stroke();
            ctx.restore();
            return;
        }
        [
            { type: road.leftEdge, side: -1, width: geometry.leftEdgeWidth },
            { type: road.rightEdge, side: 1, width: geometry.rightEdgeWidth }
        ].forEach(function (edge) {
            if (!edge.width) return;
            const y = edge.side < 0 ? -(geometry.width / 2) - edge.width : geometry.width / 2;
            ctx.fillStyle = edge.type === 'median' ? '#d4d7db' : '#b6bbc1';
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = 2;
            ctx.fillRect(-geometry.length / 2, y, geometry.length, edge.width);
            ctx.strokeRect(-geometry.length / 2, y, geometry.length, edge.width);
            if (edge.type === 'median') {
                ctx.fillStyle = '#638d52';
                ctx.fillRect(-geometry.length / 2 + 4, y + 5, geometry.length - 8, Math.max(2, edge.width - 10));
            } else {
                ctx.strokeStyle = 'rgba(75,85,99,.42)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(-geometry.length / 2, y + edge.width / 2);
                ctx.lineTo(geometry.length / 2, y + edge.width / 2);
                ctx.stroke();
            }
        });
        ctx.fillStyle = roadSurfacePattern(road.surface);
        ctx.fillRect(-geometry.length / 2, -geometry.width / 2, geometry.length, geometry.width);
        ctx.restore();
    }

    function drawRoadMarkings(road) {
        const geometry = roadGeometry(road);
        const active = selected && selected.kind === 'road' && selected.id === road.id;
        if (road.type === 'curve') {
            drawCurveRoadMarkings(road, geometry, active);
            return;
        }
        ctx.save();
        ctx.translate(road.x, road.y);
        ctx.rotate(road.rotation * Math.PI / 180);
        ctx.strokeStyle = 'rgba(255,255,255,.72)';
        ctx.lineWidth = 2;
        ctx.setLineDash([]);
        [-geometry.width / 2 + 5, geometry.width / 2 - 5].forEach(function (y) {
            ctx.beginPath(); ctx.moveTo(-geometry.length / 2, y); ctx.lineTo(geometry.length / 2, y); ctx.stroke();
        });
        ctx.setLineDash([20, 14]);
        for (let lane = 1; lane < road.lanes; lane++) {
            if (road.direction === 'two_way' && lane === road.lanes / 2) continue;
            const y = (-geometry.width / 2) + (lane * geometry.laneWidth);
            ctx.beginPath(); ctx.moveTo(-geometry.length / 2, y); ctx.lineTo(geometry.length / 2, y); ctx.stroke();
        }
        if (road.direction === 'two_way') {
            ctx.strokeStyle = '#facc15';
            ctx.lineWidth = 3;
            ctx.setLineDash(road.centerLine === 'dashed' ? [18, 12] : []);
            const centerOffsets = road.centerLine === 'double_solid' ? [-4, 4] : [0];
            centerOffsets.forEach(function (y) {
                ctx.beginPath(); ctx.moveTo(-geometry.length / 2, y); ctx.lineTo(geometry.length / 2, y); ctx.stroke();
            });
            drawRoadDirectionArrows(geometry);
        }
        if (active) {
            ctx.setLineDash([7, 5]);
            ctx.strokeStyle = '#32b3ff';
            ctx.lineWidth = 3;
            const top = -(geometry.width / 2) - geometry.leftEdgeWidth;
            const totalWidth = geometry.width + geometry.leftEdgeWidth + geometry.rightEdgeWidth;
            ctx.strokeRect(-geometry.length / 2 - 6, top - 6, geometry.length + 12, totalWidth + 12);
            ctx.setLineDash([]);
            ctx.fillStyle = '#32b3ff';
            [-geometry.length / 2, geometry.length / 2].forEach(function (x) {
                ctx.beginPath(); ctx.arc(x, 0, 7, 0, Math.PI * 2); ctx.fill();
            });
        }
        ctx.restore();

        if (active) {
            const handle = roadRotationHandle(road);
            ctx.save();
            ctx.strokeStyle = '#fff';
            ctx.fillStyle = '#32b3ff';
            ctx.lineWidth = 2;
            ctx.setLineDash([4, 4]);
            ctx.beginPath(); ctx.moveTo(road.x, road.y); ctx.lineTo(handle.x, handle.y); ctx.stroke();
            ctx.setLineDash([]);
            ctx.beginPath(); ctx.arc(handle.x, handle.y, 10, 0, Math.PI * 2); ctx.fill(); ctx.stroke();
            ctx.fillStyle = '#fff'; ctx.font = '900 10px Arial'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText('↻', handle.x, handle.y);
            ctx.textBaseline = 'alphabetic';
            ctx.font = '700 9px Arial';
            const label = road.name + ' · ' + road.lanes + (road.lanes === 1 ? ' carril' : ' carriles') + (road.direction === 'two_way' ? ' · doble sentido' : '');
            const labelWidth = ctx.measureText(label).width + 12;
            ctx.fillStyle = 'rgba(4,10,18,.84)';
            ctx.fillRect(road.x - labelWidth / 2, road.y + 16, labelWidth, 17);
            ctx.fillStyle = '#fff'; ctx.fillText(label, road.x, road.y + 28);
            ctx.restore();
        }
    }

    function drawCurveRoadMarkings(road, geometry, active) {
        ctx.save();
        ctx.translate(road.x, road.y);
        ctx.rotate(road.rotation * Math.PI / 180);
        ctx.lineCap = 'butt'; ctx.lineJoin = 'round'; ctx.lineWidth = 2;
        ctx.strokeStyle = 'rgba(255,255,255,.72)'; ctx.setLineDash([]);
        [-(geometry.width / 2) + 5, (geometry.width / 2) - 5].forEach(function (offset) {
            traceRoadPolyline(roadCurvePolyline(road, offset)); ctx.stroke();
        });
        ctx.setLineDash([20, 14]);
        for (let lane = 1; lane < road.lanes; lane++) {
            if (road.direction === 'two_way' && lane === road.lanes / 2) continue;
            const offset = (-geometry.width / 2) + (lane * geometry.laneWidth);
            traceRoadPolyline(roadCurvePolyline(road, offset)); ctx.stroke();
        }
        if (road.direction === 'two_way') {
            ctx.strokeStyle = '#facc15'; ctx.lineWidth = 3;
            ctx.setLineDash(road.centerLine === 'dashed' ? [18, 12] : []);
            (road.centerLine === 'double_solid' ? [-4, 4] : [0]).forEach(function (offset) {
                traceRoadPolyline(roadCurvePolyline(road, offset)); ctx.stroke();
            });
            drawCurveDirectionArrows(road, geometry);
        }
        if (active) drawCurveRoadSelection(road, geometry);
        ctx.restore();

        if (active) drawRoadWorldLabelAndRotation(road);
    }

    function drawCurveDirectionArrows(road, geometry) {
        ctx.save();
        ctx.setLineDash([]); ctx.strokeStyle = 'rgba(255,255,255,.42)'; ctx.fillStyle = 'rgba(255,255,255,.42)'; ctx.lineWidth = 2;
        [.28, .72].forEach(function (t) {
            [{ offset: -geometry.width / 4, direction: -1 }, { offset: geometry.width / 4, direction: 1 }].forEach(function (item) {
                const point = roadCurvePointLocal(road, t, item.offset);
                ctx.save(); ctx.translate(point.x, point.y); ctx.rotate(point.angle + (item.direction < 0 ? Math.PI : 0));
                drawDirectionArrow(0, 0, 1); ctx.restore();
            });
        });
        ctx.restore();
    }

    function drawCurveRoadSelection(road, geometry) {
        const topOffset = -(geometry.width / 2) - geometry.leftEdgeWidth - 6;
        const bottomOffset = (geometry.width / 2) + geometry.rightEdgeWidth + 6;
        ctx.strokeStyle = '#32b3ff'; ctx.lineWidth = 3; ctx.setLineDash([7, 5]);
        [topOffset, bottomOffset].forEach(function (offset) { traceRoadPolyline(roadCurvePolyline(road, offset)); ctx.stroke(); });
        ctx.setLineDash([5, 4]); ctx.strokeStyle = '#20c997'; ctx.lineWidth = 2;
        const handles = roadCurveHandles(road);
        const local = {};
        Object.keys(handles).forEach(function (name) { local[name] = { x: handles[name].localX, y: handles[name].localY }; });
        ctx.beginPath();
        ctx.moveTo(local.start.x, local.start.y); ctx.lineTo(local.control1.x, local.control1.y);
        ctx.moveTo(local.end.x, local.end.y); ctx.lineTo(local.control2.x, local.control2.y); ctx.stroke();
        ctx.setLineDash([]);
        ['start', 'end'].forEach(function (name) { ctx.fillStyle = '#7c3aed'; ctx.beginPath(); ctx.arc(local[name].x, local[name].y, 10, 0, Math.PI * 2); ctx.fill(); });
        ['control1', 'control2'].forEach(function (name) { ctx.fillStyle = '#20c997'; ctx.beginPath(); ctx.arc(local[name].x, local[name].y, 10, 0, Math.PI * 2); ctx.fill(); });
    }

    function drawRoadWorldLabelAndRotation(road) {
        const handle = roadRotationHandle(road);
        const centerLocal = road.type === 'curve' ? roadCurvePointLocal(road, .5, 0) : { x: 0, y: 0 };
        const center = roadWorldPoint(road, centerLocal.x, centerLocal.y);
        ctx.save();
        ctx.strokeStyle = '#fff'; ctx.fillStyle = '#32b3ff'; ctx.lineWidth = 2; ctx.setLineDash([4, 4]);
        ctx.beginPath(); ctx.moveTo(center.x, center.y); ctx.lineTo(handle.x, handle.y); ctx.stroke();
        ctx.setLineDash([]); ctx.beginPath(); ctx.arc(handle.x, handle.y, 10, 0, Math.PI * 2); ctx.fill(); ctx.stroke();
        ctx.fillStyle = '#fff'; ctx.font = '900 10px Arial'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText('↻', handle.x, handle.y);
        ctx.textBaseline = 'alphabetic'; ctx.font = '700 9px Arial';
        const label = road.name + ' · ' + road.lanes + (road.lanes === 1 ? ' carril' : ' carriles') + (road.direction === 'two_way' ? ' · doble sentido' : '');
        const labelWidth = ctx.measureText(label).width + 12;
        ctx.fillStyle = 'rgba(4,10,18,.84)'; ctx.fillRect(center.x - labelWidth / 2, center.y + 16, labelWidth, 17);
        ctx.fillStyle = '#fff'; ctx.fillText(label, center.x, center.y + 28);
        ctx.restore();
    }

    function drawRoadDirectionArrows(geometry) {
        const sideCenter = geometry.width / 4;
        const positions = [-geometry.length / 4, geometry.length / 4];
        ctx.save();
        ctx.setLineDash([]);
        ctx.strokeStyle = 'rgba(255,255,255,.42)';
        ctx.fillStyle = 'rgba(255,255,255,.42)';
        ctx.lineWidth = 2;
        positions.forEach(function (x) {
            drawDirectionArrow(x, -sideCenter, -1);
            drawDirectionArrow(x, sideCenter, 1);
        });
        ctx.restore();
    }

    function drawDirectionArrow(x, y, direction) {
        const size = 16;
        ctx.beginPath();
        ctx.moveTo(x - (direction * size), y);
        ctx.lineTo(x + (direction * size), y);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(x + (direction * size), y);
        ctx.lineTo(x + (direction * 7), y - 6);
        ctx.lineTo(x + (direction * 7), y + 6);
        ctx.closePath();
        ctx.fill();
    }

    function impactTime() {
        const impact = project.events.filter(function (event) { return event.code === 'PI'; }).sort(function (a, b) { return a.time - b.time; })[0];
        return impact ? impact.time : Infinity;
    }

    function drawActorPath(actor) {
        const frames = actor.keyframes;
        if (frames.length < 2) return;
        const splitTime = impactTime();
        const active = selected && selected.kind === 'actor' && selected.id === actor.id;
        ctx.save();
        ctx.lineWidth = active ? 4 : 3;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        for (let i = 0; i < frames.length - 1; i++) {
            const from = frames[i];
            const to = frames[i + 1];
            const posterior = from.time >= splitTime;
            ctx.setLineDash(posterior ? [10, 7] : []);
            if (active) {
                ctx.strokeStyle = 'rgba(255,255,255,.72)';
                ctx.globalAlpha = 1;
                ctx.lineWidth = 8;
                ctx.beginPath(); ctx.moveTo(from.x, from.y); ctx.lineTo(to.x, to.y); ctx.stroke();
            }
            ctx.strokeStyle = actor.color;
            ctx.globalAlpha = posterior ? .7 : .9;
            ctx.lineWidth = active ? 4 : 3;
            ctx.beginPath(); ctx.moveTo(from.x, from.y); ctx.lineTo(to.x, to.y); ctx.stroke();
            drawArrow(from, to, actor.color, posterior ? .72 : .95);
        }
        frames.forEach(function (frame, index) {
            ctx.setLineDash([]);
            ctx.globalAlpha = 1;
            ctx.fillStyle = '#0d1725';
            ctx.strokeStyle = active ? '#fff' : actor.color;
            ctx.lineWidth = active ? 3 : 2;
            ctx.beginPath(); ctx.arc(frame.x, frame.y, active ? 8 : 5, 0, Math.PI * 2); ctx.fill(); ctx.stroke();
            if (active) {
                ctx.fillStyle = '#f8fafc'; ctx.font = '700 9px Arial';
                ctx.fillText(index + 1, frame.x + 11, frame.y - 10);
            }
        });
        if (frames.length) {
            drawPathTag(frames[0].x, frames[0].y, 'TRAYECTORIA INICIAL', actor.color);
            if (isFinite(splitTime)) {
                const posteriorFrame = frames.find(function (frame) { return frame.time >= splitTime; });
                if (posteriorFrame) drawPathTag(posteriorFrame.x, posteriorFrame.y + 20, 'POSTERIOR AL IMPACTO', actor.color);
            }
        }
        ctx.restore();
    }

    function drawArrow(from, to, color, alpha) {
        const dx = to.x - from.x;
        const dy = to.y - from.y;
        const length = Math.sqrt(dx * dx + dy * dy);
        if (length < 50) return;
        const progress = .62;
        const x = from.x + dx * progress;
        const y = from.y + dy * progress;
        const angle = Math.atan2(dy, dx);
        ctx.save(); ctx.translate(x, y); ctx.rotate(angle); ctx.globalAlpha = alpha; ctx.fillStyle = color;
        ctx.beginPath(); ctx.moveTo(9, 0); ctx.lineTo(-5, -5); ctx.lineTo(-5, 5); ctx.closePath(); ctx.fill(); ctx.restore();
    }

    function drawPathTag(x, y, label, color) {
        ctx.save();
        ctx.font = '700 8px Arial';
        const width = ctx.measureText(label).width + 10;
        ctx.fillStyle = 'rgba(7, 16, 28, .78)';
        ctx.fillRect(x - 3, y - 22, width, 15);
        ctx.fillStyle = color;
        ctx.fillText(label, x + 2, y - 11);
        ctx.restore();
    }

    function drawActor(actor) {
        const position = actorPosition(actor, currentTime);
        if (!position) return;
        const meta = ACTOR_META[actor.type] || ACTOR_META.automovil;
        const width = meta.width * actorScale(actor, 'x');
        const height = meta.height * actorScale(actor, 'y');
        const image = getImage(actor.image);
        const active = selected && selected.kind === 'actor' && selected.id === actor.id;
        ctx.save();
        ctx.translate(position.x, position.y);
        ctx.rotate(position.rotation * Math.PI / 180);
        if (active) {
            ctx.shadowColor = actor.color;
            ctx.shadowBlur = 18;
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 4]);
            ctx.strokeRect(-width / 2 - 7, -height / 2 - 7, width + 14, height + 14);
            ctx.setLineDash([]);
        }
        if (image && image.complete && image.naturalWidth) {
            ctx.drawImage(image, -width / 2, -height / 2, width, height);
        } else {
            ctx.fillStyle = actor.color;
            ctx.fillRect(-width / 2, -height / 2, width, height);
            ctx.fillStyle = '#e5e7eb';
            ctx.fillRect(-width * .2, -height * .38, width * .38, height * .76);
        }
        ctx.restore();
        ctx.save();
        ctx.font = '700 10px Arial';
        const labelWidth = ctx.measureText(actor.name).width + 12;
        ctx.fillStyle = 'rgba(4, 10, 18, .82)';
        ctx.fillRect(position.x - labelWidth / 2, position.y + height / 2 + 9, labelWidth, 17);
        ctx.fillStyle = '#f8fafc';
        ctx.textAlign = 'center';
        ctx.fillText(actor.name, position.x, position.y + height / 2 + 21);
        ctx.restore();
        if (active && !playing) {
            drawResizeHandles(actor, position);
            drawRotationHandle(actor, position);
        }
    }

    function actorResizeHandles(actor, position) {
        if (!actor || !position) return [];
        const meta = ACTOR_META[actor.type] || ACTOR_META.automovil;
        const halfWidth = (meta.width * actorScale(actor, 'x') / 2) + 7;
        const halfHeight = (meta.height * actorScale(actor, 'y') / 2) + 7;
        const angle = position.rotation * Math.PI / 180;
        const cosine = Math.cos(angle);
        const sine = Math.sin(angle);
        return [
            { name: 'nw', localX: -halfWidth, localY: -halfHeight },
            { name: 'n', localX: 0, localY: -halfHeight },
            { name: 'ne', localX: halfWidth, localY: -halfHeight },
            { name: 'e', localX: halfWidth, localY: 0 },
            { name: 'se', localX: halfWidth, localY: halfHeight },
            { name: 's', localX: 0, localY: halfHeight },
            { name: 'sw', localX: -halfWidth, localY: halfHeight },
            { name: 'w', localX: -halfWidth, localY: 0 }
        ].map(function (handle) {
            return {
                name: handle.name,
                x: position.x + (handle.localX * cosine) - (handle.localY * sine),
                y: position.y + (handle.localX * sine) + (handle.localY * cosine)
            };
        });
    }

    function drawResizeHandles(actor, position) {
        ctx.save();
        ctx.fillStyle = '#f8fafc';
        ctx.strokeStyle = actor.color;
        ctx.lineWidth = 2;
        actorResizeHandles(actor, position).forEach(function (handle) {
            ctx.beginPath();
            ctx.rect(handle.x - 6, handle.y - 6, 12, 12);
            ctx.fill();
            ctx.stroke();
        });
        ctx.restore();
    }

    function rotationHandle(actor, position) {
        if (!actor || !position) return null;
        const meta = ACTOR_META[actor.type] || ACTOR_META.automovil;
        const width = meta.width * actorScale(actor, 'x');
        const height = meta.height * actorScale(actor, 'y');
        const distanceFromCenter = (Math.max(width, height) / 2) + 28;
        const angle = (position.rotation - 90) * Math.PI / 180;
        return {
            x: position.x + (Math.cos(angle) * distanceFromCenter),
            y: position.y + (Math.sin(angle) * distanceFromCenter),
            centerX: position.x,
            centerY: position.y
        };
    }

    function drawRotationHandle(actor, position) {
        const handle = rotationHandle(actor, position);
        if (!handle) return;
        ctx.save();
        ctx.strokeStyle = 'rgba(255,255,255,.9)';
        ctx.fillStyle = actor.color;
        ctx.lineWidth = 2;
        ctx.setLineDash([4, 4]);
        ctx.beginPath(); ctx.moveTo(handle.centerX, handle.centerY); ctx.lineTo(handle.x, handle.y); ctx.stroke();
        ctx.setLineDash([]);
        ctx.beginPath(); ctx.arc(handle.x, handle.y, 10, 0, Math.PI * 2); ctx.fill(); ctx.stroke();
        ctx.fillStyle = '#fff'; ctx.font = '900 10px Arial'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText('↻', handle.x, handle.y);
        ctx.restore();
    }

    function drawEvent(event) {
        const meta = EVENT_META[event.code];
        const active = selected && selected.kind === 'event' && selected.id === event.id;
        const visibleByTime = currentTime + .08 >= event.time;
        ctx.save();
        ctx.globalAlpha = visibleByTime ? 1 : .35;
        ctx.strokeStyle = active ? '#fff' : meta.color;
        ctx.fillStyle = meta.color;
        ctx.lineWidth = active ? 3 : 2;
        if (event.code === 'PMC' || event.code === 'PI') {
            ctx.beginPath(); ctx.arc(event.x, event.y, active ? 20 : 16, 0, Math.PI * 2); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(event.x - 23, event.y); ctx.lineTo(event.x + 23, event.y); ctx.moveTo(event.x, event.y - 23); ctx.lineTo(event.x, event.y + 23); ctx.stroke();
        }
        ctx.beginPath(); ctx.arc(event.x, event.y, 11, 0, Math.PI * 2); ctx.fill();
        ctx.fillStyle = '#fff'; ctx.font = '900 7px Arial'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText(event.code, event.x, event.y);
        ctx.textBaseline = 'alphabetic';
        ctx.font = '700 8px Arial';
        const text = meta.label + ' · ' + event.time.toFixed(1) + ' s';
        const width = ctx.measureText(text).width + 10;
        ctx.fillStyle = 'rgba(5, 12, 21, .8)'; ctx.fillRect(event.x - width / 2, event.y - 36, width, 15);
        ctx.fillStyle = '#e7edf7'; ctx.fillText(text, event.x, event.y - 25);
        ctx.restore();
    }

    function drawScaleAndLegend() {
        const ppm = project.metadata.pixelsPerMeter || 20;
        const rawMeters = 100 / Math.max(1, ppm * camera.zoom);
        const power = Math.pow(10, Math.floor(Math.log10(Math.max(.1, rawMeters))));
        const normalized = rawMeters / power;
        const nice = normalized <= 1 ? 1 : (normalized <= 2 ? 2 : (normalized <= 5 ? 5 : 10));
        const meters = nice * power;
        const pixels = meters * ppm * camera.zoom;
        ctx.save();
        ctx.strokeStyle = '#e5e7eb'; ctx.fillStyle = '#d4deea'; ctx.lineWidth = 2; ctx.font = '700 8px Arial';
        const y = canvas.height - 25;
        ctx.beginPath(); ctx.moveTo(canvas.width - 30 - pixels, y); ctx.lineTo(canvas.width - 30, y); ctx.moveTo(canvas.width - 30 - pixels, y - 4); ctx.lineTo(canvas.width - 30 - pixels, y + 4); ctx.moveTo(canvas.width - 30, y - 4); ctx.lineTo(canvas.width - 30, y + 4); ctx.stroke();
        ctx.textAlign = 'center'; ctx.fillText(meters + ' m', canvas.width - 30 - pixels / 2, y - 7);
        ctx.restore();
    }

    function drawVideoHeader() {
        ctx.save();
        ctx.fillStyle = 'rgba(4, 10, 18, .68)';
        ctx.fillRect(0, 0, canvas.width, 44);
        ctx.fillStyle = '#e7eef8'; ctx.font = '800 14px Arial'; ctx.fillText(project.metadata.name, 16, 20);
        ctx.fillStyle = '#8fa0b8'; ctx.font = '700 9px Arial'; ctx.fillText(project.metadata.hypothesis + ' · RECONSTRUCCIÓN ILUSTRATIVA', 16, 35);
        ctx.textAlign = 'right'; ctx.fillStyle = '#fff'; ctx.font = '900 16px Arial'; ctx.fillText(formatClock(currentTime, true), canvas.width - 16, 27);
        ctx.restore();
    }

    function formatClock(seconds, decimals) {
        const value = Math.max(0, Number(seconds) || 0);
        const minutes = Math.floor(value / 60);
        const remaining = value - (minutes * 60);
        const sec = decimals ? remaining.toFixed(1).padStart(4, '0') : Math.floor(remaining).toString().padStart(2, '0');
        return String(minutes).padStart(2, '0') + ':' + sec;
    }

    function canvasPoint(event) {
        const screen = canvasScreenPoint(event);
        return {
            x: (screen.x - camera.panX) / camera.zoom,
            y: (screen.y - camera.panY) / camera.zoom
        };
    }

    function canvasScreenPoint(event) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (event.clientX - rect.left) * (canvas.width / rect.width),
            y: (event.clientY - rect.top) * (canvas.height / rect.height)
        };
    }

    function setZoom(value, anchor) {
        const nextZoom = clamp(value, .25, 5);
        const focus = anchor || { x: canvas.width / 2, y: canvas.height / 2 };
        const worldX = (focus.x - camera.panX) / camera.zoom;
        const worldY = (focus.y - camera.panY) / camera.zoom;
        camera.zoom = nextZoom;
        camera.panX = focus.x - (worldX * nextZoom);
        camera.panY = focus.y - (worldY * nextZoom);
        draw();
    }

    function cameraWorldCenter() {
        return {
            x: ((canvas.width / 2) - camera.panX) / camera.zoom,
            y: ((canvas.height / 2) - camera.panY) / camera.zoom
        };
    }

    function fitScene() {
        const bounds = sceneBounds();
        const width = Math.max(100, bounds.maxX - bounds.minX);
        const height = Math.max(100, bounds.maxY - bounds.minY);
        camera.zoom = clamp(Math.min((canvas.width - 100) / width, (canvas.height - 100) / height), .25, 3);
        camera.panX = (canvas.width / 2) - (((bounds.minX + bounds.maxX) / 2) * camera.zoom);
        camera.panY = (canvas.height / 2) - (((bounds.minY + bounds.maxY) / 2) * camera.zoom);
        draw();
    }

    function sceneBounds() {
        const bounds = { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity };
        const include = function (point, margin) {
            const extra = margin || 0;
            bounds.minX = Math.min(bounds.minX, point.x - extra);
            bounds.minY = Math.min(bounds.minY, point.y - extra);
            bounds.maxX = Math.max(bounds.maxX, point.x + extra);
            bounds.maxY = Math.max(bounds.maxY, point.y + extra);
        };
        project.scene.roads.forEach(function (road) {
            const geometry = roadGeometry(road);
            if (road.type === 'curve') {
                const curveBounds = roadCurveBounds(road);
                [
                    { x: curveBounds.minX, y: curveBounds.minY }, { x: curveBounds.maxX, y: curveBounds.minY },
                    { x: curveBounds.minX, y: curveBounds.maxY }, { x: curveBounds.maxX, y: curveBounds.maxY }
                ].forEach(function (point) { include(roadWorldPoint(road, point.x, point.y), 20); });
            } else {
                const top = -(geometry.width / 2) - geometry.leftEdgeWidth;
                const bottom = (geometry.width / 2) + geometry.rightEdgeWidth;
                [
                    { x: -geometry.length / 2, y: top }, { x: geometry.length / 2, y: top },
                    { x: -geometry.length / 2, y: bottom }, { x: geometry.length / 2, y: bottom }
                ].forEach(function (point) { include(roadWorldPoint(road, point.x, point.y), 20); });
            }
        });
        project.actors.forEach(function (actor) {
            const meta = ACTOR_META[actor.type] || ACTOR_META.automovil;
            const width = meta.width * actorScale(actor, 'x');
            const height = meta.height * actorScale(actor, 'y');
            const padding = (Math.max(width, height) / 2) + 35;
            actor.keyframes.forEach(function (frame) { include(frame, padding); });
        });
        project.events.forEach(function (event) { include(event, 35); });
        if (!Number.isFinite(bounds.minX)) return { minX: 0, minY: 0, maxX: canvas.width, maxY: canvas.height };
        return bounds;
    }

    function updateFullscreenButton() {
        const active = document.fullscreenElement === root || fullscreenFallback;
        el.rtFullscreen.innerHTML = active
            ? '<i class="fas fa-compress-arrows-alt"></i><span>Salir de pantalla completa</span>'
            : '<i class="fas fa-expand-arrows-alt"></i><span>Pantalla completa</span>';
    }

    function toggleFullscreen() {
        if (document.fullscreenElement === root) {
            document.exitFullscreen();
            return;
        }
        if (fullscreenFallback) {
            fullscreenFallback = false;
            root.classList.remove('is-focus-mode');
            document.body.classList.remove('rt-focus-lock');
            updateFullscreenButton();
            draw();
            return;
        }
        if (root.requestFullscreen) {
            root.requestFullscreen().catch(function () {
                fullscreenFallback = true;
                root.classList.add('is-focus-mode');
                document.body.classList.add('rt-focus-lock');
                updateFullscreenButton();
                draw();
            });
        } else {
            fullscreenFallback = true;
            root.classList.add('is-focus-mode');
            document.body.classList.add('rt-focus-lock');
            updateFullscreenButton();
            draw();
        }
    }

    function findHit(point) {
        if (project.layers.events !== false) {
            for (let i = project.events.length - 1; i >= 0; i--) {
                const event = project.events[i];
                if (distance(point, event) <= 22) return { kind: 'event', id: event.id };
            }
        }
        if (project.layers.actors !== false) {
            for (let i = project.actors.length - 1; i >= 0; i--) {
                const actor = project.actors[i];
                const position = actorPosition(actor, currentTime);
                const meta = ACTOR_META[actor.type] || ACTOR_META.automovil;
                if (!position) continue;
                const angle = -position.rotation * Math.PI / 180;
                const dx = point.x - position.x;
                const dy = point.y - position.y;
                const localX = (dx * Math.cos(angle)) - (dy * Math.sin(angle));
                const localY = (dx * Math.sin(angle)) + (dy * Math.cos(angle));
                const scaleX = actorScale(actor, 'x');
                const scaleY = actorScale(actor, 'y');
                if (Math.abs(localX) <= (meta.width * scaleX / 2) + 12 && Math.abs(localY) <= (meta.height * scaleY / 2) + 12) {
                    return { kind: 'actor', id: actor.id };
                }
            }
        }
        if (project.layers.road !== false) {
            for (let i = project.scene.roads.length - 1; i >= 0; i--) {
                const road = project.scene.roads[i];
                const angle = -road.rotation * Math.PI / 180;
                const dx = point.x - road.x;
                const dy = point.y - road.y;
                const localX = (dx * Math.cos(angle)) - (dy * Math.sin(angle));
                const localY = (dx * Math.sin(angle)) + (dy * Math.cos(angle));
                const geometry = roadGeometry(road);
                if (road.type === 'curve') {
                    const points = roadCurvePolyline(road, 0, 80);
                    const tolerance = (geometry.width / 2) + Math.max(geometry.leftEdgeWidth, geometry.rightEdgeWidth) + 8;
                    for (let pointIndex = 1; pointIndex < points.length; pointIndex++) {
                        if (distanceToSegment({ x: localX, y: localY }, points[pointIndex - 1], points[pointIndex]) <= tolerance) {
                            return { kind: 'road', id: road.id };
                        }
                    }
                    continue;
                }
                const top = -(geometry.width / 2) - geometry.leftEdgeWidth - 8;
                const bottom = (geometry.width / 2) + geometry.rightEdgeWidth + 8;
                if (Math.abs(localX) <= geometry.length / 2 + 8 && localY >= top && localY <= bottom) {
                    return { kind: 'road', id: road.id };
                }
            }
        }
        return null;
    }

    function distance(a, b) {
        return Math.sqrt(Math.pow(a.x - b.x, 2) + Math.pow(a.y - b.y, 2));
    }

    function distanceToSegment(point, start, end) {
        return segmentProjection(point, start, end).distance;
    }

    function segmentProjection(point, start, end) {
        const dx = end.x - start.x;
        const dy = end.y - start.y;
        const lengthSquared = (dx * dx) + (dy * dy);
        if (!lengthSquared) return { distance: distance(point, start), progress: 0 };
        const progress = Math.max(0, Math.min(1, (((point.x - start.x) * dx) + ((point.y - start.y) * dy)) / lengthSquared));
        const closest = { x: start.x + (progress * dx), y: start.y + (progress * dy) };
        return { distance: distance(point, closest), progress: progress };
    }

    function actorPathHit(point) {
        if (project.layers.paths === false) return null;
        const tolerance = clamp(12 / camera.zoom, 8, 22);
        for (let actorIndex = project.actors.length - 1; actorIndex >= 0; actorIndex--) {
            const actor = project.actors[actorIndex];
            for (let frameIndex = actor.keyframes.length - 1; frameIndex >= 0; frameIndex--) {
                const frame = actor.keyframes[frameIndex];
                if (distance(point, frame) <= tolerance + 3) {
                    return { actorId: actor.id, frameIndex: frameIndex, time: frame.time, node: true };
                }
            }
        }
        for (let actorIndex = project.actors.length - 1; actorIndex >= 0; actorIndex--) {
            const actor = project.actors[actorIndex];
            for (let frameIndex = actor.keyframes.length - 2; frameIndex >= 0; frameIndex--) {
                const from = actor.keyframes[frameIndex];
                const to = actor.keyframes[frameIndex + 1];
                const projection = segmentProjection(point, from, to);
                if (projection.distance <= tolerance) {
                    return {
                        actorId: actor.id,
                        frameIndex: frameIndex,
                        time: from.time + ((to.time - from.time) * projection.progress),
                        node: false
                    };
                }
            }
        }
        return null;
    }

    function curveRoadHandleHit(point, road) {
        if (!road || road.type !== 'curve') return null;
        const handles = roadCurveHandles(road);
        const names = ['start', 'control1', 'control2', 'end'];
        for (let index = 0; index < names.length; index++) {
            if (distance(point, handles[names[index]]) <= 16) return names[index];
        }
        return null;
    }

    function actorResizeHandleHit(point, actor, position) {
        const handles = actorResizeHandles(actor, position);
        for (let index = 0; index < handles.length; index++) {
            if (distance(point, handles[index]) <= 16) return handles[index];
        }
        return null;
    }

    function selectedActor() {
        return selected && selected.kind === 'actor' ? project.actors.find(function (actor) { return actor.id === selected.id; }) : null;
    }

    function updateActorRotationControls() {
        const actor = selectedActor();
        if (!actor) return;
        const position = actorPosition(actor, currentTime);
        const rotation = normalizeAngle(position ? position.rotation : 0);
        el.rtActorRotation.value = Math.round(rotation);
        el.rtActorRotationRange.value = Math.round(rotation);
    }

    function setActorRotation(value) {
        const actor = selectedActor();
        if (!actor) return;
        const center = cameraWorldCenter();
        const position = actorPosition(actor, currentTime) || { x: center.x, y: center.y, rotation: 0 };
        upsertKeyframe(actor, currentTime, position.x, position.y, normalizeAngle(value), { manualRotation: true });
        updateActorRotationControls();
        draw();
        renderKeyframes();
        renderSummary();
        markDirty();
    }

    function selectedEvent() {
        return selected && selected.kind === 'event' ? project.events.find(function (event) { return event.id === selected.id; }) : null;
    }

    function selectedRoad() {
        return selected && selected.kind === 'road' ? project.scene.roads.find(function (road) { return road.id === selected.id; }) : null;
    }

    function createRoad(overrides) {
        const count = project.scene.roads.length + 1;
        return Object.assign({
            id: uid('road'), type: 'straight', name: 'Calle ' + count,
            x: canvas.width / 2, y: canvas.height / 2,
            lengthMeters: 30, laneWidthMeters: 3.5, lanes: 2, rotation: 0,
            direction: 'one_way', centerLine: 'solid', leftEdge: 'none', rightEdge: 'none',
            surface: 'asphalt', curve: defaultCurve()
        }, overrides || {});
    }

    function addRoad(type) {
        pushHistory();
        const offset = (project.scene.roads.length % 5) * 22;
        const isCurve = type === 'curve';
        const center = cameraWorldCenter();
        const road = createRoad({
            type: isCurve ? 'curve' : 'straight',
            name: (isCurve ? 'Curva ' : 'Calle ') + (project.scene.roads.length + 1),
            x: center.x,
            y: center.y + offset
        });
        project.scene.roads.push(road);
        selected = { kind: 'road', id: road.id };
        setTool('select');
        renderAll();
        markDirty();
    }

    function createEditableCrossing() {
        pushHistory();
        project.scene.roads = [
            createRoad({ name: 'Avenida principal', x: 600, y: 420, lengthMeters: 60, lanes: 2, rotation: 0, direction: 'two_way', centerLine: 'solid', leftEdge: 'sidewalk', rightEdge: 'sidewalk' }),
            createRoad({ name: 'Calle transversal', x: 650, y: 350, lengthMeters: 38, lanes: 2, rotation: 90, direction: 'two_way', centerLine: 'dashed' })
        ];
        selected = { kind: 'road', id: project.scene.roads[0].id };
        renderAll();
        markDirty();
    }

    function addActor(type) {
        const meta = ACTOR_META[type] || ACTOR_META.automovil;
        pushHistory();
        const sameType = project.actors.filter(function (actor) { return actor.type === type; }).length;
        const offset = project.actors.length * 28;
        const center = cameraWorldCenter();
        const actor = {
            id: uid('actor'), type: type, name: meta.label + ' ' + (sameType + 1),
            image: (config.actorImages || {})[type] || '', color: meta.color, speedKmh: 0,
            scaleX: 1, scaleY: 1,
            keyframes: [{ time: currentTime, x: center.x + offset, y: center.y + (offset % 180), rotation: 0 }]
        };
        project.actors.push(actor);
        getImage(actor.image);
        selected = { kind: 'actor', id: actor.id };
        setTool('select');
        renderAll();
        markDirty();
    }

    function addEvent(code, point) {
        pushHistory();
        const meta = EVENT_META[code];
        const event = {
            id: uid('event'), code: code, x: point.x, y: point.y, time: currentTime,
            description: meta.label + ' en la hipótesis actual.'
        };
        project.events.push(event);
        selected = { kind: 'event', id: event.id };
        pendingEvent = null;
        document.querySelectorAll('[data-add-event]').forEach(function (button) { button.classList.remove('active'); });
        el.rtPlacementHint.hidden = true;
        setTool('select');
        renderAll();
        markDirty();
    }

    function addPathPoint(point) {
        const actor = selectedActor();
        if (!actor) { toast('Selecciona primero el participante cuya ruta deseas trazar.', true); return; }
        pushHistory();
        const previous = actorPosition(actor, currentTime) || actor.keyframes[actor.keyframes.length - 1] || { x: point.x - 50, y: point.y, rotation: 0 };
        const rotation = Math.atan2(point.y - previous.y, point.x - previous.x) * 180 / Math.PI;
        upsertKeyframe(actor, currentTime, point.x, point.y, rotation);
        recalculateRotations(actor);
        const next = Math.min(project.metadata.duration, Math.round((currentTime + 1) * 10) / 10);
        setCurrentTime(next);
        renderAll();
        markDirty();
    }

    function deleteSelection() {
        if (!selected) return;
        pushHistory();
        if (selected.kind === 'actor') project.actors = project.actors.filter(function (actor) { return actor.id !== selected.id; });
        if (selected.kind === 'event') project.events = project.events.filter(function (event) { return event.id !== selected.id; });
        if (selected.kind === 'road') project.scene.roads = project.scene.roads.filter(function (road) { return road.id !== selected.id; });
        selected = null;
        renderAll();
        markDirty();
    }

    function setCurrentTime(value) {
        currentTime = clamp(value, 0, project.metadata.duration);
        el.rtTimeline.value = currentTime;
        el.rtCurrentTime.value = currentTime.toFixed(1);
        draw();
        renderKeyframes();
        updateActorRotationControls();
    }

    function play(forRecording) {
        if (playing) { pause(); return; }
        if (currentTime >= project.metadata.duration - .01) setCurrentTime(0);
        playing = true;
        lastFrameAt = performance.now();
        el.rtPlay.innerHTML = '<i class="fas fa-pause"></i>';
        el.rtPlaybackBadge.textContent = recording ? 'GRABANDO' : 'REPRODUCIENDO';
        el.rtPlaybackBadge.classList.add('playing');
        animationId = requestAnimationFrame(playbackFrame);
        if (!forRecording) renderInspector();
    }

    function pause() {
        playing = false;
        if (animationId) cancelAnimationFrame(animationId);
        animationId = null;
        el.rtPlay.innerHTML = '<i class="fas fa-play"></i>';
        el.rtPlaybackBadge.textContent = recording ? 'GRABANDO' : 'PAUSA';
        if (!recording) el.rtPlaybackBadge.classList.remove('playing');
    }

    function playbackFrame(now) {
        if (!playing) return;
        const elapsed = Math.min(.1, (now - lastFrameAt) / 1000);
        lastFrameAt = now;
        const next = currentTime + (elapsed * playbackRate);
        if (next >= project.metadata.duration) {
            setCurrentTime(project.metadata.duration);
            pause();
            if (recording && recording.state !== 'inactive') {
                setTimeout(function () { if (recording && recording.state !== 'inactive') recording.stop(); }, 220);
            }
            return;
        }
        setCurrentTime(next);
        animationId = requestAnimationFrame(playbackFrame);
    }

    function renderAll() {
        draw();
        renderInspector();
        renderActorList();
        renderTimelineEvents();
        renderSummary();
        el.rtEmptyState.hidden = project.actors.length > 0;
    }

    function renderInspector() {
        const actor = selectedActor();
        const event = selectedEvent();
        const road = selectedRoad();
        el.rtNoSelection.hidden = Boolean(actor || event || road);
        el.rtActorInspector.hidden = !actor;
        el.rtEventInspector.hidden = !event;
        el.rtRoadInspector.hidden = !road;
        if (actor) {
            el.rtActorSwatch.style.background = actor.color;
            el.rtActorTitle.textContent = actor.name;
            el.rtActorName.value = actor.name;
            el.rtActorColor.value = actor.color;
            el.rtActorSpeed.value = actor.speedKmh;
            el.rtActorLength.value = Math.round(actorScale(actor, 'x') * 100);
            el.rtActorWidth.value = Math.round(actorScale(actor, 'y') * 100);
            updateActorRotationControls();
            renderKeyframes();
        }
        if (event) {
            el.rtEventCode.textContent = event.code;
            el.rtEventCode.style.background = EVENT_META[event.code].color;
            el.rtEventTitle.textContent = EVENT_META[event.code].label;
            el.rtEventTime.max = project.metadata.duration;
            el.rtEventTime.value = event.time.toFixed(1);
            el.rtEventDescription.value = event.description;
        }
        if (road) {
            el.rtRoadTitle.textContent = road.name;
            el.rtRoadName.value = road.name;
            el.rtRoadSurface.value = road.surface;
            el.rtSurfacePreview.dataset.surface = road.surface;
            el.rtRoadLaneCount.textContent = road.lanes;
            el.rtRoadLanesLabel.textContent = road.direction === 'two_way'
                ? 'Carriles totales (' + (road.lanes / 2) + ' por sentido)'
                : 'Carriles';
            el.rtRoadDirection.value = road.direction;
            el.rtCenterLineField.hidden = road.direction !== 'two_way';
            el.rtRoadCenterLine.value = road.centerLine;
            el.rtRoadLengthField.hidden = road.type === 'curve';
            el.rtCurveHelp.hidden = road.type !== 'curve';
            el.rtRoadLength.value = Number(road.lengthMeters).toFixed(1).replace('.0', '');
            el.rtRoadLaneWidth.value = Number(road.laneWidthMeters).toFixed(1);
            el.rtRoadRotation.value = Math.round(normalizeAngle(road.rotation));
            el.rtRoadLeftEdge.value = road.leftEdge;
            el.rtRoadRightEdge.value = road.rightEdge;
            el.rtRemoveLane.disabled = road.lanes <= (road.direction === 'two_way' ? 2 : 1);
            el.rtAddLane.disabled = road.lanes >= 12;
        }
    }

    function renderKeyframes() {
        const actor = selectedActor();
        el.rtKeyframeList.innerHTML = '';
        if (!actor) return;
        actor.keyframes.forEach(function (frame, index) {
            const row = document.createElement('div');
            row.className = 'rt-keyframe' + (Math.abs(frame.time - currentTime) < .06 ? ' active' : '');
            row.innerHTML = '<strong>' + frame.time.toFixed(1) + ' s</strong><span>Fotograma ' + (index + 1) + ' · ' + Math.round(frame.x) + ', ' + Math.round(frame.y) + '</span><button type="button" title="Eliminar fotograma"><i class="fas fa-times"></i></button>';
            row.addEventListener('click', function (event) {
                if (event.target.closest('button')) return;
                pause(); setCurrentTime(frame.time);
            });
            row.querySelector('button').addEventListener('click', function () {
                if (actor.keyframes.length <= 1) { toast('El participante necesita al menos una posición.', true); return; }
                pushHistory(); actor.keyframes.splice(index, 1); renderAll(); markDirty();
            });
            el.rtKeyframeList.appendChild(row);
        });
    }

    function renderActorList() {
        el.rtActorList.innerHTML = '';
        el.rtActorCount.textContent = project.actors.length;
        project.actors.forEach(function (actor) {
            const row = document.createElement('div');
            row.className = 'rt-actor-item' + (selected && selected.kind === 'actor' && selected.id === actor.id ? ' active' : '');
            row.innerHTML = '<span style="background:' + actor.color + '"></span><img src="' + actor.image + '" alt=""><div><strong></strong><small></small></div>';
            row.querySelector('strong').textContent = actor.name;
            row.querySelector('small').textContent = actor.keyframes.length + ' fotogramas · ' + actor.speedKmh + ' km/h';
            row.addEventListener('click', function () { selected = { kind: 'actor', id: actor.id }; setTool('select'); renderAll(); });
            el.rtActorList.appendChild(row);
        });
    }

    function renderTimelineEvents() {
        el.rtEventTicks.innerHTML = '';
        project.events.forEach(function (event) {
            const tick = document.createElement('span');
            tick.className = 'rt-event-tick';
            tick.dataset.code = event.code;
            tick.style.left = ((event.time / project.metadata.duration) * 100) + '%';
            tick.style.background = EVENT_META[event.code].color;
            el.rtEventTicks.appendChild(tick);
        });
    }

    function renderSummary() {
        const actor = selectedActor();
        let totalPixels = 0;
        let frameCount = 0;
        if (actor) {
            frameCount = actor.keyframes.length;
            for (let i = 0; i < actor.keyframes.length - 1; i++) totalPixels += distance(actor.keyframes[i], actor.keyframes[i + 1]);
        } else {
            project.actors.forEach(function (item) { frameCount += item.keyframes.length; });
        }
        el.rtDistance.textContent = actor ? (totalPixels / project.metadata.pixelsPerMeter).toFixed(1) + ' m' : '—';
        el.rtKeyframeCount.textContent = frameCount;
        el.rtEventCount.textContent = project.events.length;
    }

    function setTool(nextTool) {
        tool = nextTool;
        pendingEvent = null;
        el.rtPlacementHint.hidden = true;
        document.querySelectorAll('[data-add-event]').forEach(function (button) { button.classList.remove('active'); });
        document.querySelectorAll('[data-tool]').forEach(function (button) { button.classList.toggle('active', button.dataset.tool === tool); });
        canvas.classList.toggle('is-path', tool === 'path');
        canvas.classList.toggle('is-pan', tool === 'pan');
        canvas.classList.remove('is-path-hover', 'is-keyframe-hover');
        el.rtModeHelp.innerHTML = tool === 'path'
            ? '<i class="fas fa-route"></i> Sitúa la línea de tiempo y haz clic para añadir cada posición de la trayectoria.'
            : (tool === 'pan'
                ? '<i class="fas fa-hand-paper"></i> Arrastra el lienzo para desplazarte. La rueda controla el zoom.'
                : '<i class="fas fa-info-circle"></i> Selecciona y arrastra participantes, calles o puntos técnicos.');
    }

    function toast(message, isError) {
        const node = document.createElement('div');
        node.className = 'rt-toast' + (isError ? ' is-error' : '');
        node.textContent = message;
        document.body.appendChild(node);
        setTimeout(function () { node.remove(); }, 3600);
    }

    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url; link.download = filename; document.body.appendChild(link); link.click(); link.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }

    function safeFilename(value) {
        return (value || 'reconstruccion-2d').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
    }

    function exportJson() {
        syncMetadataFromInputs();
        const blob = new Blob([JSON.stringify(project, null, 2)], { type: 'application/json;charset=utf-8' });
        downloadBlob(blob, safeFilename(project.metadata.name) + '.json');
        toast('Proyecto JSON exportado.');
    }

    function exportVideo() {
        if (!canvas.captureStream || !window.MediaRecorder) {
            toast('Este navegador no permite grabar el lienzo. Usa una versión reciente de Chrome o Edge.', true);
            return;
        }
        if (recording) return;
        syncMetadataFromInputs();
        pause();
        recordingCameraBackup = { zoom: camera.zoom, panX: camera.panX, panY: camera.panY };
        fitScene();
        const mimeTypes = ['video/mp4;codecs=avc1.42E01E', 'video/mp4', 'video/webm;codecs=vp9', 'video/webm;codecs=vp8', 'video/webm'];
        const mimeType = mimeTypes.find(function (type) { return MediaRecorder.isTypeSupported(type); }) || '';
        const stream = canvas.captureStream(30);
        const chunks = [];
        try {
            recording = new MediaRecorder(stream, mimeType ? { mimeType: mimeType, videoBitsPerSecond: 5000000 } : undefined);
        } catch (error) {
            recording = null;
            Object.assign(camera, recordingCameraBackup);
            recordingCameraBackup = null;
            draw();
            toast('No se pudo iniciar la exportación de video en este navegador.', true);
            return;
        }
        recording.ondataavailable = function (event) { if (event.data && event.data.size) chunks.push(event.data); };
        recording.onerror = function () { toast('Ocurrió un error al crear el video.', true); };
        recording.onstop = function () {
            const type = recording.mimeType || 'video/webm';
            const blob = new Blob(chunks, { type: type });
            const extension = type.indexOf('mp4') !== -1 ? 'mp4' : 'webm';
            downloadBlob(blob, safeFilename(project.metadata.name) + '.' + extension);
            stream.getTracks().forEach(function (track) { track.stop(); });
            recording = null;
            el.rtExportVideo.disabled = false;
            el.rtSaveStatus.classList.remove('is-recording');
            el.rtPlaybackBadge.textContent = 'PAUSA';
            el.rtPlaybackBadge.classList.remove('playing');
            if (recordingCameraBackup) {
                Object.assign(camera, recordingCameraBackup);
                recordingCameraBackup = null;
                draw();
            }
            saveProject(false);
            toast('Video ' + extension.toUpperCase() + ' exportado con la reconstrucción completa.');
        };
        el.rtExportVideo.disabled = true;
        el.rtSaveStatus.classList.add('is-recording');
        el.rtSaveStatus.innerHTML = '<i class="fas fa-circle"></i> Grabando video…';
        setCurrentTime(0);
        playbackRate = 1;
        el.rtPlaybackSpeed.value = '1';
        recording.start(250);
        play(true);
    }

    canvas.addEventListener('pointerdown', function (event) {
        if (playing || recording) return;
        const screenPoint = canvasScreenPoint(event);
        const point = canvasPoint(event);
        if (tool === 'pan' || event.button === 1 || event.button === 2) {
            event.preventDefault();
            dragging = { kind: 'camera', startX: screenPoint.x, startY: screenPoint.y, panX: camera.panX, panY: camera.panY };
            canvas.setPointerCapture(event.pointerId);
            canvas.classList.add('is-dragging');
            return;
        }
        if (pendingEvent) { addEvent(pendingEvent, point); return; }
        if (tool === 'path') { addPathPoint(point); return; }
        const pathHit = actorPathHit(point);
        if (pathHit) {
            selected = { kind: 'actor', id: pathHit.actorId };
            setCurrentTime(pathHit.time);
            if (pathHit.node) {
                pushHistory();
                dragging = { kind: 'keyframe', id: pathHit.actorId, frameIndex: pathHit.frameIndex };
                canvas.setPointerCapture(event.pointerId);
                canvas.classList.add('is-dragging');
            }
            renderAll();
            return;
        }
        const activeRoad = selectedRoad();
        const curveHandle = curveRoadHandleHit(point, activeRoad);
        if (curveHandle) {
            pushHistory();
            dragging = { kind: 'curve-handle', id: activeRoad.id, handle: curveHandle };
            canvas.setPointerCapture(event.pointerId);
            canvas.classList.add('is-dragging');
            return;
        }
        const roadHandle = activeRoad ? roadRotationHandle(activeRoad) : null;
        if (roadHandle && distance(point, roadHandle) <= 16) {
            pushHistory();
            dragging = { kind: 'rotate-road', id: activeRoad.id };
            canvas.setPointerCapture(event.pointerId);
            canvas.classList.add('is-dragging');
            return;
        }
        const activeActor = selectedActor();
        const activePosition = activeActor ? actorPosition(activeActor, currentTime) : null;
        const resizeHandle = activeActor && activePosition ? actorResizeHandleHit(point, activeActor, activePosition) : null;
        if (resizeHandle) {
            pushHistory();
            dragging = {
                kind: 'resize-actor', id: activeActor.id,
                handle: resizeHandle.name
            };
            canvas.setPointerCapture(event.pointerId);
            canvas.classList.add('is-dragging');
            return;
        }
        const handle = activeActor && activePosition ? rotationHandle(activeActor, activePosition) : null;
        if (handle && distance(point, handle) <= 16) {
            pushHistory();
            dragging = { kind: 'rotate', id: activeActor.id };
            canvas.setPointerCapture(event.pointerId);
            canvas.classList.add('is-dragging');
            return;
        }
        const hit = findHit(point);
        selected = hit;
        if (hit) {
            pushHistory();
            dragging = { kind: hit.kind, id: hit.id };
            canvas.setPointerCapture(event.pointerId);
            canvas.classList.add('is-dragging');
        }
        renderAll();
    });

    canvas.addEventListener('pointermove', function (event) {
        const screenPoint = canvasScreenPoint(event);
        const point = canvasPoint(event);
        el.rtCoordinate.textContent = 'x: ' + Math.round(point.x) + ' · y: ' + Math.round(point.y) + ' · ' + (point.x / project.metadata.pixelsPerMeter).toFixed(1) + ' m, ' + (point.y / project.metadata.pixelsPerMeter).toFixed(1) + ' m';
        if (!dragging) {
            const pathHover = tool === 'select' ? actorPathHit(point) : null;
            canvas.classList.toggle('is-path-hover', Boolean(pathHover && !pathHover.node));
            canvas.classList.toggle('is-keyframe-hover', Boolean(pathHover && pathHover.node));
            return;
        }
        if (dragging.kind === 'camera') {
            camera.panX = dragging.panX + (screenPoint.x - dragging.startX);
            camera.panY = dragging.panY + (screenPoint.y - dragging.startY);
        } else if (dragging.kind === 'curve-handle') {
            const road = project.scene.roads.find(function (item) { return item.id === dragging.id; });
            if (road && road.type === 'curve') {
                const dx = point.x - road.x;
                const dy = point.y - road.y;
                const angle = -road.rotation * Math.PI / 180;
                const pixelsPerMeter = project.metadata.pixelsPerMeter || 20;
                const localX = ((dx * Math.cos(angle)) - (dy * Math.sin(angle))) / pixelsPerMeter;
                const localY = ((dx * Math.sin(angle)) + (dy * Math.cos(angle))) / pixelsPerMeter;
                const fields = roadCurveHandles(road)[dragging.handle].fields;
                road.curve[fields[0]] = clamp(localX, -200, 200);
                road.curve[fields[1]] = clamp(localY, -200, 200);
            }
        } else if (dragging.kind === 'keyframe') {
            const actor = project.actors.find(function (item) { return item.id === dragging.id; });
            const frame = actor && actor.keyframes[dragging.frameIndex];
            if (actor && frame) {
                frame.x = point.x;
                frame.y = point.y;
                recalculateRotations(actor);
            }
        } else if (dragging.kind === 'rotate-road') {
            const road = project.scene.roads.find(function (item) { return item.id === dragging.id; });
            if (road) {
                road.rotation = normalizeAngle((Math.atan2(point.y - road.y, point.x - road.x) * 180 / Math.PI) + 90);
                el.rtRoadRotation.value = Math.round(road.rotation);
            }
        } else if (dragging.kind === 'road') {
            const road = project.scene.roads.find(function (item) { return item.id === dragging.id; });
            if (road) { road.x = point.x; road.y = point.y; }
        } else if (dragging.kind === 'rotate') {
            const actor = project.actors.find(function (item) { return item.id === dragging.id; });
            const position = actor ? actorPosition(actor, currentTime) : null;
            if (actor && position) {
                const rotation = (Math.atan2(point.y - position.y, point.x - position.x) * 180 / Math.PI) + 90;
                upsertKeyframe(actor, currentTime, position.x, position.y, normalizeAngle(rotation), { manualRotation: true });
                updateActorRotationControls();
            }
        } else if (dragging.kind === 'resize-actor') {
            const actor = project.actors.find(function (item) { return item.id === dragging.id; });
            const position = actor ? actorPosition(actor, currentTime) : null;
            if (actor && position) {
                const meta = ACTOR_META[actor.type] || ACTOR_META.automovil;
                const angle = -position.rotation * Math.PI / 180;
                const dx = point.x - position.x;
                const dy = point.y - position.y;
                const localX = (dx * Math.cos(angle)) - (dy * Math.sin(angle));
                const localY = (dx * Math.sin(angle)) + (dy * Math.cos(angle));
                if (/[ew]/.test(dragging.handle)) {
                    actor.scaleX = clamp((Math.abs(localX) - 7) / (meta.width / 2), .4, 4);
                }
                if (/[ns]/.test(dragging.handle)) {
                    actor.scaleY = clamp((Math.abs(localY) - 7) / (meta.height / 2), .4, 4);
                }
                el.rtActorLength.value = Math.round(actorScale(actor, 'x') * 100);
                el.rtActorWidth.value = Math.round(actorScale(actor, 'y') * 100);
            }
        } else if (dragging.kind === 'actor') {
            const actor = project.actors.find(function (item) { return item.id === dragging.id; });
            if (actor) {
                const current = actorPosition(actor, currentTime) || { rotation: 0 };
                upsertKeyframe(actor, currentTime, point.x, point.y, current.rotation);
                recalculateRotations(actor);
            }
        } else {
            const eventItem = project.events.find(function (item) { return item.id === dragging.id; });
            if (eventItem) { eventItem.x = point.x; eventItem.y = point.y; }
        }
        draw();
    });

    function endDrag() {
        if (!dragging) return;
        const changedProject = dragging.kind !== 'camera';
        dragging = null;
        canvas.classList.remove('is-dragging');
        renderAll();
        if (changedProject) markDirty();
    }
    canvas.addEventListener('pointerup', endDrag);
    canvas.addEventListener('pointercancel', endDrag);
    canvas.addEventListener('pointerleave', function () {
        el.rtCoordinate.textContent = 'x: — · y: —';
        canvas.classList.remove('is-path-hover', 'is-keyframe-hover');
    });
    canvas.addEventListener('contextmenu', function (event) { event.preventDefault(); });
    canvas.addEventListener('wheel', function (event) {
        event.preventDefault();
        const factor = event.deltaY < 0 ? 1.13 : .885;
        setZoom(camera.zoom * factor, canvasScreenPoint(event));
    }, { passive: false });

    document.querySelectorAll('[data-add-actor]').forEach(function (button) {
        button.addEventListener('click', function () { pause(); addActor(button.dataset.addActor); });
    });

    document.querySelectorAll('[data-add-event]').forEach(function (button) {
        button.addEventListener('click', function () {
            pause(); setTool('select'); pendingEvent = button.dataset.addEvent;
            button.classList.add('active');
            el.rtPlacementHint.hidden = false;
            el.rtPlacementHint.textContent = 'Haz clic para ubicar ' + pendingEvent + ' en el segundo ' + currentTime.toFixed(1);
            el.rtModeHelp.innerHTML = '<i class="fas fa-crosshairs"></i> Ubica ' + EVENT_META[pendingEvent].label + ' en la escena.';
        });
    });

    document.querySelectorAll('[data-tool]').forEach(function (button) {
        button.addEventListener('click', function () { pause(); setTool(button.dataset.tool); });
    });

    document.querySelectorAll('[data-add-road]').forEach(function (button) {
        button.addEventListener('click', function () { pause(); addRoad(button.dataset.addRoad === 'curva' ? 'curve' : 'straight'); });
    });

    document.querySelectorAll('[data-road-preset="cruce"]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (project.scene.roads.length && !window.confirm('¿Reemplazar las calles actuales por un cruce editable?')) return;
            pause(); createEditableCrossing();
        });
    });

    document.querySelectorAll('[data-clear-roads]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (project.scene.roads.length && !window.confirm('¿Quitar todas las calles de la escena?')) return;
            pushHistory();
            project.scene.roads = [];
            if (selected && selected.kind === 'road') selected = null;
            renderAll();
            markDirty();
        });
    });

    document.querySelectorAll('[data-layer]').forEach(function (input) {
        input.addEventListener('change', function () { project.layers[input.dataset.layer] = input.checked; draw(); markDirty(); });
    });

    [el.rtProjectName, el.rtHypothesis].forEach(function (input) {
        input.addEventListener('input', function () { syncMetadataFromInputs(); draw(); markDirty(); });
    });
    el.rtDuration.addEventListener('change', function () {
        pushHistory(); syncMetadataFromInputs();
        project.actors.forEach(function (actor) { actor.keyframes.forEach(function (frame) { frame.time = clamp(frame.time, 0, project.metadata.duration); }); });
        project.events.forEach(function (event) { event.time = clamp(event.time, 0, project.metadata.duration); });
        setCurrentTime(Math.min(currentTime, project.metadata.duration)); syncInputsFromProject(); renderAll(); markDirty();
    });
    el.rtScale.addEventListener('change', function () { syncMetadataFromInputs(); renderAll(); markDirty(); });

    el.rtTimeline.addEventListener('input', function () { pause(); setCurrentTime(el.rtTimeline.value); });
    el.rtCurrentTime.addEventListener('change', function () { pause(); setCurrentTime(el.rtCurrentTime.value); });
    el.rtPlaybackSpeed.addEventListener('change', function () { playbackRate = clamp(el.rtPlaybackSpeed.value, .1, 4); });
    el.rtPlay.addEventListener('click', function () { play(false); });
    el.rtReset.addEventListener('click', function () { pause(); setCurrentTime(0); });
    el.rtPrevFrame.addEventListener('click', function () { pause(); setCurrentTime(currentTime - (1 / 30)); });
    el.rtNextFrame.addEventListener('click', function () { pause(); setCurrentTime(currentTime + (1 / 30)); });
    el.rtUndo.addEventListener('click', undo);
    el.rtDelete.addEventListener('click', deleteSelection);
    el.rtFit.addEventListener('click', function () { fitScene(); toast('Toda la escena quedó ajustada al lienzo.'); });
    el.rtZoomOut.addEventListener('click', function () { setZoom(camera.zoom / 1.2); });
    el.rtZoomIn.addEventListener('click', function () { setZoom(camera.zoom * 1.2); });
    el.rtFullscreen.addEventListener('click', toggleFullscreen);
    document.addEventListener('fullscreenchange', function () {
        if (document.fullscreenElement !== root) {
            fullscreenFallback = false;
            root.classList.remove('is-focus-mode');
            document.body.classList.remove('rt-focus-lock');
        }
        updateFullscreenButton();
        setTimeout(draw, 40);
    });
    el.rtStartPath.addEventListener('click', function () { setTool('path'); });
    el.rtAddKeyframe.addEventListener('click', function () {
        const actor = selectedActor();
        if (!actor) return;
        pushHistory();
        const center = cameraWorldCenter();
        const position = actorPosition(actor, currentTime) || { x: center.x, y: center.y, rotation: 0 };
        upsertKeyframe(actor, currentTime, position.x, position.y, position.rotation);
        renderAll(); markDirty();
    });

    el.rtActorName.addEventListener('change', function () { const actor = selectedActor(); if (!actor) return; pushHistory(); actor.name = (el.rtActorName.value || ACTOR_META[actor.type].label).trim(); renderAll(); markDirty(); });
    el.rtActorColor.addEventListener('input', function () { const actor = selectedActor(); if (!actor) return; actor.color = el.rtActorColor.value; renderAll(); markDirty(); });
    el.rtActorSpeed.addEventListener('change', function () { const actor = selectedActor(); if (!actor) return; pushHistory(); actor.speedKmh = clamp(el.rtActorSpeed.value, 0, 300); renderAll(); markDirty(); });
    el.rtActorLength.addEventListener('change', function () {
        const actor = selectedActor(); if (!actor) return;
        pushHistory(); actor.scaleX = clamp(Number(el.rtActorLength.value) / 100, .4, 4); renderAll(); markDirty();
    });
    el.rtActorWidth.addEventListener('change', function () {
        const actor = selectedActor(); if (!actor) return;
        pushHistory(); actor.scaleY = clamp(Number(el.rtActorWidth.value) / 100, .4, 4); renderAll(); markDirty();
    });
    el.rtActorRotation.addEventListener('change', function () { if (!selectedActor()) return; pushHistory(); setActorRotation(el.rtActorRotation.value); });
    el.rtActorRotationRange.addEventListener('pointerdown', function () { if (selectedActor()) pushHistory(); });
    el.rtActorRotationRange.addEventListener('input', function () { setActorRotation(el.rtActorRotationRange.value); });
    document.querySelectorAll('[data-rotate-actor]').forEach(function (button) {
        button.addEventListener('click', function () {
            const actor = selectedActor();
            if (!actor) return;
            const position = actorPosition(actor, currentTime) || { rotation: 0 };
            pushHistory();
            setActorRotation(position.rotation + Number(button.dataset.rotateActor));
        });
    });
    el.rtRoadName.addEventListener('change', function () {
        const road = selectedRoad(); if (!road) return;
        pushHistory(); road.name = (el.rtRoadName.value || 'Calle').trim(); renderAll(); markDirty();
    });
    el.rtRoadSurface.addEventListener('change', function () {
        const road = selectedRoad(); if (!road) return;
        pushHistory(); road.surface = el.rtRoadSurface.value; el.rtSurfacePreview.dataset.surface = road.surface; renderAll(); markDirty();
    });
    el.rtRemoveLane.addEventListener('click', function () {
        const road = selectedRoad(); if (!road) return;
        const step = road.direction === 'two_way' ? 2 : 1;
        const minimum = road.direction === 'two_way' ? 2 : 1;
        if (road.lanes <= minimum) return;
        pushHistory(); road.lanes = Math.max(minimum, road.lanes - step); renderAll(); markDirty();
    });
    el.rtAddLane.addEventListener('click', function () {
        const road = selectedRoad(); if (!road || road.lanes >= 12) return;
        pushHistory(); road.lanes = Math.min(12, road.lanes + (road.direction === 'two_way' ? 2 : 1)); renderAll(); markDirty();
    });
    el.rtRoadDirection.addEventListener('change', function () {
        const road = selectedRoad(); if (!road) return;
        pushHistory();
        road.direction = el.rtRoadDirection.value === 'two_way' ? 'two_way' : 'one_way';
        if (road.direction === 'two_way') road.lanes = Math.max(2, road.lanes + (road.lanes % 2));
        renderAll(); markDirty();
    });
    el.rtRoadCenterLine.addEventListener('change', function () {
        const road = selectedRoad(); if (!road) return;
        pushHistory(); road.centerLine = el.rtRoadCenterLine.value; renderAll(); markDirty();
    });
    el.rtRoadLength.addEventListener('change', function () {
        const road = selectedRoad(); if (!road || road.type === 'curve') return;
        pushHistory(); road.lengthMeters = clamp(el.rtRoadLength.value, 3, 200); renderAll(); markDirty();
    });
    el.rtResetCurve.addEventListener('click', function () {
        const road = selectedRoad(); if (!road || road.type !== 'curve') return;
        pushHistory(); road.curve = defaultCurve(); renderAll(); markDirty();
    });
    el.rtRoadLaneWidth.addEventListener('change', function () {
        const road = selectedRoad(); if (!road) return;
        pushHistory(); road.laneWidthMeters = clamp(el.rtRoadLaneWidth.value, 2, 8); renderAll(); markDirty();
    });
    el.rtRoadRotation.addEventListener('change', function () {
        const road = selectedRoad(); if (!road) return;
        pushHistory(); road.rotation = normalizeAngle(el.rtRoadRotation.value); renderAll(); markDirty();
    });
    el.rtRoadLeftEdge.addEventListener('change', function () {
        const road = selectedRoad(); if (!road) return;
        pushHistory(); road.leftEdge = el.rtRoadLeftEdge.value; renderAll(); markDirty();
    });
    el.rtRoadRightEdge.addEventListener('change', function () {
        const road = selectedRoad(); if (!road) return;
        pushHistory(); road.rightEdge = el.rtRoadRightEdge.value; renderAll(); markDirty();
    });
    el.rtEventTime.addEventListener('change', function () { const event = selectedEvent(); if (!event) return; pushHistory(); event.time = clamp(el.rtEventTime.value, 0, project.metadata.duration); setCurrentTime(event.time); renderAll(); markDirty(); });
    el.rtEventDescription.addEventListener('change', function () { const event = selectedEvent(); if (!event) return; pushHistory(); event.description = el.rtEventDescription.value.trim(); renderAll(); markDirty(); });

    el.rtSaveProject.addEventListener('click', function () { saveProject(true); });
    el.rtExportProject.addEventListener('click', exportJson);
    el.rtExportVideo.addEventListener('click', exportVideo);
    el.rtImportProject.addEventListener('click', function () { el.rtImportInput.click(); });
    el.rtImportInput.addEventListener('change', function () {
        const file = el.rtImportInput.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function () {
            try {
                pushHistory(); project = normalizeProject(JSON.parse(reader.result)); selected = null; currentTime = 0;
                preloadImages(); syncInputsFromProject(); renderAll(); fitScene(); saveProject(false); toast('Proyecto importado correctamente.');
            } catch (error) { toast(error.message || 'No se pudo importar el proyecto.', true); }
            el.rtImportInput.value = '';
        };
        reader.readAsText(file);
    });

    el.rtNewProject.addEventListener('click', function () {
        if (!window.confirm('¿Iniciar un proyecto nuevo? El borrador actual seguirá disponible solo si ya fue exportado.')) return;
        pause(); pushHistory(); project = blankProject(); selected = null; currentTime = 0; history.length = 0;
        camera.zoom = 1; camera.panX = 0; camera.panY = 0;
        syncInputsFromProject(); renderAll(); saveProject(false); toast('Proyecto nuevo listo.');
    });

    window.addEventListener('keydown', function (event) {
        const typing = /INPUT|TEXTAREA|SELECT/.test(document.activeElement && document.activeElement.tagName);
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); saveProject(true); }
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z' && !typing) { event.preventDefault(); undo(); }
        if ((event.key === 'Delete' || event.key === 'Backspace') && !typing) { event.preventDefault(); deleteSelection(); }
        if (event.key === 'Escape') {
            if (fullscreenFallback) toggleFullscreen();
            pendingEvent = null; setTool('select');
        }
        if (event.code === 'Space' && !typing) { event.preventDefault(); play(false); }
        if (!typing && (event.key === '+' || event.key === '=')) { event.preventDefault(); setZoom(camera.zoom * 1.2); }
        if (!typing && event.key === '-') { event.preventDefault(); setZoom(camera.zoom / 1.2); }
        if (!typing && event.key === '0') { event.preventDefault(); fitScene(); }
        if (!typing && (event.key.toLowerCase() === 'q' || event.key.toLowerCase() === 'e') && (selectedActor() || selectedRoad())) {
            event.preventDefault();
            pushHistory();
            const delta = event.key.toLowerCase() === 'q' ? -15 : 15;
            const actor = selectedActor();
            if (actor) {
                const position = actorPosition(actor, currentTime) || { rotation: 0 };
                setActorRotation(position.rotation + delta);
            } else {
                const road = selectedRoad();
                road.rotation = normalizeAngle(road.rotation + delta);
                renderAll(); markDirty();
            }
        }
    });

    preloadImages();
    syncInputsFromProject();
    setCurrentTime(0);
    setTool('select');
    updateFullscreenButton();
    renderAll();
    el.rtUndo.disabled = true;
})();
