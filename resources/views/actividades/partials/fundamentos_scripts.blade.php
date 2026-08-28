<script>
document.addEventListener('DOMContentLoaded', function () {
    const checks = Array.from(document.querySelectorAll('.js-fundamento-actividad'));
    const buscar = document.getElementById('buscarFundamentoActividad');
    const lista = document.getElementById('fundamentosActividadList');
    const inputs = document.getElementById('fundamentosActividadInputs');
    const empty = document.getElementById('fundamentosActividadEmpty');
    const total = document.getElementById('fundamentosActividadTotal');
    const sinResultados = document.getElementById('fundamentosSinResultados');
    function normalizar(value) { return (value || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase(); }
    function render() {
        lista.innerHTML = ''; inputs.innerHTML = '';
        const seleccionados = checks.filter(check => check.checked);
        seleccionados.forEach(function (check) {
            const opcion = check.closest('.fundamento-opcion');
            const input = document.createElement('input'); input.type = 'hidden'; input.name = 'fundamento_ids[]'; input.value = check.value; inputs.appendChild(input);
            const chip = document.createElement('div'); chip.className = 'fundamento-seleccionado';
            chip.innerHTML = `<i class="fa-solid fa-scale-balanced"></i><span>${opcion.querySelector('strong').textContent} — ${opcion.querySelector('.fundamento-opcion__body > span').textContent}</span><button type="button" class="btn btn-sm" data-quitar-fundamento="${check.value}" aria-label="Quitar">&times;</button>`;
            lista.appendChild(chip);
        });
        empty.style.display = seleccionados.length ? 'none' : ''; total.textContent = `Total: ${seleccionados.length}`;
    }
    checks.forEach(function (check) { check.addEventListener('change', function () { if (checks.filter(c => c.checked).length > 20) { check.checked = false; alert('Puede seleccionar hasta 20 fundamentos.'); } render(); }); });
    buscar?.addEventListener('input', function () {
        const termino = normalizar(buscar.value); let visibles = 0;
        document.querySelectorAll('.fundamento-opcion').forEach(function (opcion) { const mostrar = normalizar(opcion.dataset.search).includes(termino); opcion.style.display = mostrar ? '' : 'none'; if (mostrar) visibles++; });
        sinResultados.style.display = visibles ? 'none' : '';
    });
    lista?.addEventListener('click', function (event) { const btn = event.target.closest('[data-quitar-fundamento]'); if (!btn) return; const check = checks.find(c => c.value === btn.dataset.quitarFundamento); if (check) check.checked = false; render(); });
    render();
});
</script>
