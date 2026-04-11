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
            carriles: 1
        };
    }

    function curva(x = 320, y = 240) {
        return {
            ...base('curva', x, y),
            radioInterno: 45,
            anchoCarril: 28,
            carriles: 1,
            angulo: 90
        };
    }

    function cruce(x = 320, y = 240) {
        return {
            ...base('cruce', x, y),
            largo: 220,
            anchoCarril: 28,
            carriles: 1
        };
    }

    function entronque(x = 320, y = 240) {
        return {
            ...base('entronque', x, y),
            largoBase: 220,
            largoBrazo: 140,
            anchoCarril: 28,
            carriles: 1
        };
    }

    function glorieta(x = 420, y = 260) {
        return {
            ...base('glorieta', x, y),
            radioIsla: 40,
            anchoCarril: 24,
            carriles: 1,
            largoAcceso: 140
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
                carriles: Math.max(1, Number(raw.carriles ?? 1))
            };
        }

        if (raw.tipo === 'curva') {
            return {
                ...baseData,
                radioInterno: Number(raw.radioInterno ?? raw.radio ?? 45),
                anchoCarril: Number(raw.anchoCarril ?? 28),
                carriles: Math.max(1, Number(raw.carriles ?? 1)),
                angulo: Math.min(180, Math.max(30, Number(raw.angulo ?? 90)))
            };
        }

        if (raw.tipo === 'cruce') {
            return {
                ...baseData,
                largo: Number(raw.largo ?? raw.size ?? 220),
                anchoCarril: Number(raw.anchoCarril ?? 28),
                carriles: Math.max(1, Number(raw.carriles ?? 1))
            };
        }

        if (raw.tipo === 'entronque') {
            return {
                ...baseData,
                largoBase: Number(raw.largoBase ?? raw.size ?? 220),
                largoBrazo: Number(raw.largoBrazo ?? 140),
                anchoCarril: Number(raw.anchoCarril ?? 28),
                carriles: Math.max(1, Number(raw.carriles ?? 1))
            };
        }

        if (raw.tipo === 'glorieta') {
            return {
                ...baseData,
                radioIsla: Number(raw.radioIsla ?? 40),
                anchoCarril: Number(raw.anchoCarril ?? 24),
                carriles: Math.max(1, Number(raw.carriles ?? 1)),
                largoAcceso: Number(raw.largoAcceso ?? 140)
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
        cruce,
        entronque,
        glorieta,
        normalize,
        serialize,
        deserialize,
        setNextIdFromExisting
    };
})();
