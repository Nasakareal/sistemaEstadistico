<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-motivo-liberacion]').forEach(function (select) {
            const targetId = select.getAttribute('data-motivo-liberacion');
            const wrapper = document.getElementById(targetId);
            const input = wrapper ? wrapper.querySelector('[data-motivo-otro-input]') : null;

            const syncMotivoOtro = function () {
                const mostrarOtro = select.value === 'Otro';

                if (wrapper) {
                    wrapper.classList.toggle('d-none', !mostrarOtro);
                }

                if (input) {
                    input.required = mostrarOtro;

                    if (!mostrarOtro) {
                        input.value = '';
                    }
                }
            };

            select.addEventListener('change', syncMotivoOtro);
            syncMotivoOtro();
        });
    });
</script>
