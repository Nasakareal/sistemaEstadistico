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
    }

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
