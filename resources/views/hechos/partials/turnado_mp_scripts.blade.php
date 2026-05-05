            const formHechoMp = document.getElementById('form_hecho');
            const vehiculosMpInput = document.getElementById('vehiculos_mp');
            const personasMpInput = document.getElementById('personas_mp');
            let turnoMpPreguntaAbierta = false;

            function valorEnteroMp(input) {
                const value = Number(input && input.value !== '' ? input.value : 0);
                return Number.isFinite(value) ? Math.max(0, parseInt(value, 10) || 0) : 0;
            }

            function esTurnadoMp() {
                return situacionSelect && situacionSelect.value === 'TURNADO';
            }

            function actualizarTurnadoMp() {
                const isTurnado = esTurnadoMp();

                [vehiculosMpInput, personasMpInput].forEach(function (input) {
                    if (!input) {
                        return;
                    }

                    input.required = isTurnado;

                    const group = input.closest('.form-group');
                    if (group) {
                        group.classList.toggle('sv-mp-required', isTurnado && valorEnteroMp(input) === 0);
                    }
                });
            }

            function preguntarTurnadoMp() {
                if (!esTurnadoMp() || !vehiculosMpInput || !personasMpInput || turnoMpPreguntaAbierta) {
                    return;
                }

                if (valorEnteroMp(vehiculosMpInput) + valorEnteroMp(personasMpInput) > 0) {
                    return;
                }

                if (!window.Swal) {
                    vehiculosMpInput.focus();
                    return;
                }

                turnoMpPreguntaAbierta = true;

                Swal.fire({
                    icon: 'question',
                    title: 'Datos de MP',
                    html: `
                        <div style="text-align:left">
                            <label for="swal_vehiculos_mp">Vehiculos presentados al MP</label>
                            <input id="swal_vehiculos_mp" type="number" min="0" step="1" class="swal2-input" value="${valorEnteroMp(vehiculosMpInput)}">
                            <label for="swal_personas_mp">Personas presentadas al MP</label>
                            <input id="swal_personas_mp" type="number" min="0" step="1" class="swal2-input" value="${valorEnteroMp(personasMpInput)}">
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Aplicar',
                    cancelButtonText: 'Capturar despues',
                    focusConfirm: false,
                    preConfirm: function () {
                        const vehiculos = valorEnteroMp(document.getElementById('swal_vehiculos_mp'));
                        const personas = valorEnteroMp(document.getElementById('swal_personas_mp'));

                        if (vehiculos + personas <= 0) {
                            Swal.showValidationMessage('Captura al menos una persona o un vehiculo presentado al MP.');
                            return false;
                        }

                        return { vehiculos: vehiculos, personas: personas };
                    }
                }).then(function (result) {
                    turnoMpPreguntaAbierta = false;

                    if (result.isConfirmed && result.value) {
                        vehiculosMpInput.value = result.value.vehiculos;
                        personasMpInput.value = result.value.personas;
                        actualizarTurnadoMp();
                    }
                });
            }

            window.actualizarGuardiaTurnadoMp = actualizarTurnadoMp;
            window.preguntarGuardiaTurnadoMp = preguntarTurnadoMp;

            actualizarTurnadoMp();

            if (situacionSelect) {
                situacionSelect.addEventListener('change', function () {
                    actualizarTurnadoMp();
                    preguntarTurnadoMp();
                });
            }

            [vehiculosMpInput, personasMpInput].forEach(function (input) {
                if (!input) {
                    return;
                }

                input.addEventListener('input', actualizarTurnadoMp);
                input.addEventListener('change', actualizarTurnadoMp);
            });

            if (formHechoMp) {
                formHechoMp.addEventListener('submit', function (event) {
                    if (!esTurnadoMp()) {
                        return;
                    }

                    if (valorEnteroMp(vehiculosMpInput) + valorEnteroMp(personasMpInput) > 0) {
                        return;
                    }

                    event.preventDefault();
                    actualizarTurnadoMp();

                    const message = 'Si la situacion es TURNADO, captura cuantos vehiculos o personas se presentaron al MP.';

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Faltan datos de MP',
                            text: message,
                            confirmButtonText: 'Capturar'
                        }).then(function () {
                            preguntarTurnadoMp();
                        });
                    } else {
                        alert(message);
                        if (vehiculosMpInput) {
                            vehiculosMpInput.focus();
                        }
                    }
                });
            }
