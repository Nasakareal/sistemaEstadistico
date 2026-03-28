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
            this.editingCurve = false;

            this.offsetX = 0;
            this.offsetY = 0;

            this.resizeStart = null;
            this.curveStart = null;

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

        isInsideElement(el, x, y) {
            const p = this.toLocal(el, x, y);

            if (el.tipo === 'carro') {
                return Math.abs(p.x) <= el.ancho / 2 && Math.abs(p.y) <= el.alto / 2;
            }

            if (el.tipo === 'calle') {
                const h = el.carriles * el.anchoCarril;
                return Math.abs(p.x) <= el.largo / 2 && Math.abs(p.y) <= h / 2;
            }

            if (el.tipo === 'curva') {
                const outer = el.radioInterno + (el.carriles * el.anchoCarril);
                const dist = Math.sqrt((p.x * p.x) + (p.y * p.y));
                const ang = Math.atan2(p.y, p.x);
                const limite = (el.angulo || 90) * Math.PI / 180;
                return p.x >= 0 && p.y >= 0 && ang >= 0 && ang <= limite && dist >= el.radioInterno && dist <= outer;
            }

            if (el.tipo === 'cruce') {
                const roadW = el.carriles * el.anchoCarril;
                const insideH = Math.abs(p.x) <= el.largo / 2 && Math.abs(p.y) <= roadW / 2;
                const insideV = Math.abs(p.x) <= roadW / 2 && Math.abs(p.y) <= el.largo / 2;
                return insideH || insideV;
            }

            if (el.tipo === 'entronque') {
                const roadW = el.carriles * el.anchoCarril;
                const base = Math.abs(p.x) <= el.largoBase / 2 && Math.abs(p.y) <= roadW / 2;
                const brazo = Math.abs(p.x) <= roadW / 2 && p.y >= -el.largoBrazo && p.y <= 0;
                return base || brazo;
            }

            if (el.tipo === 'glorieta') {
                const outerRing = el.radioIsla + (el.carriles * el.anchoCarril);
                const dist = Math.sqrt((p.x * p.x) + (p.y * p.y));
                const ring = dist >= el.radioIsla && dist <= outerRing;

                const accessH = (
                    (p.x >= outerRing && p.x <= outerRing + el.largoAcceso && Math.abs(p.y) <= (el.carriles * el.anchoCarril) / 2) ||
                    (p.x <= -outerRing && p.x >= -outerRing - el.largoAcceso && Math.abs(p.y) <= (el.carriles * el.anchoCarril) / 2)
                );

                const accessV = (
                    (p.y >= outerRing && p.y <= outerRing + el.largoAcceso && Math.abs(p.x) <= (el.carriles * el.anchoCarril) / 2) ||
                    (p.y <= -outerRing && p.y >= -outerRing - el.largoAcceso && Math.abs(p.x) <= (el.carriles * el.anchoCarril) / 2)
                );

                return ring || accessH || accessV;
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

            if (handles.curve && this.distance(p.x, p.y, handles.curve.x, handles.curve.y) <= handles.curve.r + 4) {
                return 'curve';
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

                    if (handle === 'curve') {
                        this.editingCurve = true;
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
                this.seleccionado.rotacion = Math.round((angle * 180 / Math.PI));
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

                if (el.tipo === 'carro') {
                    el.ancho = Math.max(25, original.ancho + delta);
                    el.alto = Math.max(15, original.alto + (delta * 0.5));
                }

                if (el.tipo === 'calle') {
                    el.largo = Math.max(80, original.largo + delta);
                }

                if (el.tipo === 'curva') {
                    el.radioInterno = Math.max(15, original.radioInterno + delta);
                }

                if (el.tipo === 'cruce') {
                    el.largo = Math.max(100, original.largo + delta);
                }

                if (el.tipo === 'entronque') {
                    el.largoBase = Math.max(100, original.largoBase + delta);
                    el.largoBrazo = Math.max(60, original.largoBrazo + (delta * 0.7));
                }

                if (el.tipo === 'glorieta') {
                    el.radioIsla = Math.max(15, original.radioIsla + (delta * 0.4));
                    el.largoAcceso = Math.max(60, original.largoAcceso + (delta * 0.6));
                }

                this.render();
                this.onChange(this.elementos);
                return;
            }

            if (this.editingCurve && this.seleccionado.tipo === 'curva') {
                const p = this.toLocal(this.seleccionado, pos.x, pos.y);
                let ang = Math.atan2(Math.max(0, p.y), Math.max(0, p.x)) * 180 / Math.PI;
                ang = Math.max(30, Math.min(180, ang));
                this.seleccionado.angulo = ang;
                this.render();
                this.onChange(this.elementos);
                return;
            }
        }

        onMouseUp() {
            this.dragging = false;
            this.rotating = false;
            this.resizing = false;
            this.editingCurve = false;
            this.resizeStart = null;
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
                if (typeof el.carriles !== 'undefined') {
                    el.carriles = Math.max(1, el.carriles + delta);
                }
                this.render();
                this.onChange(this.elementos);
                return;
            }

            if (evt.altKey && el.tipo === 'curva') {
                el.angulo = Math.max(30, Math.min(180, (el.angulo || 90) + (delta * 5)));
                this.render();
                this.onChange(this.elementos);
                return;
            }

            if (el.tipo === 'carro') {
                el.ancho = Math.max(25, el.ancho + (delta * 5));
                el.alto = Math.max(15, el.alto + (delta * 3));
            }

            if (el.tipo === 'calle') {
                el.largo = Math.max(80, el.largo + (delta * 12));
            }

            if (el.tipo === 'curva') {
                el.radioInterno = Math.max(15, el.radioInterno + (delta * 8));
            }

            if (el.tipo === 'cruce') {
                el.largo = Math.max(100, el.largo + (delta * 12));
            }

            if (el.tipo === 'entronque') {
                el.largoBase = Math.max(100, el.largoBase + (delta * 12));
                el.largoBrazo = Math.max(60, el.largoBrazo + (delta * 10));
            }

            if (el.tipo === 'glorieta') {
                el.radioIsla = Math.max(15, el.radioIsla + (delta * 6));
                el.largoAcceso = Math.max(60, el.largoAcceso + (delta * 4));
            }

            this.render();
            this.onChange(this.elementos);
        }

        onKeyDown(evt) {
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
                this.seleccionado.carriles += 1;
            }

            if ((evt.key === '-' || evt.key === '_') && typeof this.seleccionado.carriles !== 'undefined') {
                evt.preventDefault();
                this.seleccionado.carriles = Math.max(1, this.seleccionado.carriles - 1);
            }

            if (this.seleccionado.tipo === 'curva' && evt.key.toLowerCase() === 'q') {
                evt.preventDefault();
                this.seleccionado.angulo = Math.max(30, (this.seleccionado.angulo || 90) - 5);
            }

            if (this.seleccionado.tipo === 'curva' && evt.key.toLowerCase() === 'e') {
                evt.preventDefault();
                this.seleccionado.angulo = Math.min(180, (this.seleccionado.angulo || 90) + 5);
            }

            this.render();
            this.onChange(this.elementos);
        }

        render() {
            window.CroquisRenderer.render(this.ctx, this.canvas, this.elementos, this.assets);
        }
    }

    return CroquisEditor;
})();
