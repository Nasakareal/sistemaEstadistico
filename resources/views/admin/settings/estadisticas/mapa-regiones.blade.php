@extends('adminlte::page')

@section('title', 'Mapa Regional Michoacán')

@section('content_header')
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between">
        <h1 class="mb-2 mb-sm-0">Mapa Regional Michoacán</h1>

        <a href="{{ route('estadisticas.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Regresar
        </a>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        #mapaRegionalMichoacan {
            width: 100%;
            min-height: 76vh;
            background: #eef2f7;
            border-radius: 8px;
        }

        .mapa-regional-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 15px;
        }

        .mapa-regional-leyenda {
            max-height: 76vh;
            overflow-y: auto;
        }

        .mapa-regional-leyenda__item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        .mapa-regional-leyenda__color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
            border: 1px solid rgba(15, 23, 42, 0.18);
            flex: 0 0 auto;
        }

        .mapa-regional-error {
            display: none;
        }

        @media (max-width: 992px) {
            .mapa-regional-layout {
                grid-template-columns: 1fr;
            }

            #mapaRegionalMichoacan {
                min-height: 62vh;
            }

            .mapa-regional-leyenda {
                max-height: none;
            }
        }
    </style>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Regiones por municipio</h3>
        </div>

        <div class="card-body">
            <div class="alert alert-danger mapa-regional-error" id="mapaRegionalError">
                No se pudo cargar el mapa regional.
            </div>

            <div class="mapa-regional-layout">
                <div id="mapaRegionalMichoacan"></div>

                <div class="card mapa-regional-leyenda mb-0">
                    <div class="card-header">
                        <h3 class="card-title">Municipios configurados</h3>
                    </div>
                    <div class="card-body py-2" id="mapaRegionalLeyenda"></div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const coloresRegion = {
            "00": "#8E44AD",
            "01": "#8E44AD",
            "02": "#8E44AD",
            "03": "#8E44AD",
            "04": "#8E44AD",
            "05": "#8E44AD",
            "07": "#8E44AD",
            "08": "#8E44AD",

            "09": "#B7D800",
            "10": "#B7D800",
            "11": "#B7D800",

            "12": "#4FC3F7",
            "13": "#4FC3F7",
            "14": "#4FC3F7",
            "15": "#4FC3F7",
            "16": "#4FC3F7",
            "17": "#4FC3F7",
            "18": "#4FC3F7",
            "19": "#4FC3F7",

            "20": "#616161",
            "21": "#616161",
            "22": "#616161",
            "23": "#616161",
            "24": "#616161",

            "25": "#D6B63A",
            "26": "#D6B63A",
            "27": "#D6B63A",
            "28": "#D6B63A",
            "29": "#D6B63A",
            "30": "#D6B63A",

            "31": "#E67E22",
            "32": "#E67E22",
            "33": "#E67E22",

            "34": "#C49A00",
            "35": "#C49A00",
            "36": "#C49A00",

            "37": "#E8B08A",
            "38": "#E8B08A",

            "39": "#E91E63",
            "40": "#E91E63",
            "41": "#E91E63",
            "42": "#E91E63",
            "43": "#E91E63",

            "44": "#9CCC65",
            "45": "#9CCC65"
        };

        const municipioAClave = {
            "Morelia": "00",
            "Pátzcuaro": "01",
            "Zinapécuaro": "02",
            "Cuitzeo": "03",
            "Acuitzio": "04",
            "Quiroga": "05",
            "Salvador Escalante": "07",
            "Tarímbaro": "08",

            "Jiquilpan": "09",
            "Briseñas": "10",
            "Marcos Castellanos": "10",
            "Venustiano Carranza": "11",

            "Zamora": "12",
            "Jacona": "13",
            "Tangancícuaro": "14",
            "Purépero": "15",
            "Zacapu": "16",
            "Tangamandapio": "17",
            "Coeneo": "18",
            "Ecuandureo": "19",

            "La Piedad": "20",
            "Yurécuaro": "21",
            "Pastor Ortiz": "22",
            "Puruándiro": "23",
            "Vista Hermosa": "24",

            "Uruapan": "25",
            "Los Reyes": "26",
            "Paracho": "27",
            "Taretan": "28",
            "Tacámbaro": "29",
            "Ario": "30",

            "Apatzingán": "31",
            "Múgica": "32",
            "Gabriel Zamora": "33",

            "Huetamo": "34",
            "Nocupétaro": "35",
            "Carácuaro": "36",

            "Coalcomán de Vázquez Pallares": "37",
            "Coahuayana": "38",

            "Zitácuaro": "39",
            "Tlalpujahua": "40",
            "Maravatío": "41",
            "Tuxpan": "42",
            "Hidalgo": "43",

            "Lázaro Cárdenas": "44",
            "Arteaga": "45"
        };

        function pintarMunicipio(feature) {
            const nombre = feature.properties.NOMGEO;
            const clave = municipioAClave[nombre];
            const color = coloresRegion[clave] || "#263746";

            return {
                fillColor: color,
                fillOpacity: clave ? 0.9 : 0.25,
                color: "#ffffff",
                weight: 0.7
            };
        }

        const mapaRegional = L.map('mapaRegionalMichoacan', {
            zoomControl: true,
            attributionControl: false
        }).setView([19.15, -101.9], 8);

        fetch('{{ asset('geo/michoacan.json') }}')
            .then(response => response.json())
            .then(geojson => {
                const capaMunicipios = L.geoJSON(geojson, {
                    style: pintarMunicipio,
                    onEachFeature: function(feature, layer) {
                        const nombre = feature.properties.NOMGEO || 'Municipio';
                        const clave = municipioAClave[nombre] || 'Sin clave';

                        layer.bindPopup(
                            '<strong>Municipio:</strong> ' + escaparHtml(nombre) +
                            '<br><strong>Clave:</strong> ' + escaparHtml(clave)
                        );

                        layer.on('mouseover', function() {
                            layer.setStyle({
                                fillOpacity: 1,
                                weight: 1.5
                            });
                        });

                        layer.on('mouseout', function() {
                            layer.setStyle(pintarMunicipio(feature));
                        });
                    }
                }).addTo(mapaRegional);

                mapaRegional.fitBounds(capaMunicipios.getBounds(), {
                    padding: [20, 20]
                });

                pintarLeyenda();
            })
            .catch(() => {
                document.getElementById('mapaRegionalError').style.display = 'block';
            });

        function pintarLeyenda() {
            const leyenda = document.getElementById('mapaRegionalLeyenda');
            const municipios = Object.entries(municipioAClave)
                .sort((a, b) => a[1].localeCompare(b[1], 'es-MX', { numeric: true }));

            leyenda.innerHTML = municipios.map(([municipio, clave]) => {
                const color = coloresRegion[clave] || '#263746';

                return `
                    <div class="mapa-regional-leyenda__item">
                        <span class="mapa-regional-leyenda__color" style="background:${color}"></span>
                        <span>${escaparHtml(clave)} - ${escaparHtml(municipio)}</span>
                    </div>
                `;
            }).join('');
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
