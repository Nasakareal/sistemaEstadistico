window.CroquisUI = (function () {
    function createImage(src) {
        const img = new Image();
        img.src = src;
        return img;
    }

    function init(options) {
        const canvas = document.getElementById(options.canvasId);
        const input = document.getElementById(options.inputId);
        const form = document.getElementById(options.formId);
        const submenuContainer = document.getElementById(options.submenuContainerId || 'croquisSubmenu');

        const ctx = canvas.getContext('2d');

        const dynamicIcons = Array.isArray(options.iconos) ? options.iconos : [];

        const iconAssets = {};
        dynamicIcons.forEach(icono => {
            if (icono && icono.key && icono.src) {
                iconAssets[icono.key] = createImage(icono.src);
            }
        });

        const vehicleCatalog = {
            automovil: [
                { nombre: 'Sedán', subtipo: 'sedan', src: '/img/croquis/vehiculos/automovil/sedan.png' },
                { nombre: 'Hatchback', subtipo: 'hatchback', src: '/img/croquis/vehiculos/automovil/hatchback.png' },
                { nombre: 'Coupé', subtipo: 'coupe', src: '/img/croquis/vehiculos/automovil/coupe.png' }
            ],
            camion: [
                { nombre: 'Torton', subtipo: 'torton', src: '/img/croquis/vehiculos/camion/torton.png' },
                { nombre: 'Rabón', subtipo: 'rabon', src: '/img/croquis/vehiculos/camion/rabon.png' }
            ],
            camioneta: [
                { nombre: 'Pick-up', subtipo: 'pickup', src: '/img/croquis/vehiculos/camioneta/pickup.png' },
                { nombre: 'SUV', subtipo: 'suv', src: '/img/croquis/vehiculos/camioneta/suv.png' },
                { nombre: 'Van', subtipo: 'van', src: '/img/croquis/vehiculos/camioneta/van.png' }
            ],
            bicicleta: [
                { nombre: 'Urbana', subtipo: 'urbana', src: '/img/croquis/vehiculos/bicicleta/urbana.png' },
                { nombre: 'Montaña', subtipo: 'montana', src: '/img/croquis/vehiculos/bicicleta/montana.png' }
            ],
            motocicleta: [
                { nombre: 'Deportiva', subtipo: 'deportiva', src: '/img/croquis/vehiculos/motocicleta/deportiva.png' },
                { nombre: 'Trabajo', subtipo: 'trabajo', src: '/img/croquis/vehiculos/motocicleta/trabajo.png' }
            ],
            maquinaria: [
                { nombre: 'Retroexcavadora', subtipo: 'retroexcavadora', src: '/img/croquis/vehiculos/maquinaria/retroexcavadora.png' },
                { nombre: 'Tractor', subtipo: 'tractor', src: '/img/croquis/vehiculos/maquinaria/tractor.png' }
            ]
        };

        Object.keys(vehicleCatalog).forEach(categoria => {
            vehicleCatalog[categoria] = vehicleCatalog[categoria].map(item => ({
                ...item,
                image: createImage(item.src)
            }));
        });

        let elementos = [];
        if (options.initialData) {
            elementos = window.CroquisModels.deserialize(options.initialData);
        }

        const editor = new window.CroquisEditor({
            canvas,
            ctx,
            elementos,
            assets: {
                iconos: iconAssets,
                vehiculos: vehicleCatalog
            },
            onChange: function (els) {
                input.value = window.CroquisModels.serialize(els);
            }
        });

        input.value = window.CroquisModels.serialize(elementos);

        function agregarTextoConPrompt(textoInicial) {
            const valor = window.prompt('Escribe el texto:', textoInicial || 'Texto');
            if (valor === null) return;
            const limpio = String(valor).trim();
            if (!limpio) return;
            editor.addElement(window.CroquisModels.texto(250, 250, limpio));
        }

        function clearSubmenu() {
            if (!submenuContainer) return;
            submenuContainer.innerHTML = '';
            submenuContainer.style.display = 'none';
        }

        function renderVehicleSubmenu(categoria) {
            if (!submenuContainer) return;

            const items = vehicleCatalog[categoria] || [];
            submenuContainer.innerHTML = '';

            if (!items.length) {
                submenuContainer.style.display = 'none';
                return;
            }

            const wrap = document.createElement('div');
            wrap.className = 'croquis-submenu-panel';

            const title = document.createElement('div');
            title.className = 'croquis-submenu-title';
            title.textContent = categoria.charAt(0).toUpperCase() + categoria.slice(1);
            wrap.appendChild(title);

            const grid = document.createElement('div');
            grid.className = 'croquis-submenu-grid';

            items.forEach(item => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'croquis-submenu-item';

                const img = document.createElement('img');
                img.src = item.src;
                img.alt = item.nombre;

                const span = document.createElement('span');
                span.textContent = item.nombre;

                btn.appendChild(img);
                btn.appendChild(span);

                btn.addEventListener('click', function () {
                    editor.addElement(
                        window.CroquisModels.vehiculo(
                            180,
                            180,
                            categoria,
                            item.subtipo,
                            item.src
                        )
                    );
                    clearSubmenu();
                });

                grid.appendChild(btn);
            });

            wrap.appendChild(grid);
            submenuContainer.appendChild(wrap);
            submenuContainer.style.display = 'block';
        }

        function agregarIconoDinamico(iconKey) {
            const icono = dynamicIcons.find(item => item.key === iconKey);

            if (!icono) {
                return;
            }

            clearSubmenu();
            editor.addElement(window.CroquisModels.icono(200, 200, icono.key, icono.src));
        }

        const actions = {
            abrirMenuAutomovil: () => renderVehicleSubmenu('automovil'),
            abrirMenuCamion: () => renderVehicleSubmenu('camion'),
            abrirMenuCamioneta: () => renderVehicleSubmenu('camioneta'),
            abrirMenuBicicleta: () => renderVehicleSubmenu('bicicleta'),
            abrirMenuMotocicleta: () => renderVehicleSubmenu('motocicleta'),
            abrirMenuMaquinaria: () => renderVehicleSubmenu('maquinaria'),

            agregarCalle: () => {
                clearSubmenu();
                editor.addElement(window.CroquisModels.calle(220, 180));
            },
            agregarCurva: () => {
                clearSubmenu();
                editor.addElement(window.CroquisModels.curva(260, 220));
            },
            agregarCruce: () => {
                clearSubmenu();
                editor.addElement(window.CroquisModels.cruce(260, 220));
            },
            agregarEntronque: () => {
                clearSubmenu();
                editor.addElement(window.CroquisModels.entronque(260, 220));
            },
            agregarGlorieta: () => {
                clearSubmenu();
                editor.addElement(window.CroquisModels.glorieta(360, 260));
            },

            agregarTexto: () => {
                clearSubmenu();
                agregarTextoConPrompt('Texto');
            },
            agregarEtiquetaCalle: () => {
                clearSubmenu();
                agregarTextoConPrompt('Nombre de calle');
            },
            agregarEtiquetaReferencia: () => {
                clearSubmenu();
                agregarTextoConPrompt('Referencia');
            },

            limpiar: () => {
                clearSubmenu();
                editor.clear();
            },
            guardar: () => {
                input.value = window.CroquisModels.serialize(editor.elementos);
                form.submit();
            }
        };

        document.querySelectorAll('[data-croquis-action]').forEach(btn => {
            const actionName = btn.getAttribute('data-croquis-action');

            if (actionName === 'agregarIconoDinamico') {
                btn.addEventListener('click', function () {
                    agregarIconoDinamico(btn.getAttribute('data-icon-key'));
                });
                return;
            }

            if (actions[actionName]) {
                btn.addEventListener('click', actions[actionName]);
            }
        });

        document.addEventListener('click', function (e) {
            if (!submenuContainer) return;

            const insideToolbar = e.target.closest('.croquis-toolbar');
            const insideSubmenu = e.target.closest('#' + submenuContainer.id);

            if (!insideToolbar && !insideSubmenu) {
                clearSubmenu();
            }
        });

        return editor;
    }

    return {
        init
    };
})();
