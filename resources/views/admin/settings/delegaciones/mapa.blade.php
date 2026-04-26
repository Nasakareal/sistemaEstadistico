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
    const padres = {};
    const capasPorPadre = {};

    Promise.all([
        fetch('{{ asset('geo/michoacan.json') }}').then(r => r.json()),
        fetch('{{ route('mapa.delegaciones.data') }}').then(r => r.json())
    ]).then(([geojson, data]) => {
        data.forEach(d => {
            const municipio = normalizar(d.municipio || d.nombre);

            if (municipio) {
                delegacionesPorMunicipio[municipio] = d;
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

        const geoLayer = L.geoJSON(geojson, {
            style: function(feature) {
                const nombre = obtenerMunicipio(feature);
                const delegacion = delegacionesPorMunicipio[normalizar(nombre)];

                return {
                    color: '#f8fafc',
                    weight: 1,
                    fillColor: delegacion ? delegacion.color : '#334155',
                    fillOpacity: delegacion ? 0.82 : 0.28
                };
            },
            onEachFeature: function(feature, layer) {
                const nombre = obtenerMunicipio(feature);
                const delegacion = delegacionesPorMunicipio[normalizar(nombre)];

                if (delegacion) {
                    const padreId = delegacion.delegacion_padre_id || delegacion.id;

                    if (!capasPorPadre[padreId]) {
                        capasPorPadre[padreId] = [];
                    }

                    capasPorPadre[padreId].push(layer);
                }

                let html = '<strong>Municipio:</strong> ' + nombre;

                if (delegacion) {
                    html += '<br><strong>Delegación:</strong> ' + delegacion.nombre;
                    html += '<br><strong>Clave:</strong> ' + delegacion.clave;
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
            html += `
                <div class="delegacion-item" data-padre="${padre.id}">
                    <div class="delegacion-info">
                        <span class="color-box" style="background:${padre.color}"></span>
                        <div>
                            <div class="delegacion-nombre">${padre.nombre}</div>
                            <div class="delegacion-municipio">${padre.municipio}</div>
                        </div>
                    </div>
                    <span class="badge badge-primary badge-hijas">${padre.total_hijas} hijas</span>
                </div>
            `;
        });

        $('#listaDelegaciones').html(html || '<div class="p-3 text-muted">Sin delegaciones padre.</div>');
    }

    $(document).on('click', '.delegacion-item', function() {
        const padreId = $(this).data('padre');
        const capas = capasPorPadre[padreId] || [];

        $('.delegacion-item').removeClass('active');
        $(this).addClass('active');

        if (!capas.length) {
            return;
        }

        const grupo = L.featureGroup(capas);

        mapa.fitBounds(grupo.getBounds(), {
            padding: [35, 35]
        });

        capas.forEach(layer => {
            layer.setStyle({
                weight: 3,
                fillOpacity: 1
            });
        });

        setTimeout(() => {
            capas.forEach(layer => {
                layer.setStyle({
                    weight: 1,
                    fillOpacity: 0.82
                });
            });
        }, 1200);
    });

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
            'HIDALGO': 'HIDALGO',
            'CIUDAD HIDALGO': 'HIDALGO',
            'TUXPAN': 'TUXPAN',
            'MARAVATIO': 'MARAVATIO',
            'LAZARO CARDENAS': 'LAZARO CARDENAS'
        };

        return equivalencias[valor] || valor;
    }
</script>
@stop
