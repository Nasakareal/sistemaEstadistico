@php
    $programasFomentoPayload = ($programasFomento ?? collect())->map(function ($programa) {
        return [
            'id' => (int) $programa->id,
            'actividad_subcategoria_id' => (int) $programa->actividad_subcategoria_id,
            'nombre' => $programa->nombre,
        ];
    })->values();
@endphp

const fomentoPanel = document.getElementById('fomento_cultura_vial_panel');
const fomentoTotalInput = document.getElementById('fomento_total_poblacion_atendida');
const fomentoNumericInputs = Array.from(document.querySelectorAll('[data-fomento-total-source="1"]'));
const fomentoForzadoPorUnidad = @json($mostrarFomentoCulturaVial ?? false);
const personasAlcanzadasInput = document.getElementById('personas_alcanzadas');
const fomentoProgramaSelect = document.getElementById('fomento_programa_id');
const fomentoProgramaHelp = document.getElementById('fomento_programa_help');
const fomentoProgramas = @json($programasFomentoPayload);

function categoriaSeleccionadaEsFomento() {
    if (fomentoForzadoPorUnidad) return true;
    if (!categoriaSelect) return false;

    const selected = categoriaSelect.options[categoriaSelect.selectedIndex];
    return selected && selected.dataset && selected.dataset.fomento === '1';
}

function actualizarPanelFomento() {
    if (!fomentoPanel) return;

    fomentoPanel.style.display = categoriaSeleccionadaEsFomento() ? 'block' : 'none';
    actualizarProgramasFomento();
}

function actualizarProgramasFomento() {
    if (!fomentoProgramaSelect) return;

    const subcategoriaId = subcatSelect ? Number.parseInt(subcatSelect.value || '0', 10) : 0;
    const selected = String(fomentoProgramaSelect.dataset.selected || '');
    const opciones = fomentoProgramas.filter(function (programa) {
        return Number(programa.actividad_subcategoria_id) === subcategoriaId;
    });

    fomentoProgramaSelect.innerHTML = '';

    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = opciones.length > 0
        ? 'Seleccione...'
        : 'Sin programas para esta subcategoría';
    fomentoProgramaSelect.appendChild(emptyOption);

    opciones.forEach(function (programa) {
        const option = document.createElement('option');
        option.value = String(programa.id);
        option.textContent = programa.nombre;
        fomentoProgramaSelect.appendChild(option);
    });

    fomentoProgramaSelect.disabled = opciones.length === 0 || !categoriaSeleccionadaEsFomento();

    if (selected !== '' && opciones.some(function (programa) { return String(programa.id) === selected; })) {
        fomentoProgramaSelect.value = selected;
    }

    if (fomentoProgramaHelp) {
        fomentoProgramaHelp.textContent = opciones.length > 0
            ? `${opciones.length} opción(es) disponibles para la subcategoría seleccionada.`
            : '';
    }
}

function actualizarTotalFomento() {
    if (!fomentoTotalInput) return;

    const total = fomentoNumericInputs.reduce(function (sum, input) {
        const value = Number.parseInt(input.value || '0', 10);
        return sum + (Number.isFinite(value) && value > 0 ? value : 0);
    }, 0);

    fomentoTotalInput.value = String(total);

    if (personasAlcanzadasInput && categoriaSeleccionadaEsFomento()) {
        personasAlcanzadasInput.value = String(total);
    }
}

if (categoriaSelect) {
    categoriaSelect.addEventListener('change', actualizarPanelFomento);
}

if (subcatSelect) {
    subcatSelect.addEventListener('change', function () {
        if (fomentoProgramaSelect) {
            fomentoProgramaSelect.dataset.selected = fomentoProgramaSelect.value || fomentoProgramaSelect.dataset.selected || '';
        }
        actualizarProgramasFomento();
    });
}

fomentoNumericInputs.forEach(function (input) {
    input.addEventListener('input', actualizarTotalFomento);
    input.addEventListener('change', actualizarTotalFomento);
});

actualizarPanelFomento();
actualizarTotalFomento();
