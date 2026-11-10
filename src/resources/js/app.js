const menuButton = document.querySelector('[data-menu-toggle]');
const navigation = document.getElementById('primary-navigation');

if (menuButton instanceof HTMLButtonElement && navigation instanceof HTMLElement) {
    const openIcon = menuButton.querySelector('[data-menu-open-icon]');
    const closeIcon = menuButton.querySelector('[data-menu-close-icon]');

    const setMenuOpen = (isOpen) => {
        navigation.classList.toggle('hidden', !isOpen);
        menuButton.setAttribute('aria-expanded', String(isOpen));
        menuButton.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');
        openIcon?.classList.toggle('hidden', isOpen);
        closeIcon?.classList.toggle('hidden', !isOpen);
    };

    menuButton.addEventListener('click', () => {
        setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true');
    });

    navigation.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('a')) {
            setMenuOpen(false);
        }
    });

    document.addEventListener('click', (event) => {
        if (
            menuButton.getAttribute('aria-expanded') === 'true'
            && event.target instanceof Node
            && !navigation.contains(event.target)
            && !menuButton.contains(event.target)
        ) {
            setMenuOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true') {
            setMenuOpen(false);
            menuButton.focus();
        }
    });

    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches) {
            setMenuOpen(false);
        }
    });
}
