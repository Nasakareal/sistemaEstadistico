window.CroquisModels = (function () {
    let nextId = 1;

    function uid() {
        return 'croquis_' + (nextId++);
    }

    function setNextIdFromExisting(elementos) {
        let max = 0;

        (elementos || []).forEach(el => {
            if (!el.id) return;
            const match = String(el.id).match(/(\d+)$/);
            if (match) {
                const n = parseInt(match[1], 10);
                if (n > max) max = n;
            }
        });

        nextId = max + 1;
    }

    function base(tipo, x = 200, y = 200) {
        return {
            id: uid(),
            tipo,
            x,
            y,
            rotacion: 0,
            seleccionado: false
        };
    }

    function carro(x = 200, y = 200) {
        return {
            ...base('carro', x, y),
            ancho: 60,
            alto: 30
        };
    }

    function vehiculo(x = 200, y = 200, categoria = 'automovil', subtipo = 'sedan', src = '') {
        return {
            ...base('vehiculo', x, y),
            categoria,
            subtipo,
            src,
            ancho: 90,
            alto: 50
        };
    }

    function icono(x = 200, y = 200, clave = '', src = '') {
        return {
            ...base('icono', x, y),
            clave,
            src,
            ancho: 36,
            alto: 36
        };
    }

    function texto(x = 250, y = 250, contenido = 'Texto') {
        return {
            ...base('texto', x, y),
            contenido,
            fontSize: 20,
            fontFamily: 'Arial',
            ancho: 120,
            alto: 24
        };
    }

    function calle(x = 250, y = 200) {
        return {
            ...base('calle', x, y),
            largo: 260,
            anchoCarril: 28,
            carriles: 1,
            bordeIzquierdo: null,
            bordeDerecho: null
        };
    }

    function curva(x = 320, y = 240) {
        return {
            ...base('curva', x, y),
            anchoCarril: 28,
            carriles: 1,
            bordeIzquierdo: null,
            bordeDerecho: null,
            inicioX: -130,
            inicioY: 55,
            control1X: -80,
            control1Y: -70,
            control2X: 80,
            control2Y: -70,
            finX: 130,
            finY: 55
        };
    }

    function camellon(x = 300, y = 250) {
        return {
            ...base('camellon', x, y),
            largo: 240,
            ancho: 34
        };
    }

    function banqueta(x = 300, y = 250) {
        return {
            ...base('banqueta', x, y),
            largo: 240,
            ancho: 26
        };
    }

    function legacyCurvePoints(raw, anchoCarril, carriles) {
        const inner = Number(raw.radioInterno ?? raw.radio ?? 45);
        const angle = Math.min(180, Math.max(5, Number(raw.angulo ?? 90))) * Math.PI / 180;
        const radius = inner + ((anchoCarril * carriles) / 2);
        const tangent = (4 / 3) * Math.tan(angle / 4) * radius;
        const endX = Math.cos(angle) * radius;
        const endY = Math.sin(angle) * radius;

        return {
            inicioX: radius,
            inicioY: 0,
            control1X: radius,
            control1Y: tangent,
            control2X: endX + (Math.sin(angle) * tangent),
            control2Y: endY - (Math.cos(angle) * tangent),
            finX: endX,
            finY: endY
        };
    }

    function cruce(x = 320, y = 240) {
        return {
            ...base('cruce', x, y),
            largo: 220,
            largoHorizontal: 220,
            largoVertical: 220,
            anchoCarril: 28,
            carriles: 1,
            bordeIzquierdo: null,
            bordeDerecho: null
        };
    }

    function entronque(x = 320, y = 240) {
        return {
            ...base('entronque', x, y),
            largoBase: 220,
            largoBrazo: 140,
            anchoCarril: 28,
            carriles: 1,
            bordeIzquierdo: null,
            bordeDerecho: null
        };
    }

    function glorieta(x = 420, y = 260) {
        return {
            ...base('glorieta', x, y),
            radioIsla: 40,
            anchoCarril: 24,
            carriles: 1,
            largoAcceso: 140,
            bordeIzquierdo: null,
            bordeDerecho: null
        };
    }

    function normalizeRoadEdges(raw) {
        const allowed = ['banqueta', 'camellon'];
        const left = String(raw.bordeIzquierdo ?? '').toLowerCase();
        const right = String(raw.bordeDerecho ?? '').toLowerCase();

        return {
            bordeIzquierdo: allowed.includes(left) ? left : null,
            bordeDerecho: allowed.includes(right) ? right : null
        };
    }

    function normalize(raw) {
        if (!raw || !raw.tipo) return null;

        const baseData = {
            id: raw.id || uid(),
            tipo: raw.tipo,
            x: Number(raw.x ?? 200),
            y: Number(raw.y ?? 200),
            rotacion: Number(raw.rotacion ?? raw.r ?? 0),
            seleccionado: false
        };

        if (raw.tipo === 'carro') {
            return {
                ...baseData,
                ancho: Number(raw.ancho ?? raw.w ?? 60),
                alto: Number(raw.alto ?? raw.h ?? 30)
            };
        }

        if (raw.tipo === 'vehiculo') {
            return {
                ...baseData,
                categoria: String(raw.categoria ?? 'automovil'),
                subtipo: String(raw.subtipo ?? 'sedan'),
                src: String(raw.src ?? ''),
                ancho: Number(raw.ancho ?? raw.w ?? 90),
                alto: Number(raw.alto ?? raw.h ?? 50)
            };
        }

        if (raw.tipo === 'icono') {
            return {
                ...baseData,
                clave: String(raw.clave ?? raw.nombre ?? ''),
                src: String(raw.src ?? ''),
                ancho: Number(raw.ancho ?? raw.w ?? 36),
                alto: Number(raw.alto ?? raw.h ?? 36)
            };
        }

        if (raw.tipo === 'texto') {
            return {
                ...baseData,
                contenido: String(raw.contenido ?? raw.texto ?? 'Texto'),
                fontSize: Number(raw.fontSize ?? 20),
                fontFamily: String(raw.fontFamily ?? 'Arial'),
                ancho: Number(raw.ancho ?? raw.w ?? 120),
                alto: Number(raw.alto ?? raw.h ?? 24)
            };
        }

        if (raw.tipo === 'calle') {
            return {
                ...baseData,
                largo: Number(raw.largo ?? raw.w ?? 260),
                anchoCarril: Number(raw.anchoCarril ?? 28),
                carriles: Math.max(1, Number(raw.carriles ?? 1)),
                ...normalizeRoadEdges(raw)
            };
        }

        if (raw.tipo === 'curva') {
            const anchoCarril = Number(raw.anchoCarril ?? 28);
            const carriles = Math.max(1, Number(raw.carriles ?? 1));
            const hasBezier = ['inicioX', 'inicioY', 'control1X', 'control1Y', 'control2X', 'control2Y', 'finX', 'finY']
                .every(key => Number.isFinite(Number(raw[key])));
            const points = hasBezier
                ? {
                    inicioX: Number(raw.inicioX),
                    inicioY: Number(raw.inicioY),
                    control1X: Number(raw.control1X),
                    control1Y: Number(raw.control1Y),
                    control2X: Number(raw.control2X),
                    control2Y: Number(raw.control2Y),
                    finX: Number(raw.finX),
                    finY: Number(raw.finY)
                }
                : legacyCurvePoints(raw, anchoCarril, carriles);

            return {
                ...baseData,
                anchoCarril,
                carriles,
                ...normalizeRoadEdges(raw),
                ...points
            };
        }

        if (raw.tipo === 'camellon' || raw.tipo === 'banqueta') {
            return {
                ...baseData,
                largo: Math.max(20, Number(raw.largo ?? raw.w ?? 240)),
                ancho: Math.max(8, Number(raw.ancho ?? raw.h ?? (raw.tipo === 'camellon' ? 34 : 26)))
            };
        }

        if (raw.tipo === 'cruce') {
            const largo = Number(raw.largo ?? raw.size ?? raw.largoHorizontal ?? raw.largoVertical ?? 220);
            const largoHorizontal = Number(raw.largoHorizontal ?? raw.w ?? largo);
            const largoVertical = Number(raw.largoVertical ?? raw.h ?? largo);

            return {
                ...baseData,
                largo: Math.max(largo, largoHorizontal, largoVertical),
                largoHorizontal,
                largoVertical,
                anchoCarril: Number(raw.anchoCarril ?? 28),
                carriles: Math.max(1, Number(raw.carriles ?? 1)),
                ...normalizeRoadEdges(raw)
            };
        }

        if (raw.tipo === 'entronque') {
            return {
                ...baseData,
                largoBase: Number(raw.largoBase ?? raw.size ?? 220),
                largoBrazo: Number(raw.largoBrazo ?? 140),
                anchoCarril: Number(raw.anchoCarril ?? 28),
                carriles: Math.max(1, Number(raw.carriles ?? 1)),
                ...normalizeRoadEdges(raw)
            };
        }

        if (raw.tipo === 'glorieta') {
            return {
                ...baseData,
                radioIsla: Number(raw.radioIsla ?? 40),
                anchoCarril: Number(raw.anchoCarril ?? 24),
                carriles: Math.max(1, Number(raw.carriles ?? 1)),
                largoAcceso: Number(raw.largoAcceso ?? 140),
                ...normalizeRoadEdges(raw)
            };
        }

        return null;
    }

    function serialize(elementos) {
        return JSON.stringify((elementos || []).map(el => {
            const copy = { ...el };
            delete copy.seleccionado;
            return copy;
        }));
    }

    function deserialize(json) {
        try {
            const arr = typeof json === 'string' ? JSON.parse(json) : (json || []);
            const normalizados = arr.map(normalize).filter(Boolean);
            setNextIdFromExisting(normalizados);
            return normalizados;
        } catch (e) {
            return [];
        }
    }

    return {
        carro,
        vehiculo,
        icono,
        texto,
        calle,
        curva,
        camellon,
        banqueta,
        cruce,
        entronque,
        glorieta,
        normalize,
        serialize,
        deserialize,
        setNextIdFromExisting
    };
})();
