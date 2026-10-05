(function (root, factory) {
    'use strict';

    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root) root.ReconstructorVehicleRenderer = api;
}(typeof window !== 'undefined' ? window : globalThis, function () {
    'use strict';

    const MODELS = {
        sedan_compact: { label: 'Sedán compacto', type: 'automovil', shape: 'sedan', lengthMeters: 4.55, widthMeters: 1.80, heightMeters: 1.45, massKg: 1450, cgHeight: .55, grip: .90 },
        hatchback: { label: 'Hatchback', type: 'automovil', shape: 'hatchback', lengthMeters: 4.10, widthMeters: 1.76, heightMeters: 1.48, massKg: 1320, cgHeight: .56, grip: .92 },
        suv_midsize: { label: 'SUV mediana', type: 'camioneta', shape: 'suv', lengthMeters: 4.72, widthMeters: 1.90, heightMeters: 1.72, massKg: 1950, cgHeight: .76, grip: .84 },
        pickup_crew: { label: 'Pickup doble cabina', type: 'camioneta', shape: 'pickup', lengthMeters: 5.35, widthMeters: 1.92, heightMeters: 1.82, massKg: 2180, cgHeight: .82, grip: .80 },
        cargo_van: { label: 'Van de carga', type: 'camioneta', shape: 'van', lengthMeters: 5.15, widthMeters: 1.99, heightMeters: 2.25, massKg: 2650, cgHeight: .94, grip: .76 },
        city_bus: { label: 'Autobús urbano', type: 'camion', shape: 'bus', lengthMeters: 12.00, widthMeters: 2.55, heightMeters: 3.20, massKg: 11800, cgHeight: 1.35, grip: .70 },
        rigid_truck: { label: 'Camión rígido', type: 'camion', shape: 'rigid_truck', lengthMeters: 8.20, widthMeters: 2.50, heightMeters: 3.35, massKg: 9200, cgHeight: 1.42, grip: .68 },
        tractor_trailer: { label: 'Tractocamión', type: 'camion', shape: 'tractor_trailer', lengthMeters: 16.50, widthMeters: 2.55, heightMeters: 4.00, massKg: 26000, cgHeight: 1.65, grip: .64 },
        sport_motorcycle: { label: 'Motocicleta', type: 'motocicleta', shape: 'motorcycle', lengthMeters: 2.10, widthMeters: .82, heightMeters: 1.15, massKg: 240, cgHeight: .65, grip: .95 },
        urban_bicycle: { label: 'Bicicleta', type: 'bicicleta', shape: 'bicycle', lengthMeters: 1.80, widthMeters: .62, heightMeters: 1.08, massKg: 95, cgHeight: .75, grip: .70 },
        adult_pedestrian: { label: 'Peatón adulto', type: 'peaton', shape: 'pedestrian', lengthMeters: .55, widthMeters: .45, heightMeters: 1.72, massKg: 80, cgHeight: .90, grip: .80 }
    };

    const DEFAULT_MODEL_BY_TYPE = {
        automovil: 'sedan_compact',
        camioneta: 'pickup_crew',
        camion: 'rigid_truck',
        motocicleta: 'sport_motorcycle',
        bicicleta: 'urban_bicycle',
        peaton: 'adult_pedestrian'
    };

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, Number(value) || 0));
    }

    function rollPhase(value) {
        return ((Number(value) || 0) % 360 + 360) % 360;
    }

    function angularDistance(a, b) {
        return Math.abs((((a - b) + 540) % 360) - 180);
    }

    function classifyRoll(value) {
        const phase = rollPhase(value);
        if (angularDistance(phase, 0) <= 12) return 'sobre ruedas';
        if (angularDistance(phase, 90) <= 12) return 'sobre costado derecho';
        if (angularDistance(phase, 180) <= 12) return 'sobre techo';
        if (angularDistance(phase, 270) <= 12) return 'sobre costado izquierdo';
        return 'volcando';
    }

    function model(key, fallbackType) {
        const requested = MODELS[key];
        if (requested) return requested;
        return MODELS[DEFAULT_MODEL_BY_TYPE[fallbackType] || 'sedan_compact'];
    }

    function geometry(type, width, footprintWidth, roll, modelKey) {
        const length = Math.max(8, Number(width) || 8);
        const track = Math.max(5, Number(footprintWidth) || 5);
        const spec = model(modelKey, type);
        const bodyHeight = track * clamp(spec.heightMeters / spec.widthMeters, .55, 1.65);
        const radians = (Number(roll) || 0) * Math.PI / 180;
        const sine = Math.sin(radians);
        const cosine = Math.cos(radians);
        const deckWidth = track * Math.abs(cosine);
        const sideWidth = bodyHeight * Math.abs(sine);
        const topCenter = -(bodyHeight / 2) * sine;
        const bottomCenter = (bodyHeight / 2) * sine;
        const deckCenter = cosine >= 0 ? topCenter : bottomCenter;
        const sideDirection = Math.sign((sine * cosine) || sine || 1);
        const sideCenter = sideDirection * Math.abs(cosine) * track / 2;
        const projectedWidth = Math.max(3, deckWidth + sideWidth);

        return {
            length: length,
            track: track,
            bodyHeight: bodyHeight,
            roll: Number(roll) || 0,
            sine: sine,
            cosine: cosine,
            deckWidth: deckWidth,
            sideWidth: sideWidth,
            deckCenter: deckCenter,
            sideCenter: sideCenter,
            projectedWidth: projectedWidth,
            showingUnderside: cosine < 0,
            state: classifyRoll(roll),
            model: spec
        };
    }

    function advanceRoll(roll, rollRate, step) {
        let angle = Number(roll) || 0;
        let rate = Number(rollRate) || 0;
        const delta = clamp(step, 1 / 240, 1 / 15);
        const target = Math.round(angle / 90) * 90;
        const targetError = target - angle;
        const angularSpeed = Math.abs(rate);
        const spring = angularSpeed > 95 ? 1.1 : 5.4;

        rate += targetError * spring * delta;
        rate *= Math.pow(angularSpeed > 45 ? .986 : .972, delta * 60);
        angle += rate * delta;

        if (Math.abs(targetError) < .8 && Math.abs(rate) < 2.5) {
            angle = target;
            rate = 0;
        }

        return { roll: angle, rollRate: rate };
    }

    function roundedRect(ctx, x, y, width, height, radius) {
        const r = clamp(radius, 0, Math.min(Math.abs(width), Math.abs(height)) / 2);
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + width - r, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + r);
        ctx.lineTo(x + width, y + height - r);
        ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
        ctx.lineTo(x + r, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function drawSmallParticipant(ctx, options, geo) {
        const width = geo.length;
        const height = Math.max(2, geo.deckWidth);
        const y = geo.deckCenter - height / 2;
        const color = options.color || '#ef4444';
        const shape = geo.model.shape;

        if (shape === 'pedestrian') {
            ctx.fillStyle = '#f2b38a';
            ctx.beginPath();
            ctx.arc(width * .22, geo.deckCenter, Math.max(2.5, height * .28), 0, Math.PI * 2);
            ctx.fill();
            roundedRect(ctx, -width * .28, y + height * .18, width * .48, height * .64, height * .22);
            ctx.fillStyle = color;
            ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,.55)';
            ctx.stroke();
            return;
        }

        const wheelRadius = Math.max(2, height * (shape === 'bicycle' ? .31 : .25));
        [-.34, .34].forEach(function (position) {
            ctx.beginPath();
            ctx.arc(width * position, geo.deckCenter, wheelRadius, 0, Math.PI * 2);
            ctx.strokeStyle = '#d5dde8';
            ctx.lineWidth = Math.max(1, height * .08);
            ctx.stroke();
        });

        if (shape === 'bicycle') {
            ctx.strokeStyle = color;
            ctx.lineWidth = Math.max(1.5, height * .12);
            ctx.beginPath();
            ctx.moveTo(-width * .34, geo.deckCenter);
            ctx.lineTo(-width * .03, y + height * .2);
            ctx.lineTo(width * .16, y + height * .78);
            ctx.lineTo(-width * .18, y + height * .78);
            ctx.closePath();
            ctx.moveTo(-width * .03, y + height * .2);
            ctx.lineTo(width * .34, geo.deckCenter);
            ctx.stroke();
            return;
        }

        roundedRect(ctx, -width * .2, y + height * .2, width * .46, height * .6, height * .25);
        ctx.fillStyle = color;
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,.55)';
        ctx.stroke();
        ctx.fillStyle = '#111827';
        ctx.fillRect(width * .2, y + height * .04, width * .08, height * .92);
        ctx.fillRect(-width * .12, y + height * .04, width * .04, height * .92);
    }

    function drawVehicleTop(ctx, options, geo) {
        const width = geo.length;
        const height = Math.max(2, geo.deckWidth);
        const y = geo.deckCenter - height / 2;
        const color = options.color || '#ef4444';
        const shape = geo.model.shape;

        if (['motorcycle', 'bicycle', 'pedestrian'].includes(shape)) {
            drawSmallParticipant(ctx, options, geo);
            return;
        }

        roundedRect(ctx, -width / 2, y, width, height, Math.min(8, height * .25));
        ctx.fillStyle = color;
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,.58)';
        ctx.lineWidth = 1;
        ctx.stroke();

        if (height < 7) return;

        if (shape === 'pickup') {
            roundedRect(ctx, -width * .46, y + height * .12, width * .38, height * .76, 2);
            ctx.fillStyle = 'rgba(25,35,45,.72)';
            ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,.38)';
            ctx.stroke();
            roundedRect(ctx, width * .02, y + height * .12, width * .29, height * .76, 3);
            ctx.fillStyle = 'rgba(8,20,32,.82)';
            ctx.fill();
        } else if (shape === 'rigid_truck' || shape === 'tractor_trailer') {
            const trailerEnd = shape === 'tractor_trailer' ? width * .16 : width * .12;
            roundedRect(ctx, -width * .48, y + height * .06, width * .48 + trailerEnd, height * .88, 2);
            ctx.fillStyle = 'rgba(225,232,238,.82)';
            ctx.fill();
            ctx.strokeStyle = '#64748b';
            ctx.stroke();
            roundedRect(ctx, width * .20, y + height * .1, width * .26, height * .8, 3);
            ctx.fillStyle = color;
            ctx.fill();
            ctx.fillStyle = 'rgba(8,20,32,.85)';
            ctx.fillRect(width * .31, y + height * .14, width * .11, height * .72);
        } else if (shape === 'bus') {
            roundedRect(ctx, -width * .42, y + height * .1, width * .82, height * .8, 3);
            ctx.fillStyle = 'rgba(8,20,32,.75)';
            ctx.fill();
            ctx.strokeStyle = 'rgba(190,225,244,.45)';
            ctx.stroke();
            for (let x = -.32; x <= .28; x += .15) {
                ctx.fillStyle = 'rgba(125,211,252,.32)';
                ctx.fillRect(width * x, y + height * .18, width * .08, height * .64);
            }
        } else {
            const cabinStart = shape === 'van' ? -.30 : (shape === 'suv' ? -.25 : -.20);
            const cabinWidth = shape === 'hatchback' ? .47 : (shape === 'van' ? .66 : .42);
            roundedRect(ctx, width * cabinStart, y + height * .14, width * cabinWidth, height * .72, Math.min(5, height * .18));
            ctx.fillStyle = 'rgba(8,20,32,.78)';
            ctx.fill();
            ctx.strokeStyle = 'rgba(190,225,244,.48)';
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(width * .01, y + height * .14);
            ctx.lineTo(width * .01, y + height * .86);
            ctx.stroke();
        }

        ctx.fillStyle = '#101820';
        const wheelLength = Math.max(3, width * .055);
        [-.33, .33].forEach(function (position) {
            ctx.fillRect(width * position - wheelLength / 2, y - 1, wheelLength, Math.max(2, height * .11));
            ctx.fillRect(width * position - wheelLength / 2, y + height - Math.max(1, height * .1), wheelLength, Math.max(2, height * .11));
        });

        if (!['rigid_truck', 'tractor_trailer', 'bus'].includes(shape)) {
            ctx.fillStyle = 'rgba(255,245,180,.9)';
            ctx.fillRect(width * .43, y + height * .18, Math.max(2, width * .035), height * .18);
            ctx.fillRect(width * .43, y + height * .64, Math.max(2, width * .035), height * .18);
            ctx.fillStyle = 'rgba(255,55,55,.9)';
            ctx.fillRect(-width * .46, y + height * .18, Math.max(2, width * .03), height * .18);
            ctx.fillRect(-width * .46, y + height * .64, Math.max(2, width * .03), height * .18);
        }
    }

    function drawTop(ctx, options, geo) {
        if (geo.deckWidth < 1.2) return;
        drawVehicleTop(ctx, options, geo);
    }

    function drawSide(ctx, options, geo) {
        if (geo.sideWidth < .8) return;
        const width = geo.length;
        const height = Math.max(1, geo.sideWidth);
        const y = geo.sideCenter - height / 2;
        const color = options.color || '#ef4444';
        const lowerEdge = geo.sine >= 0 ? y + height : y;

        const gradient = ctx.createLinearGradient(0, y, 0, y + height);
        gradient.addColorStop(0, color);
        gradient.addColorStop(1, '#111827');
        roundedRect(ctx, -width / 2, y, width, height, Math.min(6, height * .2));
        ctx.fillStyle = gradient;
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,.5)';
        ctx.lineWidth = 1;
        ctx.stroke();

        if (height >= 7) {
            const windowY = y + height * .16;
            const windowHeight = height * .42;
            ctx.fillStyle = 'rgba(8,22,34,.88)';
            if (options.type === 'camion') {
                roundedRect(ctx, width * .19, windowY, width * .24, windowHeight, 2);
                ctx.fill();
            } else {
                roundedRect(ctx, -width * .2, windowY, width * .43, windowHeight, 2);
                ctx.fill();
                ctx.strokeStyle = 'rgba(174,221,244,.48)';
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(0, windowY);
                ctx.lineTo(0, windowY + windowHeight);
                ctx.stroke();
            }

            ctx.strokeStyle = 'rgba(255,255,255,.28)';
            ctx.beginPath();
            ctx.moveTo(-width * .02, y + height * .62);
            ctx.lineTo(-width * .02, y + height * .92);
            ctx.stroke();
        }

        const wheelRadius = clamp(height * .28, 2.4, 8);
        const wheelY = lowerEdge + (geo.sine >= 0 ? -wheelRadius * .1 : wheelRadius * .1);
        [-.33, .33].forEach(function (position) {
            ctx.beginPath();
            ctx.arc(width * position, wheelY, wheelRadius, 0, Math.PI * 2);
            ctx.fillStyle = '#090d12';
            ctx.fill();
            ctx.strokeStyle = '#64748b';
            ctx.lineWidth = 1;
            ctx.stroke();
            if (wheelRadius >= 4) {
                ctx.beginPath();
                ctx.arc(width * position, wheelY, wheelRadius * .42, 0, Math.PI * 2);
                ctx.fillStyle = '#94a3b8';
                ctx.fill();
            }
        });
    }

    function drawUnderside(ctx, options, geo) {
        if (geo.deckWidth < 1.2) return;
        const width = geo.length;
        const height = geo.deckWidth;
        const y = geo.deckCenter - height / 2;

        roundedRect(ctx, -width / 2, y, width, height, Math.min(6, height * .18));
        ctx.fillStyle = '#1a2029';
        ctx.fill();
        ctx.strokeStyle = '#64748b';
        ctx.lineWidth = 1;
        ctx.stroke();

        if (height < 6) return;
        roundedRect(ctx, -width * .32, y + height * .22, width * .64, height * .56, 3);
        ctx.fillStyle = '#303946';
        ctx.fill();
        ctx.fillStyle = '#111827';
        ctx.fillRect(-width * .42, y + height * .14, width * .12, height * .72);
        ctx.fillRect(width * .30, y + height * .14, width * .12, height * .72);
        ctx.strokeStyle = '#9ca3af';
        ctx.lineWidth = Math.max(1, height * .06);
        ctx.beginPath();
        ctx.moveTo(-width * .24, y + height * .5);
        ctx.lineTo(width * .37, y + height * .5);
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(-width * .18, y + height * .5, Math.max(2, height * .16), 0, Math.PI * 2);
        ctx.strokeStyle = '#4b5563';
        ctx.stroke();
    }

    function draw(ctx, options) {
        options = options || {};
        const geo = geometry(options.type, options.width, options.height, options.roll, options.model);

        ctx.save();
        ctx.globalAlpha = clamp(options.alpha == null ? 1 : options.alpha, 0, 1);
        drawSide(ctx, options, geo);
        if (geo.showingUnderside) drawUnderside(ctx, options, geo);
        else drawTop(ctx, options, geo);

        if (options.active) {
            roundedRect(
                ctx,
                -geo.length / 2 - 7,
                -geo.projectedWidth / 2 - 7,
                geo.length + 14,
                geo.projectedWidth + 14,
                6
            );
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 4]);
            ctx.stroke();
            ctx.setLineDash([]);
        }
        ctx.restore();
        return geo;
    }

    return {
        models: MODELS,
        defaultModelByType: DEFAULT_MODEL_BY_TYPE,
        model: model,
        classifyRoll: classifyRoll,
        geometry: geometry,
        advanceRoll: advanceRoll,
        draw: draw
    };
}));
