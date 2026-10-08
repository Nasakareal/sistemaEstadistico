(function () {
    'use strict';

    function labelOf(link) {
        var paragraph = link ? link.querySelector('p') : null;
        if (!paragraph) return '';

        return Array.from(paragraph.childNodes)
            .filter(function (node) { return node.nodeType === Node.TEXT_NODE; })
            .map(function (node) { return node.textContent; })
            .join(' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function initializeSidebarLocation() {
        var nav = document.querySelector('.sv-sidebar-nav');
        var current = document.getElementById('svSidebarCurrent');
        if (!nav || !current) return;

        var activeLinks = Array.from(nav.querySelectorAll('.nav-link.active'));
        var leaf = activeLinks.slice().reverse().find(function (link) {
            return !link.parentElement.classList.contains('has-treeview');
        }) || activeLinks[activeLinks.length - 1];

        if (!leaf) return;

        var ancestors = [];
        var item = leaf.closest('.nav-item');
        while (item) {
            var parentTree = item.parentElement && item.parentElement.closest('.nav-treeview');
            if (!parentTree) break;
            var parentItem = parentTree.closest('.nav-item.has-treeview');
            if (!parentItem) break;
            var parentLink = Array.from(parentItem.children).find(function (child) {
                return child.classList && child.classList.contains('nav-link');
            });
            var text = labelOf(parentLink);
            if (text) ancestors.unshift(text);
            item = parentItem;
        }

        current.querySelector('strong').textContent = labelOf(leaf) || document.title;
        var path = current.querySelector('small');
        path.textContent = ancestors.join(' / ');
        path.hidden = ancestors.length === 0;
        current.hidden = false;

        window.requestAnimationFrame(function () {
            var sidebar = document.querySelector('.main-sidebar .sidebar');
            if (!sidebar) return;
            var leafRect = leaf.getBoundingClientRect();
            var sideRect = sidebar.getBoundingClientRect();
            if (leafRect.top < sideRect.top + 125 || leafRect.bottom > sideRect.bottom - 18) {
                leaf.scrollIntoView({ block: 'center', behavior: 'auto' });
            }
        });
    }

    function initializeSiniestrosIntegrity() {
        var nav = document.querySelector('.sv-sidebar-nav');
        var reportUrl = nav ? nav.getAttribute('data-sv-integrity-report-url') : '';
        var target = document.getElementById('menuSiniestros');
        if (!nav || !reportUrl || !target) return;

        var originalMarkup = target.outerHTML;
        var originalParent = target.parentNode;
        var originalNextSibling = target.nextSibling;
        var restoring = false;
        var checkScheduled = false;
        var lastReport = Object.create(null);

        function directToggle(item) {
            return item ? Array.from(item.children).find(function (child) {
                return child.classList && child.classList.contains('nav-link');
            }) : null;
        }

        function keepOpen() {
            if (!target || target.getAttribute('data-sv-force-open') !== 'true') return;

            if (!target.classList.contains('menu-open')) target.classList.add('menu-open');
            var toggle = directToggle(target);
            var tree = Array.from(target.children).find(function (child) {
                return child.classList && child.classList.contains('nav-treeview');
            });

            if (toggle && toggle.getAttribute('aria-expanded') !== 'true') {
                toggle.setAttribute('aria-expanded', 'true');
            }
            if (tree && tree.style.display !== 'block') tree.style.display = 'block';
        }

        function detectedState() {
            if (!target || !target.isConnected || !nav.contains(target)) return 'element_absent';
            if (target.hasAttribute('hidden')) return 'hidden_attribute';
            if (target.classList.contains('d-none') || target.classList.contains('invisible')) return 'hidden_class';

            var inlineStyle = (target.getAttribute('style') || '').toLowerCase().replace(/\s+/g, '');
            if (inlineStyle.includes('display:none') || inlineStyle.includes('visibility:hidden') || inlineStyle.includes('opacity:0')) {
                return 'inline_hidden';
            }

            var toggle = directToggle(target);
            if (!toggle || labelOf(toggle) !== 'Siniestros (CHOQUES)') return 'label_modified';
            if (!target.classList.contains('nav-item') || !target.classList.contains('has-treeview')) return 'structure_modified';
            if (!target.querySelector('.nav-treeview a[href$="/hechos"]')) return 'structure_modified';

            var computed = window.getComputedStyle(target);
            if (computed.display === 'none' || computed.visibility === 'hidden' || Number(computed.opacity) === 0) {
                return 'computed_hidden';
            }

            return '';
        }

        function showRestoredWarning() {
            var existing = document.getElementById('svIntegrityWarning');
            if (existing) existing.remove();

            var alert = document.createElement('div');
            alert.id = 'svIntegrityWarning';
            alert.className = 'alert alert-warning alert-dismissible shadow';
            alert.setAttribute('role', 'alert');
            alert.style.cssText = 'position:fixed;right:18px;top:72px;z-index:20000;max-width:430px;';
            alert.innerHTML = '<button type="button" class="close" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>'
                + '<strong>Menú restaurado.</strong> Se detectó que Siniestros fue ocultado o modificado, posiblemente desde las herramientas del navegador. El incidente fue registrado.';
            alert.querySelector('button').addEventListener('click', function () { alert.remove(); });
            document.body.appendChild(alert);
        }

        function reportDetection(state) {
            var now = Date.now();
            if (lastReport[state] && now - lastReport[state] < 30000) return;
            lastReport[state] = now;

            var csrf = document.querySelector('meta[name="csrf-token"]');
            fetch(reportUrl, {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: true,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf ? csrf.content : ''
                },
                body: JSON.stringify({
                    element: 'menu_siniestros',
                    detected_state: state,
                    restored: true,
                    page_path: window.location.pathname
                })
            }).catch(function () {
                // La restauración local no depende de que el reporte llegue al servidor.
            });
        }

        function restore(state) {
            restoring = true;
            var wrapper = document.createElement('div');
            wrapper.innerHTML = originalMarkup;
            var replacement = wrapper.firstElementChild;

            if (target && target.isConnected) {
                target.replaceWith(replacement);
            } else if (originalNextSibling && originalNextSibling.parentNode === originalParent) {
                originalParent.insertBefore(replacement, originalNextSibling);
            } else {
                originalParent.appendChild(replacement);
            }

            target = replacement;
            keepOpen();
            showRestoredWarning();
            reportDetection(state);

            window.setTimeout(function () { restoring = false; }, 0);
        }

        function verify() {
            checkScheduled = false;
            if (restoring) return;

            var state = detectedState();
            if (state) restore(state);
            else keepOpen();
        }

        function scheduleVerification() {
            if (checkScheduled || restoring) return;
            checkScheduled = true;
            window.requestAnimationFrame(verify);
        }

        nav.addEventListener('click', function (event) {
            if (!target || target.getAttribute('data-sv-force-open') !== 'true') return;
            var toggle = directToggle(target);
            if (toggle && (event.target === toggle || toggle.contains(event.target))) {
                event.preventDefault();
                event.stopImmediatePropagation();
                keepOpen();
            }
        }, true);

        var observer = new MutationObserver(scheduleVerification);
        observer.observe(nav, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: true,
            attributeFilter: ['class', 'hidden', 'style']
        });

        keepOpen();
        window.setInterval(scheduleVerification, 2000);
    }

    function initialize() {
        initializeSidebarLocation();
        initializeSiniestrosIntegrity();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
})();
