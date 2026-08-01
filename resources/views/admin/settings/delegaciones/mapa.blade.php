@extends('adminlte::page')

@section('title', 'Mapa de Delegaciones')

@section('content_header')
    <h1>Mapa de Delegaciones</h1>
@stop

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">

    <style>
        .mapa-wrapper {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 15px;
        }

        #mapaDelegaciones {
            width: 100%;
            height: 78vh;
            background: #0f172a;
            border-radius: 10px;
        }

        .panel-delegaciones {
            height: 78vh;
            overflow-y: auto;
            border-radius: 10px;
        }

        .delegacion-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 10px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
        }

        .delegacion-item:hover,
        .delegacion-item.active {
            background: #f3f4f6;
        }

        .delegacion-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .color-box {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            display: inline-block;
        }

        .delegacion-nombre {
            font-weight: 600;
            font-size: 13px;
        }

        .delegacion-municipio {
            font-size: 12px;
            color: #6b7280;
        }

        .badge-hijas {
            font-size: 11px;
        }

        .delegacion-popup {
            min-width: 210px;
        }

        .delegacion-popup__titulo {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .delegacion-popup__dato {
            color: #4b5563;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .delegacion-popup__maps {
            margin-top: 8px;
            width: 100%;
        }

        @media (max-width: 992px) {
            .mapa-wrapper {
                grid-template-columns: 1fr;
            }

            .panel-delegaciones {
                height: auto;
                max-height: 350px;
            }
        }
    </style>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Distribución territorial por delegación padre</h3>

            <div class="card-tools">
                <a href="{{ route('delegaciones.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Regresar
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="mapa-wrapper">
                <div id="mapaDelegaciones"></div>

                <div class="card panel-delegaciones mb-0">
                    <div class="card-header">
                        <h3 class="card-title">Delegaciones padre</h3>
                    </div>

                    <div class="card-body p-0" id="listaDelegaciones">
                        <div class="p-3 text-muted">Cargando delegaciones...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    const mapa = L.map('mapaDelegaciones', {
        zoomControl: true,
        attributionControl: false
    }).setView([19.15, -101.9], 8);

    const delegacionesPorMunicipio = {};
    const delegacionesConCoordenadas = [];
    const padres = {};
    const capasPorPadre = {};
    const marcadoresPorPadre = {};
    const marcadoresDelegaciones = L.layerGroup().addTo(mapa);

    const iconoDelegacion = L.icon({
        iconUrl: '{{ asset('img/barracks.png') }}',
        iconSize: [34, 34],
        iconAnchor: [17, 32],
        popupAnchor: [0, -30],
        className: 'delegacion-marker-icon'
    });

    Promise.all([
        fetch('{{ asset('geo/michoacan.json') }}').then(r => r.json()),
        fetch('{{ route('mapa.delegaciones.data') }}').then(r => r.json())
    ]).then(([geojson, data]) => {
        data.forEach(d => {
            registrarDelegacionPorMunicipio(d);
            const coordenadas = obtenerCoordenadas(d);

            if (coordenadas) {
                delegacionesConCoordenadas.push({
                    delegacion: d,
                    coordenadas
                });
            }

            if (!d.delegacion_padre_id) {
                padres[d.id] = {
                    id: d.id,
                    nombre: d.nombre,
                    municipio: d.municipio || d.nombre,
                    clave: d.clave,
                    color: d.color,
                    total_hijas: 0
                };
            }
        });

        data.forEach(d => {
            const padreId = d.delegacion_padre_id || d.id;

            if (padres[padreId] && d.delegacion_padre_id) {
                padres[padreId].total_hijas++;
            }
        });

        data.forEach(d => {
            crearMarcadorDelegacion(d, geojson.features || []);
        });

        const geoLayer = L.geoJSON(geojson, {
            style: function(feature) {
                const delegacion = obtenerDelegacionParaFeature(feature);

                return {
                    color: '#f8fafc',
                    weight: 1,
                    fillColor: delegacion ? delegacion.color : '#334155',
                    fillOpacity: delegacion ? 0.82 : 0.28
                };
            },
            onEachFeature: function(feature, layer) {
                const nombre = obtenerMunicipio(feature);
                const delegacion = obtenerDelegacionParaFeature(feature);

                if (delegacion) {
                    const padreId = delegacion.delegacion_padre_id || delegacion.id;

                    if (!capasPorPadre[padreId]) {
                        capasPorPadre[padreId] = [];
                    }

                    capasPorPadre[padreId].push(layer);
                }

                let html = '<strong>Municipio:</strong> ' + escaparHtml(nombre);

                if (delegacion) {
                    html += '<br><strong>Delegación:</strong> ' + escaparHtml(delegacion.nombre);
                    html += '<br><strong>Clave:</strong> ' + escaparHtml(delegacion.clave || 'Sin clave');
                } else {
                    html += '<br><span class="text-muted">Sin delegación relacionada</span>';
                }

                layer.bindPopup(html);

                layer.on('mouseover', function() {
                    layer.setStyle({
                        weight: 2,
                        fillOpacity: 1
                    });
                });

                layer.on('mouseout', function() {
                    layer.setStyle({
                        weight: 1,
                        fillOpacity: delegacion ? 0.82 : 0.28
                    });
                });
            }
        }).addTo(mapa);

        mapa.fitBounds(geoLayer.getBounds(), {
            padding: [20, 20]
        });

        pintarLista();
    }).catch(() => {
        $('#listaDelegaciones').html('<div class="p-3 text-danger">No se pudo cargar el mapa o las delegaciones.</div>');
    });

    function pintarLista() {
        let html = '';

        Object.values(padres).forEach(padre => {
            const color = /^#[0-9a-fA-F]{6}$/.test(padre.color || '') ? padre.color : '#64748b';
            const totalHijas = Number.parseInt(padre.total_hijas, 10) || 0;

            html += `
                <div class="delegacion-item" data-padre="${padre.id}">
                    <div class="delegacion-info">
                        <span class="color-box" style="background:${color}"></span>
                        <div>
                            <div class="delegacion-nombre">${escaparHtml(padre.nombre)}</div>
                            <div class="delegacion-municipio">${escaparHtml(padre.municipio)}</div>
                        </div>
                    </div>
                    <span class="badge badge-primary badge-hijas">${totalHijas} hijas</span>
                </div>
            `;
        });

        $('#listaDelegaciones').html(html || '<div class="p-3 text-muted">Sin delegaciones padre.</div>');
    }

    $(document).on('click', '.delegacion-item', function() {
        const padreId = $(this).data('padre');
        const capas = capasPorPadre[padreId] || [];
        const marcadores = marcadoresPorPadre[padreId] || [];

        $('.delegacion-item').removeClass('active');
        $(this).addClass('active');

        if (!capas.length && !marcadores.length) {
            return;
        }

        const grupo = L.featureGroup([...capas, ...marcadores]);

        mapa.fitBounds(grupo.getBounds(), {
            padding: [35, 35],
            maxZoom: 12
        });

        capas.forEach(layer => {
            layer.setStyle({
                weight: 3,
                fillOpacity: 1
            });
        });

        marcadores.forEach(marker => {
            marker.setZIndexOffset(600);
        });

        setTimeout(() => {
            capas.forEach(layer => {
                layer.setStyle({
                    weight: 1,
                    fillOpacity: 0.82
                });
            });

            marcadores.forEach(marker => {
                marker.setZIndexOffset(0);
            });
        }, 1200);
    });

    function registrarDelegacionPorMunicipio(delegacion) {
        [
            delegacion.municipio,
            delegacion.nombre
        ].forEach(nombre => {
            const municipio = normalizar(nombre);

            if (municipio && !delegacionesPorMunicipio[municipio]) {
                delegacionesPorMunicipio[municipio] = delegacion;
            }
        });
    }

    function crearMarcadorDelegacion(delegacion, featuresMunicipios) {
        const coordenadas = obtenerCoordenadas(delegacion);

        if (!coordenadas) {
            return null;
        }

        const padreId = delegacion.delegacion_padre_id || delegacion.id;
        const marker = L.marker(coordenadas, {
            icon: iconoDelegacion,
            title: delegacion.nombre || 'Delegación',
            riseOnHover: true
        });

        marker.bindPopup(crearPopupDelegacion(
            delegacion,
            coordenadas,
            obtenerMunicipioPorCoordenadas(coordenadas, featuresMunicipios)
        ));
        marker.addTo(marcadoresDelegaciones);

        if (!marcadoresPorPadre[padreId]) {
            marcadoresPorPadre[padreId] = [];
        }

        marcadoresPorPadre[padreId].push(marker);

        return marker;
    }

    function obtenerMunicipioPorCoordenadas(coordenadas, features) {
        const feature = features.find(item => puntoDentroDeFeature(coordenadas, item));

        return feature ? obtenerMunicipio(feature) : null;
    }

    function obtenerDelegacionParaFeature(feature) {
        const nombre = obtenerMunicipio(feature);
        const delegacionPorNombre = delegacionesPorMunicipio[normalizar(nombre)];

        if (delegacionPorNombre) {
            return delegacionPorNombre;
        }

        return delegacionesConCoordenadas.find(item => {
            return puntoDentroDeFeature(item.coordenadas, feature);
        })?.delegacion || null;
    }

    function obtenerCoordenadas(delegacion) {
        const lat = Number.parseFloat(delegacion.lat);
        const lng = Number.parseFloat(delegacion.lng);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return null;
        }

        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            return null;
        }

        return [lat, lng];
    }

    function puntoDentroDeFeature(coordenadas, feature) {
        const geometry = feature?.geometry;

        if (!geometry) {
            return false;
        }

        if (geometry.type === 'Polygon') {
            return puntoDentroDePoligono(coordenadas, geometry.coordinates);
        }

        if (geometry.type === 'MultiPolygon') {
            return geometry.coordinates.some(poligono => puntoDentroDePoligono(coordenadas, poligono));
        }

        return false;
    }

    function puntoDentroDePoligono(coordenadas, anillos) {
        if (!Array.isArray(anillos) || !anillos.length) {
            return false;
        }

        const dentroExterior = puntoDentroDeAnillo(coordenadas, anillos[0]);

        if (!dentroExterior) {
            return false;
        }

        return !anillos.slice(1).some(anillo => puntoDentroDeAnillo(coordenadas, anillo));
    }

    function puntoDentroDeAnillo(coordenadas, anillo) {
        const lat = coordenadas[0];
        const lng = coordenadas[1];
        let dentro = false;

        for (let i = 0, j = anillo.length - 1; i < anillo.length; j = i++) {
            const xi = Number(anillo[i][0]);
            const yi = Number(anillo[i][1]);
            const xj = Number(anillo[j][0]);
            const yj = Number(anillo[j][1]);

            const cruza = ((yi > lat) !== (yj > lat)) &&
                (lng < ((xj - xi) * (lat - yi)) / (yj - yi) + xi);

            if (cruza) {
                dentro = !dentro;
            }
        }

        return dentro;
    }

    function crearPopupDelegacion(delegacion, coordenadas, municipioMapa) {
        const nombre = escaparHtml(delegacion.nombre || 'Delegación');
        const clave = delegacion.clave ? escaparHtml(delegacion.clave) : 'Sin clave';
        const municipioRegistrado = delegacion.municipio ? escaparHtml(delegacion.municipio) : '';
        const municipioDetectado = municipioMapa ? escaparHtml(municipioMapa) : '';
        const municipio = municipioDetectado || municipioRegistrado || 'Sin municipio registrado';
        const direccion = delegacion.direccion_completa
            ? escaparHtml(delegacion.direccion_completa)
            : 'Sin dirección registrada';
        const muestraRegistro = municipioDetectado &&
            municipioRegistrado &&
            normalizar(municipioMapa) !== normalizar(delegacion.municipio);
        const tipo = delegacion.delegacion_padre_id ? 'Delegación hija' : 'Delegación padre';
        const mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(coordenadas[0] + ',' + coordenadas[1]);

        return `
            <div class="delegacion-popup">
                <div class="delegacion-popup__titulo">${nombre}</div>
                <div class="delegacion-popup__dato"><strong>Clave:</strong> ${clave}</div>
                <div class="delegacion-popup__dato"><strong>Municipio:</strong> ${municipio}</div>
                <div class="delegacion-popup__dato"><strong>Dirección:</strong> ${direccion}</div>
                ${muestraRegistro ? `<div class="delegacion-popup__dato"><strong>Registro:</strong> ${municipioRegistrado}</div>` : ''}
                <div class="delegacion-popup__dato"><strong>Tipo:</strong> ${tipo}</div>
                <a class="btn btn-primary btn-sm delegacion-popup__maps"
                   href="${mapsUrl}"
                   target="_blank"
                   rel="noopener noreferrer">
                    <i class="fa-solid fa-location-arrow"></i> Abrir en Google Maps
                </a>
            </div>
        `;
    }

    function obtenerMunicipio(feature) {
        return feature.properties.NOMGEO ||
               feature.properties.nomgeo ||
               feature.properties.NOMBRE ||
               feature.properties.nombre ||
               feature.properties.MUNICIPIO ||
               feature.properties.municipio ||
               '';
    }

    function normalizar(txt) {
        let valor = String(txt || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase()
            .trim();

        const equivalencias = {
            'ACUITZIO': 'ACUITZIO DEL CANJE',
            'ARIO': 'ARIO DE ROSALES',
            'COALCOMAN DE VAZQUEZ PALLARES': 'COALCOMAN',
            'HIDALGO': 'HIDALGO',
            'CIUDAD HIDALGO': 'HIDALGO',
            'JOSE SIXTO VERDUZCO': 'PASTOR ORTIZ',
            'MUGICA': 'NUEVA ITALIA',
            'SALVADOR ESCALANTE': 'SANTA CLARA DEL COBRE',
            'TANGAMANDAPIO': 'SANTIAGO TANGAMANDAPIO',
            'TUXPAN': 'TUXPAN',
            'MARAVATIO': 'MARAVATIO',
            'LAZARO CARDENAS': 'LAZARO CARDENAS'
        };

        return equivalencias[valor] || valor;
    }

    function escaparHtml(txt) {
        return String(txt || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@stop
