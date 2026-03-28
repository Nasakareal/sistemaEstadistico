window.CroquisRenderer = (function () {
    const ROAD_FILL = '#2f2f2f';
    const ROAD_LINE = '#ffffff';
    const ISLAND_FILL = '#5cb85c';
    const SELECT_COLOR = '#0d6efd';
    const ROTATE_COLOR = '#dc3545';
    const RESIZE_COLOR = '#fd7e14';
    const CURVE_COLOR = '#6f42c1';

    function totalRoadWidth(el) {
        return el.carriles * el.anchoCarril;
    }

    function laneDividers(carriles, anchoCarril) {
        const total = carriles * anchoCarril;
        const start = -total / 2;
        const lines = [];

        for (let i = 1; i < carriles; i++) {
            lines.push(start + (i * anchoCarril));
        }

        return lines;
    }

    function drawDashedLaneLines(ctx, fromX, toX, yValues) {
        ctx.save();
        ctx.strokeStyle = ROAD_LINE;
        ctx.lineWidth = 2;
        ctx.setLineDash([12, 10]);

        yValues.forEach(y => {
            ctx.beginPath();
            ctx.moveTo(fromX, y);
            ctx.lineTo(toX, y);
            ctx.stroke();
        });

        ctx.restore();
    }

    function getBounds(el) {
        if (el.tipo === 'carro') {
            return { w: el.ancho, h: el.alto };
        }

        if (el.tipo === 'calle') {
            return { w: el.largo, h: totalRoadWidth(el) };
        }

        if (el.tipo === 'curva') {
            const outer = el.radioInterno + totalRoadWidth(el);
            return { w: outer * 2, h: outer * 2 };
        }

        if (el.tipo === 'cruce') {
            return { w: el.largo, h: el.largo };
        }

        if (el.tipo === 'entronque') {
            const roadW = totalRoadWidth(el);
            return { w: Math.max(el.largoBase, roadW), h: roadW + el.largoBrazo };
        }

        if (el.tipo === 'glorieta') {
            const outer = el.radioIsla + totalRoadWidth(el) + el.largoAcceso;
            return { w: outer * 2, h: outer * 2 };
        }

        return { w: 100, h: 100 };
    }

    function getHandles(el) {
        const b = getBounds(el);

        const handles = {
            rotate: { x: 0, y: -(b.h / 2) - 28, r: 12 },
            resize: { x: (b.w / 2) + 16, y: (b.h / 2) + 16, r: 12 }
        };

        if (el.tipo === 'curva') {
            const outer = el.radioInterno + totalRoadWidth(el);
            const angulo = (el.angulo || 90) * Math.PI / 180;
            handles.curve = {
                x: Math.cos(angulo) * (outer + 18),
                y: Math.sin(angulo) * (outer + 18),
                r: 11
            };
        }

        return handles;
    }

    function drawSelection(ctx, el) {
        const b = getBounds(el);
        const handles = getHandles(el);

        ctx.save();
        ctx.strokeStyle = SELECT_COLOR;
        ctx.lineWidth = 2;
        ctx.setLineDash([8, 6]);
        ctx.strokeRect(-b.w / 2, -b.h / 2, b.w, b.h);
        ctx.setLineDash([]);

        ctx.beginPath();
        ctx.moveTo(0, -b.h / 2);
        ctx.lineTo(handles.rotate.x, handles.rotate.y);
        ctx.strokeStyle = SELECT_COLOR;
        ctx.stroke();

        ctx.fillStyle = ROTATE_COLOR;
        ctx.beginPath();
        ctx.arc(handles.rotate.x, handles.rotate.y, handles.rotate.r, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = RESIZE_COLOR;
        ctx.beginPath();
        ctx.arc(handles.resize.x, handles.resize.y, handles.resize.r, 0, Math.PI * 2);
        ctx.fill();

        if (handles.curve) {
            ctx.fillStyle = CURVE_COLOR;
            ctx.beginPath();
            ctx.arc(handles.curve.x, handles.curve.y, handles.curve.r, 0, Math.PI * 2);
            ctx.fill();
        }

        ctx.restore();
    }

    function drawCar(ctx, el, carroImg) {
        if (carroImg && carroImg.complete && carroImg.naturalWidth > 0) {
            ctx.drawImage(carroImg, -el.ancho / 2, -el.alto / 2, el.ancho, el.alto);
        } else {
            ctx.fillStyle = '#d9534f';
            ctx.fillRect(-el.ancho / 2, -el.alto / 2, el.ancho, el.alto);
            ctx.fillStyle = '#222';
            ctx.fillRect(-el.ancho / 4, -el.alto / 3, el.ancho / 2, el.alto / 2);
        }

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawStreet(ctx, el) {
        const width = el.largo;
        const height = totalRoadWidth(el);

        ctx.fillStyle = ROAD_FILL;
        ctx.fillRect(-width / 2, -height / 2, width, height);

        const divs = laneDividers(el.carriles, el.anchoCarril);
        drawDashedLaneLines(ctx, -width / 2, width / 2, divs);

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawCurve(ctx, el) {
        const inner = el.radioInterno;
        const outer = inner + totalRoadWidth(el);
        const angulo = (el.angulo || 90) * Math.PI / 180;

        ctx.fillStyle = ROAD_FILL;
        ctx.beginPath();
        ctx.arc(0, 0, outer, 0, angulo);
        ctx.arc(0, 0, inner, angulo, 0, true);
        ctx.closePath();
        ctx.fill();

        ctx.save();
        ctx.strokeStyle = ROAD_LINE;
        ctx.lineWidth = 2;
        ctx.setLineDash([12, 10]);

        for (let i = 1; i < el.carriles; i++) {
            const r = inner + (i * el.anchoCarril);
            ctx.beginPath();
            ctx.arc(0, 0, r, 0, angulo);
            ctx.stroke();
        }

        ctx.restore();

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawCross(ctx, el) {
        const roadW = totalRoadWidth(el);
        const arm = el.largo;

        ctx.fillStyle = ROAD_FILL;
        ctx.fillRect(-arm / 2, -roadW / 2, arm, roadW);
        ctx.fillRect(-roadW / 2, -arm / 2, roadW, arm);

        const divs = laneDividers(el.carriles, el.anchoCarril);
        drawDashedLaneLines(ctx, -arm / 2, arm / 2, divs);

        ctx.save();
        ctx.rotate(Math.PI / 2);
        drawDashedLaneLines(ctx, -arm / 2, arm / 2, divs);
        ctx.restore();

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawTJunction(ctx, el) {
        const roadW = totalRoadWidth(el);

        ctx.fillStyle = ROAD_FILL;
        ctx.fillRect(-el.largoBase / 2, -roadW / 2, el.largoBase, roadW);
        ctx.fillRect(-roadW / 2, -el.largoBrazo, roadW, el.largoBrazo);

        const divs = laneDividers(el.carriles, el.anchoCarril);
        drawDashedLaneLines(ctx, -el.largoBase / 2, el.largoBase / 2, divs);

        ctx.save();
        ctx.rotate(Math.PI / 2);
        drawDashedLaneLines(ctx, 0, el.largoBrazo, divs);
        ctx.restore();

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawRoundabout(ctx, el) {
        const ringWidth = totalRoadWidth(el);
        const outer = el.radioIsla + ringWidth;

        ctx.fillStyle = ROAD_FILL;
        ctx.beginPath();
        ctx.arc(0, 0, outer, 0, Math.PI * 2);
        ctx.arc(0, 0, el.radioIsla, 0, Math.PI * 2, true);
        ctx.closePath();
        ctx.fill();

        ctx.fillStyle = ISLAND_FILL;
        ctx.beginPath();
        ctx.arc(0, 0, Math.max(6, el.radioIsla - 4), 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = ROAD_FILL;
        ctx.fillRect(-outer - el.largoAcceso, -ringWidth / 2, el.largoAcceso, ringWidth);
        ctx.fillRect(outer, -ringWidth / 2, el.largoAcceso, ringWidth);
        ctx.fillRect(-ringWidth / 2, -outer - el.largoAcceso, ringWidth, el.largoAcceso);
        ctx.fillRect(-ringWidth / 2, outer, ringWidth, el.largoAcceso);

        const divs = laneDividers(el.carriles, el.anchoCarril);

        ctx.save();
        ctx.strokeStyle = ROAD_LINE;
        ctx.lineWidth = 2;
        ctx.setLineDash([12, 10]);

        for (let i = 1; i < el.carriles; i++) {
            const r = el.radioIsla + (i * el.anchoCarril);
            ctx.beginPath();
            ctx.arc(0, 0, r, 0, Math.PI * 2);
            ctx.stroke();
        }

        divs.forEach(y => {
            ctx.beginPath();
            ctx.moveTo(-outer - el.largoAcceso, y);
            ctx.lineTo(-outer, y);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(outer, y);
            ctx.lineTo(outer + el.largoAcceso, y);
            ctx.stroke();
        });

        ctx.save();
        ctx.rotate(Math.PI / 2);
        divs.forEach(y => {
            ctx.beginPath();
            ctx.moveTo(-outer - el.largoAcceso, y);
            ctx.lineTo(-outer, y);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(outer, y);
            ctx.lineTo(outer + el.largoAcceso, y);
            ctx.stroke();
        });
        ctx.restore();

        ctx.restore();

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawElement(ctx, el, assets = {}) {
        ctx.save();
        ctx.translate(el.x, el.y);
        ctx.rotate((el.rotacion || 0) * Math.PI / 180);

        if (el.tipo === 'carro') drawCar(ctx, el, assets.carroImg);
        if (el.tipo === 'calle') drawStreet(ctx, el);
        if (el.tipo === 'curva') drawCurve(ctx, el);
        if (el.tipo === 'cruce') drawCross(ctx, el);
        if (el.tipo === 'entronque') drawTJunction(ctx, el);
        if (el.tipo === 'glorieta') drawRoundabout(ctx, el);

        ctx.restore();
    }

    function render(ctx, canvas, elementos, assets = {}) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        elementos.forEach(el => drawElement(ctx, el, assets));
    }

    return {
        render,
        totalRoadWidth,
        getBounds,
        getHandles
    };
})();
