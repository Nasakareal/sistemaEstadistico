            const actividadForm = document.querySelector('form[action*="actividades"]');
            const detenidasInput = document.getElementById('personas_detenidas');

            function campoActividadLleno(field) {
                if (!field || field.disabled || field.type === 'hidden') {
                    return false;
                }

                if (field.type === 'file') {
                    return field.files && field.files.length > 0;
                }

                const value = String(field.value || '').trim();

                if (value === '') {
                    return false;
                }

                if (field.type === 'number') {
                    return Number(value) !== 0;
                }

                return true;
            }

            function actualizarCampoActividad(field) {
                const group = field ? field.closest('.form-group') : null;

                if (!group) {
                    return;
                }

                const lleno = campoActividadLleno(field);
                group.classList.toggle('sv-field-filled', lleno);

                if (field === detenidasInput) {
                    const detenidas = Number(field.value || 0);
                    group.classList.toggle('sv-field-review', detenidas > 0);
                }
            }

            document
                .querySelectorAll('form[action*="actividades"] input.form-control, form[action*="actividades"] select.form-control, form[action*="actividades"] textarea.form-control')
                .forEach(function (field) {
                    actualizarCampoActividad(field);
                    field.addEventListener('input', function () { actualizarCampoActividad(field); });
                    field.addEventListener('change', function () { actualizarCampoActividad(field); });
                });

            if (detenidasInput) {
                detenidasInput.setAttribute('max', '3');
            }

            if (actividadForm && detenidasInput) {
                actividadForm.addEventListener('submit', function (event) {
                    const detenidas = Number(detenidasInput.value || 0);

                    if (detenidas > 3) {
                        event.preventDefault();

                        Swal.fire({
                            icon: 'error',
                            title: 'Personas detenidas',
                            text: 'No se pueden capturar mas de 3 personas detenidas en una actividad.',
                            confirmButtonText: 'Corregir'
                        }).then(function () {
                            detenidasInput.focus();
                            detenidasInput.select();
                        });

                        return;
                    }

                    if (detenidas === 1 && actividadForm.dataset.detencionesConfirmadas !== '1') {
                        event.preventDefault();

                        Swal.fire({
                            icon: 'question',
                            title: 'Confirmar detenido',
                            text: 'Capturaste 1 persona detenida. Confirma que ese dato es correcto.',
                            showCancelButton: true,
                            confirmButtonText: 'Si, es correcto',
                            cancelButtonText: 'Revisar'
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                actividadForm.dataset.detencionesConfirmadas = '1';

                                if (actividadForm.requestSubmit) {
                                    actividadForm.requestSubmit();
                                } else {
                                    actividadForm.submit();
                                }
                            } else {
                                detenidasInput.focus();
                                detenidasInput.select();
                            }
                        });
                    }
                });
            }
