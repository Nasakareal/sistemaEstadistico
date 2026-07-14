window.CroquisEditor = (function () {
    class CroquisEditor {
        constructor(options) {
            this.canvas = options.canvas;
            this.ctx = options.ctx;
            this.elementos = options.elementos || [];
            this.assets = options.assets || {};
            this.onChange = options.onChange || function () { };
            this.onSelectionChange = options.onSelectionChange || function () { };

            this.dragging = false;
            this.rotating = false;
            this.resizing = false;
            this.editingCurve = null;

            this.offsetX = 0;
            this.offsetY = 0;

            this.resizeStart = null;
            this.rotateStart = null;

            this.clipboard = null;
            this.pasteOffset = 0;

            this.seleccionado = null;

            this.bindEvents();
            this.render();
        }

        bindEvents() {
            this.canvas.addEventListener('mousedown', this.onMouseDown.bind(this));
            this.canvas.addEventListener('mousemove', this.onMouseMove.bind(this));
            this.canvas.addEventListener('mouseup', this.onMouseUp.bind(this));
            this.canvas.addEventListener('mouseleave', this.onMouseUp.bind(this));
            this.canvas.addEventListener('wheel', this.onWheel.bind(this), { passive: false });
            window.addEventListener('keydown', this.onKeyDown.bind(this));
        }

        setElementos(elementos) {
            this.elementos = elementos || [];
            this.clearSelection();
            this.render();
            this.onChange(this.elementos);
        }

        addElement(el) {
            this.clearSelection();
            el.seleccionado = true;
            this.seleccionado = el;
            this.elementos.push(el);
            this.render();
            this.onChange(this.elementos);
            this.onSelectionChange(this.seleccionado);
        }

        removeSelected() {
            if (!this.seleccionado) return;
            this.elementos = this.elementos.filter(el => el.id !== this.seleccionado.id);
            this.seleccionado = null;
            this.render();
            this.onChange(this.elementos);
            this.onSelectionChange(null);
        }

        copySelected() {
            if (!this.seleccionado) {
                return false;
            }

            this.clipboard = JSON.parse(JSON.stringify(this.seleccionado));
            delete this.clipboard.id;
            delete this.clipboard.seleccionado;
            this.pasteOffset = 0;

            return true;
        }

        pasteCopied() {
            if (!this.clipboard) {
                return false;
            }

            this.pasteOffset += 24;
            const raw = JSON.parse(JSON.stringify(this.clipboard));
            raw.id = null;
            raw.x = Number(raw.x ?? 200) + this.pasteOffset;
            raw.y = Number(raw.y ?? 200) + this.pasteOffset;
            const clone = window.CroquisModels.normalize(raw);

            if (!clone) {
                return false;
            }

            this.addElement(clone);
            return true;
        }

        duplicateSelected() {
            return this.copySelected() && this.pasteCopied();
        }

        changeSelectedLanes(delta) {
            if (!this.seleccionado || typeof this.seleccionado.carriles === 'undefined') {
                return false;
            }

            this.seleccionado.carriles = Math.max(1, this.seleccionado.carriles + delta);
            this.render();
            this.onChange(this.elementos);
            this.onSelectionChange(this.seleccionado);

            return true;
        }

        setSelectedRoadEdge(side, type) {
            const roadTypes = ['calle', 'curva', 'cruce', 'entronque', 'glorieta'];
            if (!this.seleccionado || !roadTypes.includes(this.seleccionado.tipo)) {
                return false;
            }

            const field = side === 'izquierdo' ? 'bordeIzquierdo' : 'bordeDerecho';
            this.seleccionado[field] = ['banqueta', 'camellon'].includes(type) ? type : null;
            this.render();
            this.onChange(this.elementos);
            this.onSelectionChange(this.seleccionado);

            return true;
        }

        clear() {
            this.elementos = [];
            this.seleccionado = null;
            this.render();
            this.onChange(this.elementos);
            this.onSelectionChange(null);
        }

        clearSelection() {
            this.elementos.forEach(el => el.seleccionado = false);
            this.seleccionado = null;
            this.onSelectionChange(null);
        }

        select(el) {
            this.elementos.forEach(item => item.seleccionado = false);
            if (el) {
                el.seleccionado = true;
                this.seleccionado = el;
            } else {
                this.seleccionado = null;
            }
            this.render();
            this.onSelectionChange(this.seleccionado);
        }

        getMousePos(evt) {
            const rect = this.canvas.getBoundingClientRect();
            return {
                x: evt.clientX - rect.left,
                y: evt.clientY - rect.top
            };
        }

        toLocal(el, x, y) {
            const dx = x - el.x;
            const dy = y - el.y;
            const angle = -(el.rotacion || 0) * Math.PI / 180;

            return {
                x: dx * Math.cos(angle) - dy * Math.sin(angle),
                y: dx * Math.sin(angle) + dy * Math.cos(angle)
            };
        }

        getBounds(el) {
            return window.CroquisRenderer.getBounds(el);
        }

        getHandles(el) {
            return window.CroquisRenderer.getHandles(el);
        }

        distance(ax, ay, bx, by) {
            return Math.sqrt(Math.pow(ax - bx, 2) + Math.pow(ay - by, 2));
        }

        distanceToSegment(point, start, end) {
            const dx = end.x - start.x;
            const dy = end.y - start.y;
            const lengthSquared = (dx * dx) + (dy * dy);

            if (lengthSquared === 0) {
                return this.distance(point.x, point.y, start.x, start.y);
            }

            const t = Math.max(0, Math.min(1,
                (((point.x - start.x) * dx) + ((point.y - start.y) * dy)) / lengthSquared
            ));

            return this.distance(point.x, point.y, start.x + (t * dx), start.y + (t * dy));
        }

        scaleCurve(el, source, factor) {
            ['inicioX', 'inicioY', 'control1X', 'control1Y', 'control2X', 'control2Y', 'finX', 'finY']
                .forEach(key => el[key] = source[key] * factor);
        }

        totalRoadWidth(el) {
            return Math.max(1, Number(el.carriles) || 1) * Math.max(1, Number(el.anchoCarril) || 1);
        }

        maxAttachedWidth(el) {
            return window.CroquisRenderer.maxAttachedWidth(el);
        }

        setTotalRoadWidth(el, totalWidth) {
            const carriles = Math.max(1, Number(el.carriles) || 1);
            const minWidth = carriles * 10;
            el.anchoCarril = Math.max(minWidth, totalWidth) / carriles;
        }

        crossHorizontalLength(el) {
            return Number(el.largoHorizontal ?? el.largo ?? 220);
        }

        crossVerticalLength(el) {
            return Number(el.largoVertical ?? el.largo ?? 220);
        }

        isInsideElement(el, x, y) {
            const p = this.toLocal(el, x, y);

            if (['carro', 'vehiculo', 'icono', 'texto', 'camellon', 'banqueta'].includes(el.tipo)) {
                const bounds = this.getBounds(el);
                return Math.abs(p.x) <= bounds.w / 2 && Math.abs(p.y) <= bounds.h / 2;
            }

            if (el.tipo === 'calle') {
                const h = this.totalRoadWidth(el) + (this.maxAttachedWidth(el) * 2);
                return Math.abs(p.x) <= el.largo / 2 && Math.abs(p.y) <= h / 2;
            }

            if (el.tipo === 'curva') {
                const points = window.CroquisRenderer.curvePolyline(el, 0, 64);
                const tolerance = (this.totalRoadWidth(el) / 2) + this.maxAttachedWidth(el) + 5;

                for (let i = 1; i < points.length; i++) {
                    if (this.distanceToSegment(p, points[i - 1], points[i]) <= tolerance) {
                        return true;
                    }
                }

                return false;
            }

            if (el.tipo === 'cruce') {
                const roadW = this.totalRoadWidth(el) + (this.maxAttachedWidth(el) * 2);
                const insideH = Math.abs(p.x) <= this.crossHorizontalLength(el) / 2 && Math.abs(p.y) <= roadW / 2;
                const insideV = Math.abs(p.x) <= roadW / 2 && Math.abs(p.y) <= this.crossVerticalLength(el) / 2;
                return insideH || insideV;
            }

            if (el.tipo === 'entronque') {
                const roadW = (el.carriles * el.anchoCarril) + (this.maxAttachedWidth(el) * 2);
                const base = Math.abs(p.x) <= el.largoBase / 2 && Math.abs(p.y) <= roadW / 2;
                const brazo = Math.abs(p.x) <= roadW / 2 && p.y >= -el.largoBrazo && p.y <= 0;
                return base || brazo;
            }

            if (el.tipo === 'glorieta') {
                const innerRing = Math.max(0, el.radioIsla - window.CroquisRenderer.attachedWidth(el.bordeIzquierdo));
                const outerRing = el.radioIsla + this.totalRoadWidth(el) + window.CroquisRenderer.attachedWidth(el.bordeDerecho);
                const dist = Math.sqrt((p.x * p.x) + (p.y * p.y));
                return dist >= innerRing && dist <= outerRing;
            }

            return false;
        }

        getHandleHit(el, x, y) {
            const p = this.toLocal(el, x, y);
            const handles = this.getHandles(el);

            if (handles.rotate && this.distance(p.x, p.y, handles.rotate.x, handles.rotate.y) <= handles.rotate.r + 4) {
                return 'rotate';
            }

            if (handles.resize && this.distance(p.x, p.y, handles.resize.x, handles.resize.y) <= handles.resize.r + 4) {
                return 'resize';
            }

            for (const name of ['curveStart', 'curveControl1', 'curveControl2', 'curveEnd']) {
                const handle = handles[name];
                if (handle && this.distance(p.x, p.y, handle.x, handle.y) <= handle.r + 4) {
                    return name;
                }
            }

            return null;
        }

        hitTest(x, y) {
            for (let i = this.elementos.length - 1; i >= 0; i--) {
                if (this.isInsideElement(this.elementos[i], x, y)) {
                    return this.elementos[i];
                }
            }
            return null;
        }

        onMouseDown(evt) {
            const pos = this.getMousePos(evt);

            for (let i = this.elementos.length - 1; i >= 0; i--) {
                const el = this.elementos[i];
                const handle = this.getHandleHit(el, pos.x, pos.y);

                if (handle) {
                    this.select(el);

                    if (handle === 'rotate') {
                        this.rotating = true;
                        this.rotateStart = {
                            angle: Math.atan2(pos.y - el.y, pos.x - el.x),
                            rotation: el.rotacion || 0
                        };
                        return;
                    }

                    if (handle === 'resize') {
                        this.resizing = true;
                        this.resizeStart = {
                            mouseX: pos.x,
                            mouseY: pos.y,
                            original: JSON.parse(JSON.stringify(el))
                        };
                        return;
                    }

                    if (handle.startsWith('curve')) {
                        this.editingCurve = handle;
                        return;
                    }
                }
            }

            const found = this.hitTest(pos.x, pos.y);

            if (!found) {
                this.select(null);
                return;
            }

            this.select(found);
            this.dragging = true;
            this.canvas.classList.add('dragging');
            this.offsetX = pos.x - found.x;
            this.offsetY = pos.y - found.y;
        }

        onMouseMove(evt) {
            if (!this.seleccionado) return;

            const pos = this.getMousePos(evt);

            if (this.dragging) {
                this.seleccionado.x = pos.x - this.offsetX;
                this.seleccionado.y = pos.y - this.offsetY;
                this.render();
                this.onChange(this.elementos);
                return;
            }

            if (this.rotating) {
                const angle = Math.atan2(pos.y - this.seleccionado.y, pos.x - this.seleccionado.x);
                const start = this.rotateStart || { angle, rotation: this.seleccionado.rotacion || 0 };
                let deltaAngle = (angle - start.angle) * 180 / Math.PI;

                if (deltaAngle > 180) {
                    deltaAngle -= 360;
                }

                if (deltaAngle < -180) {
                    deltaAngle += 360;
                }

                this.seleccionado.rotacion = Math.round(start.rotation + deltaAngle);
                this.render();
                this.onChange(this.elementos);
                return;
            }

            if (this.resizing) {
                const dx = pos.x - this.resizeStart.mouseX;
                const dy = pos.y - this.resizeStart.mouseY;
                const delta = Math.max(dx, dy);
                const el = this.seleccionado;
                const original = this.resizeStart.original;
                const startLocal = this.toLocal(original, this.resizeStart.mouseX, this.resizeStart.mouseY);
                const currentLocal = this.toLocal(original, pos.x, pos.y);
                const localDx = currentLocal.x - startLocal.x;
                const localDy = currentLocal.y - startLocal.y;

                if (el.tipo === 'carro') {
                    el.ancho = Math.max(25, original.ancho + localDx);
                    el.alto = Math.max(15, original.alto + localDy);
                }

                if (el.tipo === 'vehiculo') {
                    el.ancho = Math.max(20, original.ancho + localDx);
                    el.alto = Math.max(20, original.alto + localDy);
                }

                if (el.tipo === 'icono') {
                    const aspectRatio = original.alto / Math.max(1, original.ancho);
                    el.ancho = Math.max(20, original.ancho + delta);
                    el.alto = Math.max(20, el.ancho * aspectRatio);
                }

                if (el.tipo === 'calle') {
                    el.largo = Math.max(80, original.largo + localDx);
                    this.setTotalRoadWidth(el, this.totalRoadWidth(original) + localDy);
                }

                if (el.tipo === 'curva') {
                    const bounds = this.getBounds(original);
                    const factor = Math.max(0.15, 1 + Math.max(
                        localDx / Math.max(20, bounds.w / 2),
                        localDy / Math.max(20, bounds.h / 2)
                    ));
                    this.scaleCurve(el, original, factor);
                    this.setTotalRoadWidth(el, this.totalRoadWidth(original) * factor);
                }

                if (el.tipo === 'camellon' || el.tipo === 'banqueta') {
                    el.largo = Math.max(20, original.largo + localDx);
                    el.ancho = Math.max(8, original.ancho + localDy);
                }

                if (el.tipo === 'cruce') {
                    el.largoHorizontal = Math.max(100, this.crossHorizontalLength(original) + localDx);
                    el.largoVertical = Math.max(100, this.crossVerticalLength(original) + localDy);
                    el.largo = Math.max(el.largoHorizontal, el.largoVertical);
                }

                if (el.tipo === 'entronque') {
                    el.largoBase = Math.max(100, original.largoBase + localDx);
                    el.largoBrazo = Math.max(60, original.largoBrazo + localDy);
                }

                if (el.tipo === 'glorieta') {
                    el.radioIsla = Math.max(15, original.radioIsla + (delta * 0.4));
                }

                this.render();
                this.onChange(this.elementos);
                return;
            }

            if (this.editingCurve && this.seleccionado.tipo === 'curva') {
                const p = this.toLocal(this.seleccionado, pos.x, pos.y);
                const fields = {
                    curveStart: ['inicioX', 'inicioY'],
                    curveControl1: ['control1X', 'control1Y'],
                    curveControl2: ['control2X', 'control2Y'],
                    curveEnd: ['finX', 'finY']
                }[this.editingCurve];

                if (fields) {
                    this.seleccionado[fields[0]] = p.x;
                    this.seleccionado[fields[1]] = p.y;
                }
                this.render();
                this.onChange(this.elementos);
                return;
            }
        }

        onMouseUp() {
            this.dragging = false;
            this.rotating = false;
            this.resizing = false;
            this.editingCurve = null;
            this.resizeStart = null;
            this.rotateStart = null;
            this.canvas.classList.remove('dragging');
        }

        onWheel(evt) {
            if (!this.seleccionado) return;

            evt.preventDefault();

            const delta = evt.deltaY > 0 ? -1 : 1;
            const el = this.seleccionado;

            if (evt.shiftKey) {
                el.rotacion += delta * 5;
                this.render();
                this.onChange(this.elementos);
                return;
            }

            if (evt.ctrlKey || evt.metaKey) {
                this.changeSelectedLanes(delta);
                return;
            }

            if (el.tipo === 'carro') {
                el.ancho = Math.max(25, el.ancho + (delta * 5));
                el.alto = Math.max(15, el.alto + (delta * 3));
            }

            if (el.tipo === 'vehiculo' || el.tipo === 'icono') {
                const aspectRatio = el.alto / Math.max(1, el.ancho);
                el.ancho = Math.max(20, el.ancho + (delta * 5));
                el.alto = Math.max(20, el.ancho * aspectRatio);
            }

            if (el.tipo === 'calle') {
                el.largo = Math.max(80, el.largo + (delta * 12));
                this.setTotalRoadWidth(el, this.totalRoadWidth(el) + (delta * 4));
            }

            if (el.tipo === 'curva') {
                const original = { ...el };
                const factor = delta > 0 ? 1.06 : 0.94;
                this.scaleCurve(el, original, factor);
                this.setTotalRoadWidth(el, this.totalRoadWidth(original) * factor);
            }

            if (el.tipo === 'camellon' || el.tipo === 'banqueta') {
                el.largo = Math.max(20, el.largo + (delta * 12));
                el.ancho = Math.max(8, el.ancho + (delta * 3));
            }

            if (el.tipo === 'cruce') {
                el.largoHorizontal = Math.max(100, this.crossHorizontalLength(el) + (delta * 12));
                el.largoVertical = Math.max(100, this.crossVerticalLength(el) + (delta * 12));
                el.largo = Math.max(el.largoHorizontal, el.largoVertical);
            }

            if (el.tipo === 'entronque') {
                el.largoBase = Math.max(100, el.largoBase + (delta * 12));
                el.largoBrazo = Math.max(60, el.largoBrazo + (delta * 10));
            }

            if (el.tipo === 'glorieta') {
                el.radioIsla = Math.max(15, el.radioIsla + (delta * 6));
            }

            this.render();
            this.onChange(this.elementos);
        }

        onKeyDown(evt) {
            const target = evt.target;
            const isEditingText = target && (
                ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
                || target.isContentEditable
            );

            if (isEditingText) return;

            const key = evt.key.toLowerCase();
            const commandKey = evt.ctrlKey || evt.metaKey;

            if (commandKey && key === 'c') {
                if (this.copySelected()) evt.preventDefault();
                return;
            }

            if (commandKey && key === 'v') {
                if (this.pasteCopied()) evt.preventDefault();
                return;
            }

            if (commandKey && key === 'd') {
                if (this.duplicateSelected()) evt.preventDefault();
                return;
            }

            if (!this.seleccionado) return;

            if (evt.key === 'Delete' || evt.key === 'Backspace') {
                evt.preventDefault();
                this.removeSelected();
                return;
            }

            if (evt.key === 'ArrowLeft') {
                evt.preventDefault();
                this.seleccionado.x -= 5;
            }

            if (evt.key === 'ArrowRight') {
                evt.preventDefault();
                this.seleccionado.x += 5;
            }

            if (evt.key === 'ArrowUp') {
                evt.preventDefault();
                this.seleccionado.y -= 5;
            }

            if (evt.key === 'ArrowDown') {
                evt.preventDefault();
                this.seleccionado.y += 5;
            }

            if (evt.key.toLowerCase() === 'r') {
                evt.preventDefault();
                this.seleccionado.rotacion += 5;
            }

            if ((evt.key === '+' || evt.key === '=') && typeof this.seleccionado.carriles !== 'undefined') {
                evt.preventDefault();
                this.changeSelectedLanes(1);
                return;
            }

            if ((evt.key === '-' || evt.key === '_') && typeof this.seleccionado.carriles !== 'undefined') {
                evt.preventDefault();
                this.changeSelectedLanes(-1);
                return;
            }

            this.render();
            this.onChange(this.elementos);
        }

        render() {
            window.CroquisRenderer.render(this.ctx, this.canvas, this.elementos, this.assets);
        }

        getPreviewDataUrl() {
            const seleccionado = this.seleccionado;
            const seleccionados = this.elementos.map(el => el.seleccionado);

            this.seleccionado = null;
            this.elementos.forEach(el => el.seleccionado = false);
            this.render();

            let dataUrl = '';

            try {
                dataUrl = this.canvas.toDataURL('image/png');
            } catch (e) {
                dataUrl = '';
            }

            this.elementos.forEach((el, index) => el.seleccionado = seleccionados[index]);
            this.seleccionado = seleccionado;
            this.render();

            return dataUrl;
        }
    }

    return CroquisEditor;
})();
