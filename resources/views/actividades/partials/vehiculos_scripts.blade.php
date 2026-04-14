<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modoVehiculos = @json($modo ?? 'edit');
        const tipoGeneralVehiculo = document.getElementById('vehiculo_tipo_general');
        const tipoVehiculo = document.getElementById('vehiculo_tipo');
        const formTemporalVehiculo = document.getElementById('formVehiculoActividadTemporal');
        const listaVehiculos = document.getElementById('vehiculosActividadList');
        const emptyVehiculos = document.getElementById('vehiculosActividadEmpty');
        const inputsVehiculos = document.getElementById('vehiculosActividadInputs');
        const badgeTotalVehiculos = document.getElementById('vehiculosActividadTotal');
        let vehiculosTemporales = @json(array_values($vehiculosIniciales ?? []));

        const carroceriasActividad = {
            automovil: ['Sedán', 'Hatchback', 'Coupé', 'SUV', 'Convertible'],
            camioneta: ['Pick-up', 'Panel', 'Vagoneta', 'Furgoneta', 'Van'],
            camion: ['Caja seca', 'Caja cerrada', 'Caja abierta', 'Plataforma', 'Volteo', 'Refrigerado', 'Cisterna', 'Pipa', 'Grúa', 'Torton', 'Rabón', 'Tracto', 'Redilas'],
            motocicleta: ['Trabajo', 'Cruiser', 'Doble Propósito', 'Scooter', 'Enduro', 'Naked', 'Pista', 'Chopper', 'Cuatrimoto'],
            bicicleta: ['Montaña', 'Ruta', 'BMX', 'Urbana', 'Plegable'],
            remolque: ['Plataforma', 'Caja cerrada', 'Caja seca', 'Cama baja', 'Refrigerado', 'Volteo', 'Góndola', 'Dolly', 'Portacontenedor'],
            maquinaria: ['Retroexcavadora', 'Excavadora', 'Cargador frontal', 'Motoconformadora', 'Bulldozer', 'Rodillo compactador', 'Grúa industrial', 'Montacargas', 'Tractor agrícola', 'Pavimentadora', 'Compactadora'],
            tren: ['Locomotora', 'Vagón', 'Tren de carga', 'Tren de pasajeros', 'Tranvía', 'Metro'],
            semoviente: ['Caballo', 'Burro', 'Vaca', 'Mula', 'Otro animal de tiro']
        };

        const camposVehiculo = [
            'marca',
            'modelo',
            'tipo_general',
            'tipo',
            'linea',
            'color',
            'placas',
            'estado_placas',
            'serie',
            'capacidad_personas',
            'tipo_servicio',
            'tarjeta_circulacion_nombre',
            'grua',
            'corralon',
            'aseguradora',
            'antecedente_vehiculo',
            'monto_danos',
            'partes_danadas'
        ];

        function escapeHtml(value) {
            return (value || '').toString().replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        }

        function normalizarTextoVehiculo(value) {
            return (value || '')
                .toString()
                .trim()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toUpperCase();
        }

        function detectarTipoGeneral(tipoActual) {
            const tipoNorm = normalizarTextoVehiculo(tipoActual);

            for (const [grupo, opciones] of Object.entries(carroceriasActividad)) {
                const existe = opciones.some(function (opcion) {
                    return normalizarTextoVehiculo(opcion) === tipoNorm;
                });

                if (existe) {
                    return grupo;
                }
            }

            return '';
        }

        function cargarCarroceriasActividad() {
            if (!tipoGeneralVehiculo || !tipoVehiculo) {
                return;
            }

            const tipoActual = tipoVehiculo.dataset.oldTipo || '';
            const tipoActualNorm = normalizarTextoVehiculo(tipoActual);
            const seleccion = tipoGeneralVehiculo.value;

            tipoVehiculo.innerHTML = '<option value="">Seleccione...</option>';

            if (carroceriasActividad[seleccion]) {
                carroceriasActividad[seleccion].forEach(function (opcion) {
                    const opt = document.createElement('option');
                    opt.value = opcion;
                    opt.textContent = opcion;

                    if (tipoActualNorm && normalizarTextoVehiculo(opcion) === tipoActualNorm) {
                        opt.selected = true;
                    }

                    tipoVehiculo.appendChild(opt);
                });
            }
        }

        function resetModalVehiculo() {
            if (!formTemporalVehiculo) {
                return;
            }

            formTemporalVehiculo.reset();

            if (tipoVehiculo) {
                tipoVehiculo.dataset.oldTipo = '';
                tipoVehiculo.innerHTML = '<option value="">Seleccione un tipo primero...</option>';
            }
        }

        function obtenerDatosVehiculo(form) {
            const data = {};
            const formData = new FormData(form);

            camposVehiculo.forEach(function (field) {
                data[field] = (formData.get(field) || '').toString().trim();
            });

            data.antecedente_vehiculo = form.querySelector('[name="antecedente_vehiculo"]')?.checked ? '1' : '0';
            data.capacidad_personas = data.capacidad_personas || '0';

            return data;
        }

        function crearHidden(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value === undefined || value === null ? '' : value;
            return input;
        }

        function renderVehiculosTemporales() {
            if (modoVehiculos !== 'create' || !listaVehiculos || !inputsVehiculos) {
                return;
            }

            listaVehiculos.innerHTML = '';
            inputsVehiculos.innerHTML = '';

            vehiculosTemporales.forEach(function (vehiculo, index) {
                camposVehiculo.forEach(function (field) {
                    inputsVehiculos.appendChild(crearHidden(`vehiculos[${index}][${field}]`, vehiculo[field] || ''));
                });

                const placas = vehiculo.placas ? vehiculo.placas : 'SIN PLACAS';
                const marcaLinea = [vehiculo.marca, vehiculo.linea].filter(Boolean).join(' ') || 'VEHÍCULO';
                const tieneAntecedente = String(vehiculo.antecedente_vehiculo || '0') === '1';
                const card = document.createElement('div');
                card.className = 'vehiculo-card';
                card.innerHTML = `
                    <div class="vehiculo-card-head">
                        <div class="min-w-0">
                            <div class="vehiculo-title text-truncate">${escapeHtml(marcaLinea)}</div>
                            <div class="vehiculo-subtitle">${escapeHtml(vehiculo.modelo ? 'Modelo ' + vehiculo.modelo : 'Modelo no especificado')}</div>
                        </div>
                        <span class="vehiculo-placa"><i class="fa-solid fa-id-card"></i> ${escapeHtml(placas)}</span>
                    </div>
                    <div class="vehiculo-card-body">
                        <div class="vehiculo-chip-row">
                            <span class="vehiculo-chip"><i class="fa-solid fa-car-rear"></i> ${escapeHtml(vehiculo.tipo || 'Tipo N/D')}</span>
                            <span class="vehiculo-chip"><i class="fa-solid fa-palette"></i> ${escapeHtml(vehiculo.color || 'Color N/D')}</span>
                            <span class="vehiculo-chip"><i class="fa-solid fa-user-group"></i> ${escapeHtml(vehiculo.capacidad_personas || '0')}</span>
                        </div>
                        <div class="vehiculo-card-actions">
                            <span class="badge ${tieneAntecedente ? 'badge-danger' : 'badge-success'}">
                                Antecedente: ${tieneAntecedente ? 'SÍ' : 'NO'}
                            </span>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-remove-vehiculo="${index}">
                                <i class="fa-solid fa-trash"></i> Quitar
                            </button>
                        </div>
                    </div>
                `;

                listaVehiculos.appendChild(card);
            });

            if (emptyVehiculos) {
                emptyVehiculos.style.display = vehiculosTemporales.length ? 'none' : '';
            }

            if (badgeTotalVehiculos) {
                badgeTotalVehiculos.textContent = `Total: ${vehiculosTemporales.length}`;
            }
        }

        if (tipoGeneralVehiculo && tipoVehiculo) {
            const oldTipo = tipoVehiculo.dataset.oldTipo || '';

            if (!tipoGeneralVehiculo.value && oldTipo) {
                tipoGeneralVehiculo.value = detectarTipoGeneral(oldTipo);
            }

            tipoGeneralVehiculo.addEventListener('change', function () {
                tipoVehiculo.dataset.oldTipo = '';
                cargarCarroceriasActividad();
            });

            cargarCarroceriasActividad();
        }

        document.querySelectorAll('.js-uppercase').forEach(function (input) {
            input.addEventListener('input', function () {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.toLocaleUpperCase('es-MX');

                if (typeof start === 'number' && typeof end === 'number') {
                    this.setSelectionRange(start, end);
                }
            });
        });

        if (formTemporalVehiculo) {
            formTemporalVehiculo.addEventListener('submit', function (event) {
                event.preventDefault();

                if (!formTemporalVehiculo.checkValidity()) {
                    formTemporalVehiculo.reportValidity();
                    return;
                }

                vehiculosTemporales.push(obtenerDatosVehiculo(formTemporalVehiculo));
                renderVehiculosTemporales();
                resetModalVehiculo();

                if (window.jQuery) {
                    $('#modalAgregarVehiculoActividad').modal('hide');
                }
            });
        }

        if (listaVehiculos) {
            listaVehiculos.addEventListener('click', function (event) {
                const btn = event.target.closest('[data-remove-vehiculo]');

                if (!btn) {
                    return;
                }

                const index = Number(btn.dataset.removeVehiculo);
                vehiculosTemporales.splice(index, 1);
                renderVehiculosTemporales();
            });
        }

        renderVehiculosTemporales();

        @if ($errors->any() && old('actividad_vehiculo_modal'))
            if (window.jQuery) {
                $('#modalAgregarVehiculoActividad').modal('show');
            }
        @endif
    });
</script>
