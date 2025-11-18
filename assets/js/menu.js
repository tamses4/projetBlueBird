// assets/js/menu.js
document.addEventListener('DOMContentLoaded', function () {
    // Menu public
    const toggle = document.getElementById('menu-toggle');
    const nav = document.getElementById('nav-menu');

    if (toggle && nav) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            nav.classList.toggle('active');
        });
    }

    // Menu admin
    const adminToggle = document.getElementById('admin-menu-toggle');
    const adminNav = document.getElementById('admin-nav');

    if (adminToggle && adminNav) {
        adminToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            adminNav.classList.toggle('active');
        });
    }

    // Fermer les menus en cliquant ailleurs
    document.addEventListener('click', () => {
        if (nav && nav.classList.contains('active')) {
            nav.classList.remove('active');
        }
        if (adminNav && adminNav.classList.contains('active')) {
            adminNav.classList.remove('active');
        }
    });

    // Empêcher la fermeture quand on clique dans le menu
    if (nav) nav.addEventListener('click', (e) => e.stopPropagation());
    if (adminNav) adminNav.addEventListener('click', (e) => e.stopPropagation());
});