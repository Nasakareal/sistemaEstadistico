document.addEventListener('DOMContentLoaded', function () {
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

    const config = configNode ? JSON.parse(configNode.textContent) : {};
    const allCampos = allCamposNode ? JSON.parse(allCamposNode.textContent) : [];

    const fotosInput = document.getElementById('fotos');
    const previewContainer = document.getElementById('preview_fotos_container');

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
        document.querySelectorAll('.campo-dinamico').forEach(item => {
            item.classList.add('campo-oculto');

            const input = item.querySelector('input, select, textarea');
            if (input) {
                input.disabled = true;
            }
        });
    }

    function limpiarCamposOcultos() {
        document.querySelectorAll('.campo-dinamico').forEach(item => {
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

            input.value = '';
        });
    }

    function mostrarCampos(campos) {
        campos.forEach(nombreCampo => {
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
    }

    if (selectDispositivo) {
        selectDispositivo.addEventListener('change', actualizarFormulario);
        actualizarFormulario();
    }

    if (fotosInput && previewContainer) {
        fotosInput.addEventListener('change', function () {
            previewContainer.innerHTML = '';

            const archivos = Array.from(this.files || []);

            archivos.forEach((archivo) => {
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
});
