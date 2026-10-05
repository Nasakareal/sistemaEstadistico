(function (root, factory) {
    'use strict';

    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root) root.ReconstructorVehicleRenderer = api;
}(typeof window !== 'undefined' ? window : globalThis, function () {
    'use strict';

    const BODY_HEIGHT_RATIO = {
        automovil: .74,
        camioneta: .86,
        camion: 1.02
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

    function geometry(type, width, footprintWidth, roll) {
        const length = Math.max(8, Number(width) || 8);
        const track = Math.max(5, Number(footprintWidth) || 5);
        const bodyHeight = track * (BODY_HEIGHT_RATIO[type] || .78);
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
            state: classifyRoll(roll)
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

    function drawFallbackTop(ctx, options, geo) {
        const width = geo.length;
        const height = Math.max(2, geo.deckWidth);
        const y = geo.deckCenter - height / 2;
        const color = options.color || '#ef4444';

        roundedRect(ctx, -width / 2, y, width, height, Math.min(8, height * .25));
        ctx.fillStyle = color;
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,.58)';
        ctx.lineWidth = 1;
        ctx.stroke();

        if (height < 7) return;
        roundedRect(ctx, -width * .2, y + height * .14, width * .42, height * .72, Math.min(5, height * .18));
        ctx.fillStyle = 'rgba(8,20,32,.78)';
        ctx.fill();
        ctx.strokeStyle = 'rgba(190,225,244,.48)';
        ctx.stroke();
        ctx.fillStyle = 'rgba(255,245,180,.9)';
        ctx.fillRect(width * .43, y + height * .18, Math.max(2, width * .035), height * .18);
        ctx.fillRect(width * .43, y + height * .64, Math.max(2, width * .035), height * .18);
        ctx.fillStyle = 'rgba(255,55,55,.9)';
        ctx.fillRect(-width * .46, y + height * .18, Math.max(2, width * .03), height * .18);
        ctx.fillRect(-width * .46, y + height * .64, Math.max(2, width * .03), height * .18);
    }

    function drawTop(ctx, options, geo) {
        if (geo.deckWidth < 1.2) return;
        const image = options.image;
        if (image && image.complete && image.naturalWidth) {
            ctx.save();
            ctx.drawImage(
                image,
                -geo.length / 2,
                geo.deckCenter - geo.deckWidth / 2,
                geo.length,
                geo.deckWidth
            );
            ctx.restore();
            return;
        }
        drawFallbackTop(ctx, options, geo);
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
        const geo = geometry(options.type, options.width, options.height, options.roll);

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
        classifyRoll: classifyRoll,
        geometry: geometry,
        advanceRoll: advanceRoll,
        draw: draw
    };
}));
