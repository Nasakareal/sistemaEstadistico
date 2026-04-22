document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form_dispositivo');

    const horaInput = document.getElementById('hora');
    const horaInicioInput = document.getElementById('hora_inicio');
    const horaFinInput = document.getElementById('hora_fin');
    const selectDispositivo = document.getElementById('operativo_dispositivo_catalogo_id');

    const resumen = document.getElementById('bloque_resumen_dispositivo');
    const titulo = document.getElementById('titulo_dispositivo_dinamico');

    const seccionDinamica = document.getElementById('seccion_datos_dinamicos');
    const seccionExtra = document.getElementById('seccion_generales_extra');
    const seccionObservaciones = document.getElementById('seccion_observaciones');
    const seccionGeorreferencia = document.getElementById('seccion_georreferencia');
    const seccionNarrativa = document.getElementById('seccion_narrativa');
    const seccionApoyoUsuario = document.getElementById('seccion_apoyo_usuario');
    const seccionResponsable = document.getElementById('seccion_responsable');
    const seccionFotos = document.getElementById('seccion_fotos');

    const configNode = document.getElementById('dispositivos-config');
    const allCamposNode = document.getElementById('all-campos-config');

    let config = {};
    let allCampos = [];

    try {
        config = configNode ? JSON.parse(configNode.textContent) : {};
    } catch (error) {
        console.error('Error al parsear dispositivos-config:', error);
        config = {};
    }

    try {
        allCampos = allCamposNode ? JSON.parse(allCamposNode.textContent) : [];
    } catch (error) {
        console.error('Error al parsear all-campos-config:', error);
        allCampos = [];
    }

    const fotosInput = document.getElementById('fotos');
    const previewContainer = document.getElementById('preview_fotos_container');

    const btnSubmit = document.getElementById('btn_submit');
    const btnGeo = document.getElementById('btn_geo');
    const btnGeoClear = document.getElementById('btn_geo_clear');
    const geoStatus = document.getElementById('geo_status');

    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const coordenadasTextoInput = document.getElementById('coordenadas_texto');
    const calidadGeoInput = document.getElementById('calidad_geo');
    const fuenteUbicacionInput = document.getElementById('fuente_ubicacion');

    console.log('GCM JS cargado');
    console.log('btnGeo:', btnGeo);
    console.log('latInput:', latInput);
    console.log('lngInput:', lngInput);
    console.log('geoStatus:', geoStatus);

    function inicializarHora(input) {
        if (!input) {
            return;
        }

        if (input.value) {
            input.value = String(input.value).substring(0, 5);
        }

        if (window.flatpickr) {
            flatpickr(input, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                allowInput: true
            });
        }
    }

    inicializarHora(horaInput);
    inicializarHora(horaInicioInput);
    inicializarHora(horaFinInput);

    function normalizarNombre(nombre) {
        return String(nombre || '')
            .trim()
            .toUpperCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function obtenerClaveConfig(nombre) {
        const normalizado = normalizarNombre(nombre);

        if (config[normalizado]) {
            return normalizado;
        }

        for (const key in config) {
            if (normalizarNombre(key) === normalizado) {
                return key;
            }
        }

        return null;
    }

    function ocultarTodosLosCampos() {
        document.querySelectorAll('.campo-dinamico').forEach(function (item) {
            item.classList.add('campo-oculto');

            const input = item.querySelector('input, select, textarea');
            if (input) {
                input.disabled = true;
            }
        });
    }

    function limpiarCamposOcultos() {
        document.querySelectorAll('.campo-dinamico').forEach(function (item) {
            if (!item.classList.contains('campo-oculto')) {
                return;
            }

            const input = item.querySelector('input, select, textarea');
            if (!input) {
                return;
            }

            if (input.tagName === 'TEXTAREA') {
                input.value = '';
                return;
            }

            if (input.type === 'number') {
                input.value = 0;
                return;
            }

            if (input.type === 'checkbox') {
                input.checked = false;
                return;
            }

            if (input.type === 'file') {
                input.value = '';
                return;
            }

            input.value = '';
        });
    }

    function mostrarCampos(campos) {
        campos.forEach(function (nombreCampo) {
            const bloque = document.querySelector('.campo-dinamico[data-campo="' + nombreCampo + '"]');

            if (!bloque) {
                return;
            }

            bloque.classList.remove('campo-oculto');

            const input = bloque.querySelector('input, select, textarea');
            if (input) {
                input.disabled = false;
            }
        });
    }

    function ocultarSecciones() {
        if (resumen) resumen.classList.add('d-none');
        if (seccionDinamica) seccionDinamica.classList.add('d-none');
        if (seccionExtra) seccionExtra.classList.add('d-none');
        if (seccionObservaciones) seccionObservaciones.classList.add('d-none');
        if (seccionGeorreferencia) seccionGeorreferencia.classList.add('d-none');
        if (seccionNarrativa) seccionNarrativa.classList.add('d-none');
        if (seccionApoyoUsuario) seccionApoyoUsuario.classList.add('d-none');
        if (seccionResponsable) seccionResponsable.classList.add('d-none');
        if (seccionFotos) seccionFotos.classList.add('d-none');
    }

    function mostrarSeccionesBase() {
        if (resumen) resumen.classList.remove('d-none');
        if (seccionDinamica) seccionDinamica.classList.remove('d-none');
        if (seccionExtra) seccionExtra.classList.remove('d-none');
        if (seccionObservaciones) seccionObservaciones.classList.remove('d-none');
        if (seccionGeorreferencia) seccionGeorreferencia.classList.remove('d-none');
        if (seccionNarrativa) seccionNarrativa.classList.remove('d-none');
        if (seccionResponsable) seccionResponsable.classList.remove('d-none');
        if (seccionFotos) seccionFotos.classList.remove('d-none');
    }

    function requiereApoyoUsuario(nombreNormalizado) {
        return (
            nombreNormalizado.includes('CABALLERO DEL CAMINO') ||
            nombreNormalizado.includes('CABALLEROS DEL CAMINO') ||
            nombreNormalizado.includes('RSV') ||
            nombreNormalizado.includes('APOYO') ||
            nombreNormalizado.includes('PATRULLAJE')
        );
    }

    function setGeoUI() {
        const lat = latInput ? String(latInput.value || '').trim() : '';
        const lng = lngInput ? String(lngInput.value || '').trim() : '';
        const calidad = calidadGeoInput ? String(calidadGeoInput.value || '').trim() : '';

        if (lat && lng) {
            if (geoStatus) {
                geoStatus.textContent = 'OK: ' + lat + ', ' + lng + (calidad ? ' (±' + calidad + ' m)' : '');
            }

            if (btnGeoClear) {
                btnGeoClear.style.display = 'inline-block';
            }
        } else {
            if (geoStatus) {
                geoStatus.textContent = 'Sin coordenadas';
            }

            if (btnGeoClear) {
                btnGeoClear.style.display = 'none';
            }
        }
    }

    function toastError(msg) {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Ubicación',
                text: msg
            });
        } else {
            alert(msg);
        }
    }

    function toastOk(msg) {
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Ubicación',
                text: msg,
                timer: 1600,
                showConfirmButton: false
            });
        }
    }

    function limpiarUbicacion() {
        if (latInput) latInput.value = '';
        if (lngInput) lngInput.value = '';
        if (coordenadasTextoInput) coordenadasTextoInput.value = '';
        if (calidadGeoInput) calidadGeoInput.value = '';
        if (fuenteUbicacionInput) fuenteUbicacionInput.value = '';
        setGeoUI();
    }

    function capturarUbicacion() {
        console.log('Click en btn_geo');

        if (!navigator.geolocation) {
            toastError('Tu navegador no soporta geolocalización.');
            return;
        }

        if (!latInput || !lngInput) {
            console.error('No se encontraron los inputs lat/lng');
            toastError('No se encontraron los campos de coordenadas.');
            return;
        }

        if (geoStatus) {
            geoStatus.textContent = 'Obteniendo ubicación...';
        }

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                console.log('Geolocalización OK:', pos);

                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const acc = pos.coords.accuracy;

                const latFix = typeof lat === 'number' ? lat.toFixed(7) : '';
                const lngFix = typeof lng === 'number' ? lng.toFixed(7) : '';

                latInput.value = latFix;
                lngInput.value = lngFix;

                if (coordenadasTextoInput) {
                    coordenadasTextoInput.value = latFix + ',' + lngFix;
                }

                if (calidadGeoInput) {
                    calidadGeoInput.value = typeof acc === 'number' ? String(Math.round(acc)) : '';
                }

                if (fuenteUbicacionInput) {
                    fuenteUbicacionInput.value = 'GPS_WEB';
                }

                setGeoUI();
                toastOk('Coordenadas capturadas.');
            },
            function (err) {
                console.error('Error geolocalización:', err);

                let msg = 'No se pudo obtener la ubicación.';

                if (err && err.code === 1) msg = 'Permiso denegado. Activa la ubicación y permite el acceso.';
                if (err && err.code === 2) msg = 'Ubicación no disponible.';
                if (err && err.code === 3) msg = 'Tiempo de espera agotado. Intenta otra vez.';

                setGeoUI();
                toastError(msg);
            },
            {
                enableHighAccuracy: true,
                timeout: 12000,
                maximumAge: 0
            }
        );
    }

    function capturarUbicacionAutomaticaSilenciosa() {
        const lat = latInput ? String(latInput.value || '').trim() : '';
        const lng = lngInput ? String(lngInput.value || '').trim() : '';

        if (lat && lng) {
            setGeoUI();
            return;
        }

        if (!navigator.geolocation) {
            setGeoUI();
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const acc = pos.coords.accuracy;

                const latFix = typeof lat === 'number' ? lat.toFixed(7) : '';
                const lngFix = typeof lng === 'number' ? lng.toFixed(7) : '';

                if (latInput) latInput.value = latFix;
                if (lngInput) lngInput.value = lngFix;
                if (coordenadasTextoInput) coordenadasTextoInput.value = latFix + ',' + lngFix;
                if (calidadGeoInput) calidadGeoInput.value = typeof acc === 'number' ? String(Math.round(acc)) : '';
                if (fuenteUbicacionInput) fuenteUbicacionInput.value = 'GPS_WEB';

                setGeoUI();
            },
            function (err) {
                console.warn('Geolocalización automática falló:', err);
                setGeoUI();
            },
            {
                enableHighAccuracy: true,
                timeout: 12000,
                maximumAge: 0
            }
        );
    }

    function actualizarFormulario() {
        const selectedOption = selectDispositivo
            ? selectDispositivo.options[selectDispositivo.selectedIndex]
            : null;

        ocultarTodosLosCampos();
        ocultarSecciones();

        if (!selectedOption || !selectedOption.dataset.nombre) {
            limpiarCamposOcultos();
            return;
        }

        const nombre = selectedOption.dataset.nombre;
        const clave = obtenerClaveConfig(nombre);

        if (!clave || !config[clave]) {
            limpiarCamposOcultos();
            return;
        }

        mostrarSeccionesBase();

        if (titulo) {
            titulo.textContent = config[clave].titulo;
        }

        mostrarCampos(config[clave].campos);
        limpiarCamposOcultos();

        const nombreNormalizado = normalizarNombre(nombre);

        if (requiereApoyoUsuario(nombreNormalizado) && seccionApoyoUsuario) {
            seccionApoyoUsuario.classList.remove('d-none');
        }

        if (seccionGeorreferencia && !seccionGeorreferencia.classList.contains('d-none')) {
            capturarUbicacionAutomaticaSilenciosa();
        }

        syncRelacionadosCounters();
    }

    function parseJsonNode(id, fallback) {
        const node = document.getElementById(id);

        if (!node) {
            return fallback;
        }

        try {
            return JSON.parse(node.textContent || '');
        } catch (error) {
            console.error('Error al parsear ' + id + ':', error);
            return fallback;
        }
    }

    const vehiculosDisponibles = parseJsonNode('guardianes-vehiculos-disponibles', []);
    const vehiculosIniciales = parseJsonNode('guardianes-vehiculos-iniciales', []);
    const vehiculosMetaInicial = parseJsonNode('guardianes-vehiculos-meta-inicial', {});
    const personasIniciales = parseJsonNode('guardianes-personas-iniciales', []);

    const relacionadosInputs = document.getElementById('guardianesRelacionadosInputs');
    const vehiculosList = document.getElementById('guardianesVehiculosList');
    const vehiculosEmpty = document.getElementById('guardianesVehiculosEmpty');
    const vehiculosTotal = document.getElementById('guardianesVehiculosTotal');
    const personasList = document.getElementById('guardianesPersonasList');
    const personasEmpty = document.getElementById('guardianesPersonasEmpty');
    const personasTotal = document.getElementById('guardianesPersonasTotal');

    const vehiculoQrRaw = document.getElementById('guardianesVehiculoQrRaw');
    const btnAutocompletarVehiculoQr = document.getElementById('btnAutocompletarVehiculoQr');
    const vehiculoRol = document.getElementById('guardianesVehiculoRol');
    const vehiculoObservaciones = document.getElementById('guardianesVehiculoObservaciones');
    const vehiculoMarca = document.getElementById('guardianesVehiculoMarca');
    const vehiculoModelo = document.getElementById('guardianesVehiculoModelo');
    const vehiculoTipoGeneral = document.getElementById('guardianesVehiculoTipoGeneral');
    const vehiculoTipo = document.getElementById('guardianesVehiculoTipo');
    const vehiculoLinea = document.getElementById('guardianesVehiculoLinea');
    const vehiculoColor = document.getElementById('guardianesVehiculoColor');
    const vehiculoPlacas = document.getElementById('guardianesVehiculoPlacas');
    const vehiculoEstadoPlacas = document.getElementById('guardianesVehiculoEstadoPlacas');
    const vehiculoSerie = document.getElementById('guardianesVehiculoSerie');
    const vehiculoCapacidad = document.getElementById('guardianesVehiculoCapacidad');
    const vehiculoTipoServicio = document.getElementById('guardianesVehiculoTipoServicio');
    const vehiculoTarjetaNombre = document.getElementById('guardianesVehiculoTarjetaNombre');
    const vehiculoGrua = document.getElementById('guardianesVehiculoGrua');
    const vehiculoCorralon = document.getElementById('guardianesVehiculoCorralon');
    const vehiculoAseguradora = document.getElementById('guardianesVehiculoAseguradora');
    const vehiculoMontoDanos = document.getElementById('guardianesVehiculoMontoDanos');
    const vehiculoPartesDanadas = document.getElementById('guardianesVehiculoPartesDanadas');
    const vehiculoAntecedente = document.getElementById('guardianesVehiculoAntecedente');
    const btnAgregarVehiculoRelacionado = document.getElementById('btnAgregarVehiculoRelacionado');

    const personaNombre = document.getElementById('guardianesPersonaNombre');
    const personaTipo = document.getElementById('guardianesPersonaTipo');
    const personaCurp = document.getElementById('guardianesPersonaCurp');
    const personaTelefono = document.getElementById('guardianesPersonaTelefono');
    const personaDomicilio = document.getElementById('guardianesPersonaDomicilio');
    const personaSexo = document.getElementById('guardianesPersonaSexo');
    const personaOcupacion = document.getElementById('guardianesPersonaOcupacion');
    const personaEdad = document.getElementById('guardianesPersonaEdad');
    const personaTipoLicencia = document.getElementById('guardianesPersonaTipoLicencia');
    const personaEstadoLicencia = document.getElementById('guardianesPersonaEstadoLicencia');
    const personaVigenciaLicencia = document.getElementById('guardianesPersonaVigenciaLicencia');
    const personaNumeroLicencia = document.getElementById('guardianesPersonaNumeroLicencia');
    const personaPermanente = document.getElementById('guardianesPersonaPermanente');
    const personaCinturon = document.getElementById('guardianesPersonaCinturon');
    const personaAntecedentes = document.getElementById('guardianesPersonaAntecedentes');
    const personaCertificadoLesiones = document.getElementById('guardianesPersonaCertificadoLesiones');
    const personaCertificadoAlcoholemia = document.getElementById('guardianesPersonaCertificadoAlcoholemia');
    const personaAlientoEtilico = document.getElementById('guardianesPersonaAlientoEtilico');
    const personaObservaciones = document.getElementById('guardianesPersonaObservaciones');
    const btnAgregarPersonaRelacionada = document.getElementById('btnAgregarPersonaRelacionada');

    const vehiculosById = {};

    vehiculosDisponibles.forEach(function (vehiculo) {
        vehiculosById[Number(vehiculo.id)] = vehiculo;
    });

    let vehiculosRelacionados = Array.isArray(vehiculosIniciales)
        ? vehiculosIniciales.map(function (id) {
            const numericId = Number(id);
            const meta = vehiculosMetaInicial[id] || vehiculosMetaInicial[String(numericId)] || {};

            if (!vehiculosById[numericId]) {
                vehiculosById[numericId] = {
                    id: numericId,
                    label: '#' + numericId + ' VEHICULO RELACIONADO',
                    placas: '',
                    serie: '',
                    marca: '',
                    linea: '',
                    tipo: '',
                    color: '',
                    antecedente_vehiculo: false
                };
            }

            return {
                id: numericId,
                rol: meta.rol || 'IMPACTADO',
                observaciones: meta.observaciones || '',
                vehiculo: vehiculosById[numericId]
            };
        }).filter(function (item) {
            return item.id > 0;
        })
        : [];

    let personasRelacionadas = Array.isArray(personasIniciales)
        ? personasIniciales.map(function (persona) {
            return {
                nombre: persona.nombre || '',
                tipo_participacion: persona.tipo_participacion || 'IMPACTADA',
                curp: persona.curp || '',
                telefono: persona.telefono || '',
                domicilio: persona.domicilio || '',
                sexo: persona.sexo || '',
                ocupacion: persona.ocupacion || '',
                edad: persona.edad || '',
                tipo_licencia: persona.tipo_licencia || '',
                estado_licencia: persona.estado_licencia || '',
                vigencia_licencia: persona.vigencia_licencia || '',
                numero_licencia: persona.numero_licencia || '',
                permanente: !!persona.permanente,
                cinturon: !!persona.cinturon,
                antecedentes: !!persona.antecedentes,
                certificado_lesiones: !!persona.certificado_lesiones,
                certificado_alcoholemia: !!persona.certificado_alcoholemia,
                aliento_etilico: !!persona.aliento_etilico,
                observaciones: persona.observaciones || ''
            };
        }).filter(function (persona) {
            return persona.nombre;
        })
        : [];

    function escapeHtmlRelacionados(value) {
        return (value === undefined || value === null ? '' : String(value)).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function normalizarRelacionado(value) {
        return String(value || '')
            .trim()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase();
    }

    function scannerToken(value) {
        return normalizarRelacionado(value).replace(/[^A-Z0-9]/g, '');
    }

    function crearHiddenRelacionado(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value === undefined || value === null ? '' : value;

        return input;
    }

    function toastRelacionado(icon, title, text) {
        if (window.Swal) {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                timer: icon === 'success' ? 1300 : undefined,
                showConfirmButton: icon !== 'success'
            });
            return;
        }

        alert(text || title);
    }

    function valueOf(input) {
        return input ? String(input.value || '').trim() : '';
    }

    function checkedOf(input) {
        return !!(input && input.checked);
    }

    function setValue(input, value) {
        if (!input || value === undefined || value === null || value === '') {
            return;
        }

        input.value = String(value).toLocaleUpperCase('es-MX');
    }

    function normalizeQrKey(value) {
        return normalizarRelacionado(value).replace(/[^A-Z0-9]/g, '');
    }

    function cleanQrValue(value) {
        return String(value || '').replace(/^[\s:=>#-]+/, '').trim();
    }

    function pickQrValue(pairs, aliases) {
        for (let i = 0; i < aliases.length; i++) {
            const key = normalizeQrKey(aliases[i]);
            if (pairs[key]) {
                return pairs[key];
            }
        }

        return '';
    }

    function looseQr(raw, regex) {
        const match = regex.exec(raw);
        return match && match[1] ? cleanQrValue(match[1]) : '';
    }

    function parseTarjetaCirculacion(raw) {
        const text = String(raw || '').trim();
        const pairs = {};

        text.split(/[\n;|]+/).forEach(function (part) {
            const match = part.match(/^\s*([^:=#>\-]{2,45})\s*(?:[:=#]|=>|-)\s*(.+?)\s*$/);
            if (!match) {
                return;
            }

            const key = normalizeQrKey(match[1]);
            const value = cleanQrValue(match[2]);
            if (key && value && !pairs[key]) {
                pairs[key] = value;
            }
        });

        const modeloRaw = pickQrValue(pairs, ['anio', 'año', 'ano', 'modelo', 'year']);
        const modeloMatch = modeloRaw.match(/(19|20)\d{2}/);
        const tipoRaw = pickQrValue(pairs, ['tipo', 'clase', 'tipo vehiculo', 'clase vehiculo', 'carroceria', 'carrocería']);
        const tipoServicioRaw = pickQrValue(pairs, ['servicio', 'tipo servicio', 'tipo de servicio', 'uso', 'clase servicio']);

        return {
            marca: pickQrValue(pairs, ['marca', 'marca vehiculo', 'marca del vehiculo', 'brand']),
            modelo: modeloMatch ? modeloMatch[0] : '',
            linea: pickQrValue(pairs, ['linea', 'línea', 'submarca', 'version', 'versión', 'descripcion']),
            color: pickQrValue(pairs, ['color', 'color vehiculo', 'color del vehiculo']),
            placas: scannerToken(pickQrValue(pairs, ['placa', 'placas', 'matricula', 'matrícula', 'lamina', 'lámina']) || looseQr(text, /(?:placas?|matr[ií]cula|l[aá]mina)\s*[:#\-]?\s*([A-Z0-9\-\s]{5,15})/i)),
            estado_placas: scannerToken(pickQrValue(pairs, ['estado placas', 'entidad placas', 'entidad', 'estado', 'expedido en'])),
            serie: scannerToken(pickQrValue(pairs, ['serie', 'no serie', 'numero de serie', 'número de serie', 'niv', 'vin', 'nvi', 'motor']) || looseQr(text, /(?:serie|niv|vin)\s*[:#\-]?\s*([A-HJ-NPR-Z0-9]{6,17})/i)),
            tipo_servicio: tipoServicioRaw ? normalizarRelacionado(tipoServicioRaw) : 'PARTICULAR',
            tarjeta_circulacion_nombre: pickQrValue(pairs, ['propietario', 'nombre propietario', 'nombre del propietario', 'nombre', 'razon social', 'razón social', 'titular']),
            tipo_general: tipoRaw,
            tipo: tipoRaw
        };
    }

    function autocompletarVehiculoDesdeQr() {
        const parsed = parseTarjetaCirculacion(valueOf(vehiculoQrRaw));
        const hasValue = Object.keys(parsed).some(function (key) {
            return String(parsed[key] || '').trim() !== '';
        });

        if (!hasValue) {
            toastRelacionado('error', 'Tarjeta sin datos', 'No encontré datos útiles para autocompletar.');
            return;
        }

        setValue(vehiculoMarca, parsed.marca);
        setValue(vehiculoModelo, parsed.modelo);
        setValue(vehiculoTipoGeneral, parsed.tipo_general);
        setValue(vehiculoTipo, parsed.tipo);
        setValue(vehiculoLinea, parsed.linea);
        setValue(vehiculoColor, parsed.color);
        setValue(vehiculoPlacas, parsed.placas);
        setValue(vehiculoEstadoPlacas, parsed.estado_placas);
        setValue(vehiculoSerie, parsed.serie);
        setValue(vehiculoTipoServicio, parsed.tipo_servicio);
        setValue(vehiculoTarjetaNombre, parsed.tarjeta_circulacion_nombre);
        toastRelacionado('success', 'Autocompletado', 'Revisa los campos antes de agregar.');
    }

    function validarVehiculoNuevo(vehiculo) {
        if (!vehiculo.marca) return 'Captura la marca.';
        if (!vehiculo.tipo_general) return 'Captura el tipo general.';
        if (!vehiculo.tipo) return 'Captura la carrocería.';
        if (!vehiculo.linea) return 'Captura la línea.';
        if (!vehiculo.color) return 'Captura el color.';
        if (vehiculo.placas && !/^[A-Z0-9]{5,15}$/.test(vehiculo.placas)) return 'Placas inválidas.';
        if (vehiculo.placas && !vehiculo.estado_placas) return 'Selecciona el estado de las placas.';
        if (!vehiculo.serie || !/^[A-Z0-9]{6,17}$/.test(vehiculo.serie)) return 'Captura un NIV/serie válido.';
        if (!vehiculo.tipo_servicio) return 'Captura el tipo de servicio.';
        if (vehiculo.capacidad_personas === '' || Number(vehiculo.capacidad_personas) < 0) return 'Captura una capacidad válida.';
        if (vehiculo.monto_danos === '' || Number(vehiculo.monto_danos) < 0) return 'Captura el monto de daños.';
        if (!vehiculo.partes_danadas) return 'Captura las partes dañadas.';
        return null;
    }

    function buildVehiculoNuevo() {
        return {
            marca: normalizarRelacionado(valueOf(vehiculoMarca)),
            modelo: normalizarRelacionado(valueOf(vehiculoModelo)),
            tipo_general: normalizarRelacionado(valueOf(vehiculoTipoGeneral)),
            tipo: normalizarRelacionado(valueOf(vehiculoTipo)),
            linea: normalizarRelacionado(valueOf(vehiculoLinea)),
            color: normalizarRelacionado(valueOf(vehiculoColor)),
            placas: scannerToken(valueOf(vehiculoPlacas)),
            estado_placas: scannerToken(valueOf(vehiculoEstadoPlacas)),
            serie: scannerToken(valueOf(vehiculoSerie)),
            capacidad_personas: valueOf(vehiculoCapacidad),
            tipo_servicio: normalizarRelacionado(valueOf(vehiculoTipoServicio)),
            tarjeta_circulacion_nombre: normalizarRelacionado(valueOf(vehiculoTarjetaNombre)),
            grua: normalizarRelacionado(valueOf(vehiculoGrua)),
            corralon: normalizarRelacionado(valueOf(vehiculoCorralon)),
            aseguradora: normalizarRelacionado(valueOf(vehiculoAseguradora)),
            antecedente_vehiculo: checkedOf(vehiculoAntecedente),
            monto_danos: valueOf(vehiculoMontoDanos),
            partes_danadas: normalizarRelacionado(valueOf(vehiculoPartesDanadas))
        };
    }

    function limpiarVehiculoForm() {
        [vehiculoQrRaw, vehiculoObservaciones, vehiculoMarca, vehiculoModelo, vehiculoTipoGeneral, vehiculoTipo, vehiculoLinea, vehiculoColor, vehiculoPlacas, vehiculoEstadoPlacas, vehiculoSerie, vehiculoTarjetaNombre, vehiculoGrua, vehiculoCorralon, vehiculoAseguradora, vehiculoPartesDanadas].forEach(function (input) {
            if (input) input.value = '';
        });
        if (vehiculoCapacidad) vehiculoCapacidad.value = '0';
        if (vehiculoTipoServicio) vehiculoTipoServicio.value = 'PARTICULAR';
        if (vehiculoMontoDanos) vehiculoMontoDanos.value = '0';
        if (vehiculoAntecedente) vehiculoAntecedente.checked = false;
    }

    function vehiculoDuplicado(vehiculo) {
        return vehiculosRelacionados.some(function (item) {
            const actual = item.vehiculo || vehiculosById[item.id] || {};
            const placasA = scannerToken(actual.placas || '');
            const serieA = scannerToken(actual.serie || '');
            return (vehiculo.placas && placasA === vehiculo.placas) || (vehiculo.serie && serieA === vehiculo.serie);
        });
    }
    function vehiculoTieneAntecedente(vehiculo) {
        const value = vehiculo ? vehiculo.antecedente_vehiculo : false;

        return value === true || value === 1 || value === '1' || value === 'true';
    }

    function syncHiddenRelacionados() {
        if (!relacionadosInputs) {
            return;
        }

        relacionadosInputs.innerHTML = '';

        let nuevosIndex = 0;
        vehiculosRelacionados.forEach(function (item) {
            if (item.id) {
                relacionadosInputs.appendChild(crearHiddenRelacionado('vehiculos[]', item.id));
                relacionadosInputs.appendChild(crearHiddenRelacionado('vehiculos_meta[' + item.id + '][rol]', item.rol || 'IMPACTADO'));
                relacionadosInputs.appendChild(crearHiddenRelacionado('vehiculos_meta[' + item.id + '][observaciones]', item.observaciones || ''));
                return;
            }

            const vehiculo = item.vehiculo || {};
            const prefix = 'vehiculos_nuevos[' + nuevosIndex + ']';
            nuevosIndex++;

            relacionadosInputs.appendChild(crearHiddenRelacionado(prefix + '[rol]', item.rol || 'IMPACTADO'));
            relacionadosInputs.appendChild(crearHiddenRelacionado(prefix + '[observaciones]', item.observaciones || ''));
            ['marca', 'modelo', 'tipo_general', 'tipo', 'linea', 'color', 'placas', 'estado_placas', 'serie', 'capacidad_personas', 'tipo_servicio', 'tarjeta_circulacion_nombre', 'grua', 'corralon', 'aseguradora', 'monto_danos', 'partes_danadas'].forEach(function (field) {
                relacionadosInputs.appendChild(crearHiddenRelacionado(prefix + '[' + field + ']', vehiculo[field] || ''));
            });
            relacionadosInputs.appendChild(crearHiddenRelacionado(prefix + '[antecedente_vehiculo]', vehiculo.antecedente_vehiculo ? '1' : '0'));
        });

        personasRelacionadas.forEach(function (persona, index) {
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][nombre]', persona.nombre || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][tipo_participacion]', persona.tipo_participacion || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][curp]', persona.curp || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][telefono]', persona.telefono || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][domicilio]', persona.domicilio || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][sexo]', persona.sexo || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][ocupacion]', persona.ocupacion || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][edad]', persona.edad || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][tipo_licencia]', persona.tipo_licencia || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][estado_licencia]', persona.estado_licencia || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][vigencia_licencia]', persona.vigencia_licencia || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][numero_licencia]', persona.numero_licencia || ''));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][permanente]', persona.permanente ? '1' : '0'));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][cinturon]', persona.cinturon ? '1' : '0'));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][antecedentes]', persona.antecedentes ? '1' : '0'));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][certificado_lesiones]', persona.certificado_lesiones ? '1' : '0'));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][certificado_alcoholemia]', persona.certificado_alcoholemia ? '1' : '0'));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][aliento_etilico]', persona.aliento_etilico ? '1' : '0'));
            relacionadosInputs.appendChild(crearHiddenRelacionado('personas[' + index + '][observaciones]', persona.observaciones || ''));
        });
    }

    function setNumberIfAvailable(id, value) {
        const input = document.getElementById(id);

        if (!input || input.disabled) {
            return;
        }

        input.value = String(value);
    }

    function syncRelacionadosCounters() {
        if (!relacionadosInputs) {
            return;
        }

        const vehiculosImpactados = vehiculosRelacionados.filter(function (item) {
            return normalizarRelacionado(item.rol) === 'IMPACTADO';
        }).length;

        const vehiculosInspeccionados = vehiculosRelacionados.filter(function (item) {
            return normalizarRelacionado(item.rol) === 'INSPECCIONADO';
        }).length;

        const vehiculosRecuperados = vehiculosRelacionados.filter(function (item) {
            return normalizarRelacionado(item.rol) === 'RECUPERADO';
        }).length;

        const antecedentesVehiculos = vehiculosRelacionados.filter(function (item) {
            return vehiculoTieneAntecedente(item.vehiculo || vehiculosById[item.id]);
        }).length;

        const personasImpactadas = personasRelacionadas.filter(function (persona) {
            return ['IMPACTADA', 'CONDUCTOR', 'ACOMPANANTE', 'PEATON'].includes(normalizarRelacionado(persona.tipo_participacion));
        }).length;

        const personasInspeccionadas = personasRelacionadas.filter(function (persona) {
            return normalizarRelacionado(persona.tipo_participacion) === 'INSPECCIONADA';
        }).length;

        setNumberIfAvailable('vehiculos_impactados', vehiculosImpactados);
        setNumberIfAvailable('vehiculos_inspeccionados', vehiculosInspeccionados);
        setNumberIfAvailable('vehiculos_recuperados', vehiculosRecuperados);
        setNumberIfAvailable('antecedentes_vehiculos', antecedentesVehiculos);
        setNumberIfAvailable('personas_impactadas', personasImpactadas);
        setNumberIfAvailable('personas_inspeccionadas', personasInspeccionadas);
    }

    function renderVehiculosRelacionados() {
        if (!vehiculosList) {
            return;
        }

        vehiculosList.innerHTML = '';

        vehiculosRelacionados.forEach(function (item, index) {
            const vehiculo = item.vehiculo || vehiculosById[item.id] || {};
            const placas = vehiculo.placas || 'SIN PLACAS';
            const marcaLinea = [vehiculo.marca, vehiculo.linea].filter(Boolean).join(' ') || (item.id ? ('VEHICULO #' + item.id) : 'VEHICULO NUEVO');
            const modelo = vehiculo.modelo ? 'Modelo ' + vehiculo.modelo : 'Modelo no especificado';
            const tieneAntecedente = vehiculoTieneAntecedente(vehiculo);

            const card = document.createElement('div');
            card.className = 'vehiculo-card';
            card.innerHTML = `
                <div class="vehiculo-card-head">
                    <div class="min-w-0">
                        <div class="vehiculo-title text-truncate">${escapeHtmlRelacionados(marcaLinea)}</div>
                        <div class="vehiculo-subtitle">${escapeHtmlRelacionados(item.rol || 'IMPACTADO')} · ${escapeHtmlRelacionados(modelo)}</div>
                    </div>
                    <span class="vehiculo-placa"><i class="fa-solid fa-id-card"></i> ${escapeHtmlRelacionados(placas)}</span>
                </div>
                <div class="vehiculo-card-body">
                    <div class="vehiculo-chip-row">
                        <span class="vehiculo-chip"><i class="fa-solid fa-car-rear"></i> ${escapeHtmlRelacionados(vehiculo.tipo || 'Tipo N/D')}</span>
                        <span class="vehiculo-chip"><i class="fa-solid fa-palette"></i> ${escapeHtmlRelacionados(vehiculo.color || 'Color N/D')}</span>
                        <span class="vehiculo-chip"><i class="fa-solid fa-barcode"></i> ${escapeHtmlRelacionados(vehiculo.serie || 'NIV N/D')}</span>
                    </div>
                    ${vehiculo.conductor_nombre ? `<div class="vehiculo-nota"><span>Conductor</span><p>${escapeHtmlRelacionados(vehiculo.conductor_nombre)}</p></div>` : ''}
                    ${item.observaciones ? `<div class="vehiculo-nota"><span>Observaciones</span><p>${escapeHtmlRelacionados(item.observaciones)}</p></div>` : ''}
                    <div class="vehiculo-card-actions">
                        <span class="badge ${tieneAntecedente ? 'badge-danger' : 'badge-success'}">Antecedente: ${tieneAntecedente ? 'SI' : 'NO'}</span>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-vehiculo-relacionado="${index}">
                            <i class="fa-solid fa-trash"></i> Quitar
                        </button>
                    </div>
                </div>
            `;

            vehiculosList.appendChild(card);
        });

        if (vehiculosEmpty) {
            vehiculosEmpty.style.display = vehiculosRelacionados.length ? 'none' : '';
        }

        if (vehiculosTotal) {
            vehiculosTotal.textContent = 'Total: ' + vehiculosRelacionados.length;
        }
    }

    function renderPersonasRelacionadas() {
        if (!personasList) {
            return;
        }

        personasList.innerHTML = '';

        personasRelacionadas.forEach(function (persona, index) {
            const card = document.createElement('div');
            card.className = 'vehiculo-card';
            card.innerHTML = `
                <div class="vehiculo-card-head">
                    <div class="min-w-0">
                        <div class="vehiculo-title text-truncate">${escapeHtmlRelacionados(persona.nombre)}</div>
                        <div class="vehiculo-subtitle">${escapeHtmlRelacionados(persona.tipo_participacion || 'SIN TIPO')}</div>
                    </div>
                    <span class="vehiculo-placa"><i class="fa-solid fa-user"></i></span>
                </div>
                <div class="vehiculo-card-body">
                    <div class="vehiculo-mini-grid">
                        <div><span>CURP</span><strong>${escapeHtmlRelacionados(persona.curp || 'N/D')}</strong></div>
                        <div><span>Teléfono</span><strong>${escapeHtmlRelacionados(persona.telefono || 'N/D')}</strong></div>
                    </div>
                    ${persona.observaciones ? `<div class="vehiculo-nota"><span>Observaciones</span><p>${escapeHtmlRelacionados(persona.observaciones)}</p></div>` : ''}
                    <div class="vehiculo-card-actions">
                        <span class="badge badge-info">${escapeHtmlRelacionados(persona.tipo_participacion || 'SIN TIPO')}</span>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-persona-relacionada="${index}">
                            <i class="fa-solid fa-trash"></i> Quitar
                        </button>
                    </div>
                </div>
            `;

            personasList.appendChild(card);
        });

        if (personasEmpty) {
            personasEmpty.style.display = personasRelacionadas.length ? 'none' : '';
        }

        if (personasTotal) {
            personasTotal.textContent = 'Total: ' + personasRelacionadas.length;
        }
    }

    function renderRelacionados() {
        syncHiddenRelacionados();
        renderVehiculosRelacionados();
        renderPersonasRelacionadas();
        syncRelacionadosCounters();
    }

    if (btnAutocompletarVehiculoQr) {
        btnAutocompletarVehiculoQr.addEventListener('click', autocompletarVehiculoDesdeQr);
    }

    if (btnAgregarVehiculoRelacionado) {
        btnAgregarVehiculoRelacionado.addEventListener('click', function () {
            const vehiculo = buildVehiculoNuevo();
            const error = validarVehiculoNuevo(vehiculo);

            if (error) {
                toastRelacionado('error', 'Vehículo incompleto', error);
                return;
            }

            if (vehiculoDuplicado(vehiculo)) {
                toastRelacionado('error', 'Vehículo duplicado', 'Ya agregaste un vehículo con esas placas o ese NIV/serie.');
                return;
            }

            vehiculosRelacionados.push({
                id: null,
                rol: vehiculoRol ? vehiculoRol.value : 'IMPACTADO',
                observaciones: vehiculoObservaciones ? vehiculoObservaciones.value.trim() : '',
                vehiculo: vehiculo
            });

            limpiarVehiculoForm();
            renderRelacionados();
            toastRelacionado('success', 'Vehículo agregado', 'Listo.');
        });
    }

    if (btnAgregarPersonaRelacionada) {
        btnAgregarPersonaRelacionada.addEventListener('click', function () {
            const nombre = personaNombre ? personaNombre.value.trim() : '';
            const telefono = personaTelefono ? personaTelefono.value.trim() : '';

            if (!nombre) {
                toastRelacionado('error', 'Nombre requerido', 'Captura el nombre de la persona.');
                return;
            }

            if (telefono && !/^\d{10}$/.test(telefono)) {
                toastRelacionado('error', 'Teléfono inválido', 'El teléfono debe tener exactamente 10 dígitos.');
                return;
            }

            personasRelacionadas.push({
                nombre: nombre,
                tipo_participacion: personaTipo ? personaTipo.value : 'IMPACTADA',
                curp: personaCurp ? personaCurp.value.trim() : '',
                telefono: telefono,
                domicilio: valueOf(personaDomicilio),
                sexo: valueOf(personaSexo),
                ocupacion: valueOf(personaOcupacion),
                edad: valueOf(personaEdad),
                tipo_licencia: valueOf(personaTipoLicencia),
                estado_licencia: valueOf(personaEstadoLicencia),
                vigencia_licencia: valueOf(personaVigenciaLicencia),
                numero_licencia: valueOf(personaNumeroLicencia),
                permanente: checkedOf(personaPermanente),
                cinturon: checkedOf(personaCinturon),
                antecedentes: checkedOf(personaAntecedentes),
                certificado_lesiones: checkedOf(personaCertificadoLesiones),
                certificado_alcoholemia: checkedOf(personaCertificadoAlcoholemia),
                aliento_etilico: checkedOf(personaAlientoEtilico),
                observaciones: personaObservaciones ? personaObservaciones.value.trim() : ''
            });

            [personaNombre, personaCurp, personaTelefono, personaDomicilio, personaOcupacion, personaEdad, personaTipoLicencia, personaEstadoLicencia, personaVigenciaLicencia, personaNumeroLicencia, personaObservaciones].forEach(function (input) {
                if (input) input.value = '';
            });
            [personaPermanente, personaCinturon, personaAntecedentes, personaCertificadoLesiones, personaCertificadoAlcoholemia, personaAlientoEtilico].forEach(function (input) {
                if (input) input.checked = false;
            });

            renderRelacionados();
            toastRelacionado('success', 'Persona agregada', 'Listo.');
        });
    }

    if (vehiculosList) {
        vehiculosList.addEventListener('click', function (event) {
            const btn = event.target.closest('[data-remove-vehiculo-relacionado]');

            if (!btn) {
                return;
            }

            vehiculosRelacionados.splice(Number(btn.dataset.removeVehiculoRelacionado), 1);
            renderRelacionados();
        });
    }

    if (personasList) {
        personasList.addEventListener('click', function (event) {
            const btn = event.target.closest('[data-remove-persona-relacionada]');

            if (!btn) {
                return;
            }

            personasRelacionadas.splice(Number(btn.dataset.removePersonaRelacionada), 1);
            renderRelacionados();
        });
    }

    document.querySelectorAll('.js-uppercase-relacionados').forEach(function (input) {
        input.addEventListener('input', function () {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toLocaleUpperCase('es-MX');

            if (typeof start === 'number' && typeof end === 'number') {
                this.setSelectionRange(start, end);
            }
        });
    });

    renderRelacionados();

    if (selectDispositivo) {
        selectDispositivo.addEventListener('change', actualizarFormulario);
        actualizarFormulario();
    }

    if (btnGeo) {
        btnGeo.addEventListener('click', capturarUbicacion);
    } else {
        console.error('No se encontró #btn_geo');
    }

    if (btnGeoClear) {
        btnGeoClear.addEventListener('click', limpiarUbicacion);
    }

    setGeoUI();

    if (fotosInput && previewContainer) {
        fotosInput.addEventListener('change', function () {
            previewContainer.innerHTML = '';

            const archivos = Array.from(this.files || []);

            archivos.forEach(function (archivo) {
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-3';

                const card = document.createElement('div');
                card.className = 'card bg-dark h-100';

                const body = document.createElement('div');
                body.className = 'card-body';

                const nombreArchivo = document.createElement('h6');
                nombreArchivo.textContent = archivo.name;
                nombreArchivo.className = 'mb-2';

                const caption = document.createElement('input');
                caption.type = 'text';
                caption.name = 'fotos_caption[]';
                caption.className = 'form-control mb-2';
                caption.placeholder = 'Caption o título';

                const observaciones = document.createElement('textarea');
                observaciones.name = 'fotos_observaciones[]';
                observaciones.className = 'form-control';
                observaciones.rows = 2;
                observaciones.placeholder = 'Observaciones de la foto';

                body.appendChild(nombreArchivo);
                body.appendChild(caption);
                body.appendChild(observaciones);

                if (archivo.type && archivo.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.className = 'img-fluid rounded mb-2';
                    img.style.maxHeight = '180px';
                    img.style.objectFit = 'cover';
                    img.src = URL.createObjectURL(archivo);
                    body.insertBefore(img, nombreArchivo);
                }

                card.appendChild(body);
                col.appendChild(card);
                previewContainer.appendChild(col);
            });
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            const lat = latInput ? String(latInput.value || '').trim() : '';
            const lng = lngInput ? String(lngInput.value || '').trim() : '';

            if (latInput && lngInput && (!lat || !lng)) {
                e.preventDefault();
                setGeoUI();
                toastError('Captura la ubicación antes de registrar.');
                return;
            }

            if (coordenadasTextoInput && lat && lng) {
                coordenadasTextoInput.value = lat + ',' + lng;
            }

            if (btnSubmit) {
                btnSubmit.disabled = true;
            }
        });
    }
});
