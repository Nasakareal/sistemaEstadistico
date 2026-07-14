window.CroquisUI = (function () {
    function createImage(src, onReady) {
        const img = new Image();

        if (typeof onReady === 'function') {
            img.addEventListener('load', onReady);
            img.addEventListener('error', onReady);
        }

        img.src = src;
        return img;
    }

    function init(options) {
        const canvas = document.getElementById(options.canvasId);
        const input = document.getElementById(options.inputId);
        const previewInput = options.previewInputId ? document.getElementById(options.previewInputId) : null;
        const form = document.getElementById(options.formId);
        const submenuContainer = document.getElementById(options.submenuContainerId || 'croquisSubmenu');

        const ctx = canvas.getContext('2d');

        const iconCategories = Array.isArray(options.iconosCategorias) ? options.iconosCategorias : [];
        const flatIcons = Array.isArray(options.iconos) ? options.iconos : [];
        const categorizedIcons = iconCategories.length
            ? iconCategories.reduce((items, categoria) => {
                const categoriaItems = Array.isArray(categoria.items) ? categoria.items : [];
                return items.concat(categoriaItems.map(item => ({
                    ...item,
                    categoria: categoria.key
                })));
            }, [])
            : [];
        const dynamicIcons = iconCategories.length ? categorizedIcons : flatIcons;
        const assetIcons = flatIcons.length ? flatIcons.concat(categorizedIcons) : dynamicIcons;
        const vehicleCategories = Array.isArray(options.vehiculos) ? options.vehiculos : [];
        const defaultIcon = options.defaultIcono || null;
        let editor = null;

        function renderWhenAssetIsReady() {
            if (editor) {
                editor.render();
            }
        }

        const iconAssets = {};
        assetIcons.forEach(icono => {
            if (icono && icono.key && icono.src) {
                iconAssets[icono.key] = createImage(icono.src, renderWhenAssetIsReady);
            }
        });

        const iconCatalog = {};

        iconCategories.forEach(categoria => {
            if (!categoria || !categoria.key) {
                return;
            }

            iconCatalog[categoria.key] = {
                key: categoria.key,
                label: categoria.label || categoria.key,
                items: Array.isArray(categoria.items) ? categoria.items : []
            };
        });

        const vehicleCatalog = {};
        const vehicleAssets = {};

        vehicleCategories.forEach(categoria => {
            if (!categoria || !categoria.key) {
                return;
            }

            const categoriaKey = categoria.key;
            const items = Array.isArray(categoria.items) ? categoria.items : [];

            vehicleCatalog[categoriaKey] = {
                key: categoriaKey,
                label: categoria.label || categoriaKey,
                items: items.map(item => ({
                    ...item,
                    image: createImage(item.src, renderWhenAssetIsReady)
                }))
            };

            vehicleAssets[categoriaKey] = {};

            vehicleCatalog[categoriaKey].items.forEach(item => {
                if (item.subtipo) {
                    vehicleAssets[categoriaKey][item.subtipo] = item.image;
                }
            });
        });

        function hasDefaultIcon(elementos) {
            if (!defaultIcon || !defaultIcon.key) {
                return true;
            }

            return elementos.some(el => {
                return el.tipo === 'icono' && (el.clave === defaultIcon.key || el.nombre === defaultIcon.key);
            });
        }

        function createDefaultIconElement() {
            const size = 72;
            const margin = 24;
            const el = window.CroquisModels.icono(
                canvas.width - (size / 2) - margin,
                (size / 2) + margin,
                defaultIcon.key,
                defaultIcon.src
            );

            el.ancho = size;
            el.alto = size;
            return el;
        }

        function ensureDefaultElements(elementos) {
            const list = Array.isArray(elementos) ? elementos : [];

            if (defaultIcon && defaultIcon.key && defaultIcon.src && !hasDefaultIcon(list)) {
                list.push(createDefaultIconElement());
            }

            return list;
        }

        let elementos = [];
        if (options.initialData) {
            elementos = window.CroquisModels.deserialize(options.initialData);
        }
        elementos = ensureDefaultElements(elementos);

        editor = new window.CroquisEditor({
            canvas,
            ctx,
            elementos,
            assets: {
                iconos: iconAssets,
                vehiculos: vehicleAssets
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

        function renderIconSubmenu(categoria) {
            if (!submenuContainer) return;

            const categoriaData = iconCatalog[categoria] || { label: categoria, items: [] };
            const items = categoriaData.items || [];
            submenuContainer.innerHTML = '';

            const wrap = document.createElement('div');
            wrap.className = 'croquis-submenu-panel';

            const title = document.createElement('div');
            title.className = 'croquis-submenu-title';
            title.textContent = categoriaData.label || categoria;
            wrap.appendChild(title);

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'croquis-submenu-empty';
                empty.textContent = 'Aún no hay imágenes en esta categoría.';
                wrap.appendChild(empty);
                submenuContainer.appendChild(wrap);
                submenuContainer.style.display = 'block';
                return;
            }

            const grid = document.createElement('div');
            grid.className = 'croquis-submenu-grid';

            items.forEach(item => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'croquis-submenu-item';

                const img = document.createElement('img');
                img.src = item.src;
                img.alt = item.label || item.key;

                const span = document.createElement('span');
                span.textContent = item.label || item.key;

                btn.appendChild(img);
                btn.appendChild(span);

                btn.addEventListener('click', function () {
                    editor.addElement(window.CroquisModels.icono(200, 200, item.key, item.src));
                    clearSubmenu();
                });

                grid.appendChild(btn);
            });

            wrap.appendChild(grid);
            submenuContainer.appendChild(wrap);
            submenuContainer.style.display = 'block';
        }

        function renderVehicleSubmenu(categoria) {
            if (!submenuContainer) return;

            const categoriaData = vehicleCatalog[categoria] || { label: categoria, items: [] };
            const items = categoriaData.items || [];
            submenuContainer.innerHTML = '';

            const wrap = document.createElement('div');
            wrap.className = 'croquis-submenu-panel';

            const title = document.createElement('div');
            title.className = 'croquis-submenu-title';
            title.textContent = categoriaData.label || categoria.charAt(0).toUpperCase() + categoria.slice(1);
            wrap.appendChild(title);

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'croquis-submenu-empty';
                empty.textContent = 'Aún no hay imágenes en esta categoría.';
                wrap.appendChild(empty);
                submenuContainer.appendChild(wrap);
                submenuContainer.style.display = 'block';
                return;
            }

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
                    const vehiculo = window.CroquisModels.vehiculo(
                        180,
                        180,
                        categoria,
                        item.subtipo,
                        item.src
                    );

                    const size = getDefaultVehicleSize(item);
                    vehiculo.ancho = size.ancho;
                    vehiculo.alto = size.alto;

                    editor.addElement(vehiculo);
                    clearSubmenu();
                });

                grid.appendChild(btn);
            });

            wrap.appendChild(grid);
            submenuContainer.appendChild(wrap);
            submenuContainer.style.display = 'block';
        }

        function getDefaultVehicleSize(item) {
            const maxSize = 90;
            const naturalWidth = Math.max(1, Number(item.anchoOriginal || item.ancho || maxSize));
            const naturalHeight = Math.max(1, Number(item.altoOriginal || item.alto || maxSize));

            if (naturalWidth >= naturalHeight) {
                return {
                    ancho: maxSize,
                    alto: Math.max(20, Math.round(maxSize * (naturalHeight / naturalWidth)))
                };
            }

            return {
                ancho: Math.max(20, Math.round(maxSize * (naturalWidth / naturalHeight))),
                alto: maxSize
            };
        }

        function agregarIconoDinamico(iconKey) {
            const icono = dynamicIcons.find(item => item.key === iconKey);

            if (!icono) {
                return;
            }

            clearSubmenu();
            editor.addElement(window.CroquisModels.icono(200, 200, icono.key, icono.src));
        }

        function cambiarLateral(side, type) {
            clearSubmenu();
            if (!editor.setSelectedRoadEdge(side, type)) {
                window.alert('Selecciona primero una calle, curva, cruce, entronque o glorieta.');
            }
        }

        const actions = {
            agregarCalle: () => {
                clearSubmenu();
                editor.addElement(window.CroquisModels.calle(220, 180));
            },
            agregarCurva: () => {
                clearSubmenu();
                editor.addElement(window.CroquisModels.curva(260, 220));
            },
            agregarCamellon: () => {
                clearSubmenu();
                editor.addElement(window.CroquisModels.camellon(300, 260));
            },
            agregarBanqueta: () => {
                clearSubmenu();
                editor.addElement(window.CroquisModels.banqueta(300, 260));
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
            agregarCarril: () => {
                clearSubmenu();
                editor.changeSelectedLanes(1);
            },
            quitarCarril: () => {
                clearSubmenu();
                editor.changeSelectedLanes(-1);
            },
            banquetaIzquierda: () => cambiarLateral('izquierdo', 'banqueta'),
            camellonIzquierdo: () => cambiarLateral('izquierdo', 'camellon'),
            quitarLateralIzquierdo: () => cambiarLateral('izquierdo', null),
            banquetaDerecha: () => cambiarLateral('derecho', 'banqueta'),
            camellonDerecho: () => cambiarLateral('derecho', 'camellon'),
            quitarLateralDerecho: () => cambiarLateral('derecho', null),

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

            copiar: () => {
                clearSubmenu();
                if (!editor.copySelected()) {
                    window.alert('Selecciona primero el elemento que quieres copiar.');
                }
            },
            pegar: () => {
                clearSubmenu();
                if (!editor.pasteCopied()) {
                    window.alert('Primero copia un elemento del croquis.');
                }
            },
            duplicar: () => {
                clearSubmenu();
                if (!editor.duplicateSelected()) {
                    window.alert('Selecciona primero el elemento que quieres duplicar.');
                }
            },

            limpiar: () => {
                clearSubmenu();
                editor.setElementos(ensureDefaultElements([]));
            },
            guardar: () => {
                input.value = window.CroquisModels.serialize(editor.elementos);
                if (previewInput) {
                    previewInput.value = editor.getPreviewDataUrl();
                }
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

            if (actionName === 'abrirMenuVehiculo') {
                btn.addEventListener('click', function () {
                    renderVehicleSubmenu(btn.getAttribute('data-vehicle-category'));
                });
                return;
            }

            if (actionName === 'abrirMenuIcono') {
                btn.addEventListener('click', function () {
                    renderIconSubmenu(btn.getAttribute('data-icon-category'));
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
