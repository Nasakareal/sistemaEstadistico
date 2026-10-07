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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSidebarLocation);
    } else {
        initializeSidebarLocation();
    }
})();
