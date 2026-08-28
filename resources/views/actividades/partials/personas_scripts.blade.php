<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formPersonaActividadTemporal');
    const lista = document.getElementById('personasActividadList');
    const inputs = document.getElementById('personasActividadInputs');
    const empty = document.getElementById('personasActividadEmpty');
    const total = document.getElementById('personasActividadTotal');
    const tipo = document.getElementById('persona_tipo_participacion');
    const vehiculo = document.getElementById('persona_vehiculo_indice');
    const ayuda = document.getElementById('persona_vehiculo_ayuda');
    const licencia = document.getElementById('personaCamposLicencia');
    let personas = @json(array_values(old('personas', [])));
    const campos = ['tipo_participacion','vehiculo_indice','nombre','telefono','domicilio','sexo','nacionalidad','ocupacion','edad','tipo_licencia','estado_licencia','numero_licencia','vigencia_licencia','permanente','antecedentes','observaciones'];

    function esc(value) {
        return (value || '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }
    function vehiculos() { return typeof window.actividadVehiculosTemporales === 'function' ? window.actividadVehiculosTemporales() : []; }
    function etiquetaVehiculo(index) {
        const item = vehiculos()[Number(index)];
        return item ? ([item.marca, item.linea, item.placas].filter(Boolean).join(' · ') || `Vehículo ${Number(index) + 1}`) : '';
    }
    function hidden(name, value) {
        const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value ?? ''; return input;
    }
    function actualizarVehiculos() {
        if (!vehiculo) return;
        const actual = vehiculo.value;
        vehiculo.innerHTML = '<option value="">Sin vehículo</option>';
        vehiculos().forEach(function (item, index) {
            const option = document.createElement('option'); option.value = index; option.textContent = etiquetaVehiculo(index); vehiculo.appendChild(option);
        });
        vehiculo.value = actual;
        actualizarTipo();
    }
    function actualizarTipo() {
        if (!tipo || !vehiculo) return;
        const requiere = tipo.value === 'CONDUCTOR' || tipo.value === 'PASAJERO';
        vehiculo.required = requiere;
        licencia.style.display = tipo.value === 'CONDUCTOR' ? '' : 'none';
        ayuda.textContent = requiere ? 'Obligatorio para esta participación.' : 'Opcional; un peatón puede quedar sin vehículo.';
    }
    function render() {
        lista.innerHTML = ''; inputs.innerHTML = '';
        personas.forEach(function (persona, index) {
            campos.forEach(field => inputs.appendChild(hidden(`personas[${index}][${field}]`, persona[field] || '')));
            const relacion = persona.vehiculo_indice !== '' && persona.vehiculo_indice !== null && persona.vehiculo_indice !== undefined ? etiquetaVehiculo(persona.vehiculo_indice) : 'SIN VEHÍCULO';
            const card = document.createElement('div'); card.className = 'vehiculo-card';
            card.innerHTML = `<div class="vehiculo-card-head"><div class="min-w-0"><div class="vehiculo-title text-truncate">${esc(persona.nombre)}</div><div class="vehiculo-subtitle">${esc(persona.tipo_participacion)}</div></div><span class="vehiculo-placa"><i class="fa-solid fa-link"></i> ${esc(relacion)}</span></div><div class="vehiculo-card-body"><div class="vehiculo-chip-row">${persona.edad ? `<span class="vehiculo-chip">${esc(persona.edad)} años</span>` : ''}${persona.sexo ? `<span class="vehiculo-chip">${esc(persona.sexo)}</span>` : ''}${persona.numero_licencia ? `<span class="vehiculo-chip"><i class="fa-solid fa-id-card"></i> ${esc(persona.numero_licencia)}</span>` : ''}</div><div class="vehiculo-card-actions"><span class="badge badge-${persona.tipo_participacion === 'CONDUCTOR' ? 'warning' : 'info'}">${esc(persona.tipo_participacion)}</span><button type="button" class="btn btn-outline-danger btn-sm" data-remove-persona="${index}"><i class="fa-solid fa-trash"></i> Quitar</button></div></div>`;
            lista.appendChild(card);
        });
        empty.style.display = personas.length ? 'none' : '';
        total.textContent = `Total: ${personas.length}`;
    }
    tipo?.addEventListener('change', actualizarTipo);
    document.addEventListener('actividad:vehiculos-actualizados', function () { actualizarVehiculos(); render(); });
    document.addEventListener('actividad:vehiculo-eliminado', function (event) {
        const eliminado = Number(event.detail.index);
        personas = personas.filter(p => p.vehiculo_indice === '' || p.vehiculo_indice === null || p.vehiculo_indice === undefined || Number(p.vehiculo_indice) !== eliminado)
            .map(function (p) { if (p.vehiculo_indice !== '' && p.vehiculo_indice !== null && Number(p.vehiculo_indice) > eliminado) p.vehiculo_indice = String(Number(p.vehiculo_indice) - 1); return p; });
        render();
    });
    form?.addEventListener('submit', function (event) {
        event.preventDefault(); actualizarTipo();
        const fd = new FormData(form); const data = {};
        campos.forEach(field => data[field] = (fd.get(field) || '').toString().trim());
        data.permanente = form.querySelector('[name="permanente"]')?.checked ? '1' : '0';
        data.antecedentes = form.querySelector('[name="antecedentes"]')?.checked ? '1' : '0';
        const repetido = data.tipo_participacion === 'CONDUCTOR' && personas.some(p => p.tipo_participacion === 'CONDUCTOR' && String(p.vehiculo_indice) === String(data.vehiculo_indice));
        vehiculo.setCustomValidity(repetido ? 'Este vehículo ya tiene un conductor.' : '');
        if (!form.checkValidity()) { form.reportValidity(); return; }
        personas.push(data); render(); form.reset(); actualizarVehiculos(); actualizarTipo();
        if (window.jQuery) $('#modalAgregarPersonaActividad').modal('hide');
    });
    lista?.addEventListener('click', function (event) { const btn = event.target.closest('[data-remove-persona]'); if (!btn) return; personas.splice(Number(btn.dataset.removePersona), 1); render(); });
    actualizarVehiculos(); actualizarTipo(); render();
});
</script>
