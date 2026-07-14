window.CroquisRenderer = (function () {
    const ROAD_FILL = '#2f2f2f';
    const ROAD_LINE = '#ffffff';
    const ISLAND_FILL = '#5cb85c';
    const SELECT_COLOR = '#0d6efd';
    const ROTATE_COLOR = '#dc3545';
    const RESIZE_COLOR = '#fd7e14';
    const CURVE_COLOR = '#6f42c1';
    const CURVE_CONTROL_COLOR = '#20c997';
    const MEDIAN_FILL = '#70a95b';
    const CURB_COLOR = '#d7d7d7';
    const SIDEWALK_FILL = '#c9c9c9';
    const ATTACHED_WIDTHS = {
        banqueta: 26,
        camellon: 34
    };

    function totalRoadWidth(el) {
        return Math.max(1, Number(el.carriles) || 1) * Math.max(1, Number(el.anchoCarril) || 1);
    }

    function crossHorizontalLength(el) {
        return Number(el.largoHorizontal ?? el.largo ?? 220);
    }

    function crossVerticalLength(el) {
        return Number(el.largoVertical ?? el.largo ?? 220);
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

    function curvePoint(el, t, offset = 0) {
        const u = 1 - t;
        const x = (u * u * u * el.inicioX)
            + (3 * u * u * t * el.control1X)
            + (3 * u * t * t * el.control2X)
            + (t * t * t * el.finX);
        const y = (u * u * u * el.inicioY)
            + (3 * u * u * t * el.control1Y)
            + (3 * u * t * t * el.control2Y)
            + (t * t * t * el.finY);
        const dx = (3 * u * u * (el.control1X - el.inicioX))
            + (6 * u * t * (el.control2X - el.control1X))
            + (3 * t * t * (el.finX - el.control2X));
        const dy = (3 * u * u * (el.control1Y - el.inicioY))
            + (6 * u * t * (el.control2Y - el.control1Y))
            + (3 * t * t * (el.finY - el.control2Y));
        const length = Math.sqrt((dx * dx) + (dy * dy)) || 1;

        return {
            x: x + ((-dy / length) * offset),
            y: y + ((dx / length) * offset)
        };
    }

    function curvePolyline(el, offset = 0, steps = 56) {
        const points = [];
        for (let i = 0; i <= steps; i++) {
            points.push(curvePoint(el, i / steps, offset));
        }
        return points;
    }

    function tracePolyline(ctx, points) {
        if (!points.length) return;
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (let i = 1; i < points.length; i++) {
            ctx.lineTo(points[i].x, points[i].y);
        }
    }

    function strokePolyline(ctx, points) {
        tracePolyline(ctx, points);
        ctx.stroke();
    }

    function attachedWidth(type) {
        return ATTACHED_WIDTHS[type] || 0;
    }

    function maxAttachedWidth(el) {
        return Math.max(attachedWidth(el.bordeIzquierdo), attachedWidth(el.bordeDerecho));
    }

    function strokeAttachedPath(ctx, type, drawPath) {
        const width = attachedWidth(type);
        if (!width) return;

        ctx.save();
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = type === 'camellon' ? CURB_COLOR : '#858585';
        ctx.lineWidth = width + 2;
        drawPath();
        ctx.stroke();
        ctx.strokeStyle = type === 'camellon' ? MEDIAN_FILL : SIDEWALK_FILL;
        ctx.lineWidth = Math.max(2, width - (type === 'camellon' ? 6 : 3));
        drawPath();
        ctx.stroke();
        ctx.restore();
    }

    function drawAttachedRoadEdges(ctx, el) {
        const roadWidth = totalRoadWidth(el);
        const sides = [
            { type: el.bordeIzquierdo, sign: -1 },
            { type: el.bordeDerecho, sign: 1 }
        ];

        sides.forEach(side => {
            const width = attachedWidth(side.type);
            if (!width) return;
            const offset = side.sign * ((roadWidth / 2) + (width / 2));

            if (el.tipo === 'calle') {
                strokeAttachedPath(ctx, side.type, () => {
                    ctx.beginPath();
                    ctx.moveTo(-el.largo / 2, offset);
                    ctx.lineTo(el.largo / 2, offset);
                });
            }

            if (el.tipo === 'curva') {
                strokeAttachedPath(ctx, side.type, () => tracePolyline(ctx, curvePolyline(el, offset)));
            }

            if (el.tipo === 'cruce') {
                strokeAttachedPath(ctx, side.type, () => {
                    ctx.beginPath();
                    ctx.moveTo(-crossHorizontalLength(el) / 2, offset);
                    ctx.lineTo(crossHorizontalLength(el) / 2, offset);
                    ctx.moveTo(offset, -crossVerticalLength(el) / 2);
                    ctx.lineTo(offset, crossVerticalLength(el) / 2);
                });
            }

            if (el.tipo === 'entronque') {
                strokeAttachedPath(ctx, side.type, () => {
                    ctx.beginPath();
                    ctx.moveTo(-el.largoBase / 2, offset);
                    ctx.lineTo(el.largoBase / 2, offset);
                    ctx.moveTo(offset, -el.largoBrazo);
                    ctx.lineTo(offset, 0);
                });
            }

            if (el.tipo === 'glorieta') {
                const radius = side.sign < 0
                    ? Math.max(width / 2, el.radioIsla - (width / 2))
                    : el.radioIsla + roadWidth + (width / 2);
                strokeAttachedPath(ctx, side.type, () => {
                    ctx.beginPath();
                    ctx.arc(0, 0, radius, 0, Math.PI * 2);
                });
            }
        });
    }

    function getBounds(el) {
        if (el.tipo === 'carro') {
            return { w: el.ancho, h: el.alto };
        }

        if (el.tipo === 'vehiculo') {
            return { w: el.ancho || 90, h: el.alto || 50 };
        }

        if (el.tipo === 'icono') {
            return { w: el.ancho || 36, h: el.alto || 36 };
        }

        if (el.tipo === 'texto') {
            return { w: el.ancho || 120, h: el.alto || 24 };
        }

        if (el.tipo === 'calle') {
            return { w: el.largo + 4, h: totalRoadWidth(el) + (maxAttachedWidth(el) * 2) };
        }

        if (el.tipo === 'curva') {
            const margin = (totalRoadWidth(el) / 2) + maxAttachedWidth(el) + 4;
            const points = curvePolyline(el);
            const maxX = Math.max(...points.map(point => Math.abs(point.x))) + margin;
            const maxY = Math.max(...points.map(point => Math.abs(point.y))) + margin;
            return { w: maxX * 2, h: maxY * 2 };
        }

        if (el.tipo === 'camellon' || el.tipo === 'banqueta') {
            return { w: el.largo, h: el.ancho };
        }

        if (el.tipo === 'cruce') {
            const roadW = totalRoadWidth(el);
            const attached = maxAttachedWidth(el) * 2;
            return {
                w: Math.max(crossHorizontalLength(el), roadW) + attached,
                h: Math.max(crossVerticalLength(el), roadW) + attached
            };
        }

        if (el.tipo === 'entronque') {
            const roadW = totalRoadWidth(el);
            const attached = maxAttachedWidth(el) * 2;
            return { w: Math.max(el.largoBase, roadW) + attached, h: roadW + el.largoBrazo + attached };
        }

        if (el.tipo === 'glorieta') {
            const outer = el.radioIsla + totalRoadWidth(el) + attachedWidth(el.bordeDerecho);
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
            handles.curveStart = { x: el.inicioX, y: el.inicioY, r: 10 };
            handles.curveControl1 = { x: el.control1X, y: el.control1Y, r: 10 };
            handles.curveControl2 = { x: el.control2X, y: el.control2Y, r: 10 };
            handles.curveEnd = { x: el.finX, y: el.finY, r: 10 };
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

        if (handles.curveStart) {
            ctx.strokeStyle = CURVE_CONTROL_COLOR;
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 4]);
            ctx.beginPath();
            ctx.moveTo(handles.curveStart.x, handles.curveStart.y);
            ctx.lineTo(handles.curveControl1.x, handles.curveControl1.y);
            ctx.moveTo(handles.curveEnd.x, handles.curveEnd.y);
            ctx.lineTo(handles.curveControl2.x, handles.curveControl2.y);
            ctx.stroke();
            ctx.setLineDash([]);

            ['curveStart', 'curveEnd'].forEach(name => {
                const handle = handles[name];
                ctx.fillStyle = CURVE_COLOR;
                ctx.beginPath();
                ctx.arc(handle.x, handle.y, handle.r, 0, Math.PI * 2);
                ctx.fill();
            });

            ['curveControl1', 'curveControl2'].forEach(name => {
                const handle = handles[name];
                ctx.fillStyle = CURVE_CONTROL_COLOR;
                ctx.beginPath();
                ctx.arc(handle.x, handle.y, handle.r, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        ctx.restore();
    }

    function drawImageFallback(ctx, el, bg = '#999') {
        const w = el.ancho || 40;
        const h = el.alto || 40;

        ctx.fillStyle = bg;
        ctx.fillRect(-w / 2, -h / 2, w, h);

        ctx.strokeStyle = '#222';
        ctx.lineWidth = 2;
        ctx.strokeRect(-w / 2, -h / 2, w, h);

        ctx.beginPath();
        ctx.moveTo(-w / 2, -h / 2);
        ctx.lineTo(w / 2, h / 2);
        ctx.moveTo(w / 2, -h / 2);
        ctx.lineTo(-w / 2, h / 2);
        ctx.stroke();
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

    function drawVehicle(ctx, el, assets = {}) {
        const img = assets.vehiculos && assets.vehiculos[el.categoria] && assets.vehiculos[el.categoria][el.subtipo]
            ? assets.vehiculos[el.categoria][el.subtipo]
            : null;

        if (img && img.complete && img.naturalWidth > 0) {
            ctx.drawImage(img, -el.ancho / 2, -el.alto / 2, el.ancho, el.alto);
        } else if (el.src) {
            const temp = new Image();
            temp.src = el.src;

            if (temp.complete && temp.naturalWidth > 0) {
                ctx.drawImage(temp, -el.ancho / 2, -el.alto / 2, el.ancho, el.alto);
            } else {
                drawImageFallback(ctx, el, '#6c757d');
            }
        } else {
            drawImageFallback(ctx, el, '#6c757d');
        }

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawIcon(ctx, el, assets = {}) {
        const img = assets.iconos && (assets.iconos[el.clave] || assets.iconos[el.nombre]) ? (assets.iconos[el.clave] || assets.iconos[el.nombre]) : null;

        if (img && img.complete && img.naturalWidth > 0) {
            ctx.drawImage(img, -el.ancho / 2, -el.alto / 2, el.ancho, el.alto);
        } else if (el.src) {
            const temp = new Image();
            temp.src = el.src;

            if (temp.complete && temp.naturalWidth > 0) {
                ctx.drawImage(temp, -el.ancho / 2, -el.alto / 2, el.ancho, el.alto);
            } else {
                drawImageFallback(ctx, el, '#17a2b8');
            }
        } else {
            drawImageFallback(ctx, el, '#17a2b8');
        }

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawText(ctx, el) {
        const fontSize = el.fontSize || 20;
        const fontFamily = el.fontFamily || 'Arial';
        const contenido = el.contenido || 'Texto';

        ctx.save();
        ctx.font = fontSize + 'px ' + fontFamily;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#111';
        ctx.fillText(contenido, 0, 0);

        const metrics = ctx.measureText(contenido);
        el.ancho = Math.max(40, Math.ceil(metrics.width) + 12);
        el.alto = Math.max(24, Math.ceil(fontSize) + 8);
        ctx.restore();

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawStreet(ctx, el) {
        const width = el.largo;
        const height = totalRoadWidth(el);

        drawAttachedRoadEdges(ctx, el);

        ctx.fillStyle = ROAD_FILL;
        ctx.fillRect(-width / 2, -height / 2, width, height);

        const divs = laneDividers(el.carriles, el.anchoCarril);
        drawDashedLaneLines(ctx, -width / 2, width / 2, divs);

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawCurve(ctx, el) {
        drawAttachedRoadEdges(ctx, el);

        ctx.save();
        ctx.strokeStyle = ROAD_FILL;
        ctx.lineWidth = totalRoadWidth(el);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(el.inicioX, el.inicioY);
        ctx.bezierCurveTo(el.control1X, el.control1Y, el.control2X, el.control2Y, el.finX, el.finY);
        ctx.stroke();

        ctx.strokeStyle = ROAD_LINE;
        ctx.lineWidth = 2;
        ctx.setLineDash([12, 10]);
        laneDividers(el.carriles, el.anchoCarril).forEach(offset => {
            strokePolyline(ctx, curvePolyline(el, offset));
        });

        ctx.restore();

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawMedian(ctx, el) {
        ctx.fillStyle = CURB_COLOR;
        ctx.fillRect(-el.largo / 2, -el.ancho / 2, el.largo, el.ancho);
        ctx.fillStyle = MEDIAN_FILL;
        ctx.fillRect(-el.largo / 2 + 3, -el.ancho / 2 + 3, Math.max(1, el.largo - 6), Math.max(1, el.ancho - 6));

        if (el.seleccionado) drawSelection(ctx, el);
    }

    function drawSidewalk(ctx, el) {
        ctx.fillStyle = SIDEWALK_FILL;
        ctx.fillRect(-el.largo / 2, -el.ancho / 2, el.largo, el.ancho);
        ctx.strokeStyle = '#858585';
        ctx.lineWidth = 2;
        ctx.strokeRect(-el.largo / 2, -el.ancho / 2, el.largo, el.ancho);
        ctx.save();
        ctx.strokeStyle = '#a2a2a2';
        ctx.lineWidth = 1;
        for (let x = -el.largo / 2 + 28; x < el.largo / 2; x += 28) {
            ctx.beginPath();
            ctx.moveTo(x, -el.ancho / 2);
            ctx.lineTo(x, el.ancho / 2);
            ctx.stroke();
        }
        ctx.restore();

        if (el.seleccionado) drawSelection(ctx, el);
    }

    function drawCross(ctx, el) {
        const roadW = totalRoadWidth(el);
        const armH = crossHorizontalLength(el);
        const armV = crossVerticalLength(el);

        drawAttachedRoadEdges(ctx, el);

        ctx.fillStyle = ROAD_FILL;
        ctx.fillRect(-armH / 2, -roadW / 2, armH, roadW);
        ctx.fillRect(-roadW / 2, -armV / 2, roadW, armV);

        const divs = laneDividers(el.carriles, el.anchoCarril);
        drawDashedLaneLines(ctx, -armH / 2, armH / 2, divs);

        ctx.save();
        ctx.rotate(Math.PI / 2);
        drawDashedLaneLines(ctx, -armV / 2, armV / 2, divs);
        ctx.restore();

        if (el.seleccionado) {
            drawSelection(ctx, el);
        }
    }

    function drawTJunction(ctx, el) {
        const roadW = totalRoadWidth(el);

        drawAttachedRoadEdges(ctx, el);

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
        ctx.arc(0, 0, Math.max(6, el.radioIsla - attachedWidth(el.bordeIzquierdo) - 4), 0, Math.PI * 2);
        ctx.fill();

        drawAttachedRoadEdges(ctx, el);

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
        if (el.tipo === 'vehiculo') drawVehicle(ctx, el, assets);
        if (el.tipo === 'icono') drawIcon(ctx, el, assets);
        if (el.tipo === 'texto') drawText(ctx, el);
        if (el.tipo === 'calle') drawStreet(ctx, el);
        if (el.tipo === 'curva') drawCurve(ctx, el);
        if (el.tipo === 'camellon') drawMedian(ctx, el);
        if (el.tipo === 'banqueta') drawSidewalk(ctx, el);
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
        attachedWidth,
        maxAttachedWidth,
        getBounds,
        getHandles,
        curvePolyline
    };
})();
