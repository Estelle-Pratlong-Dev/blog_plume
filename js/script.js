// Navigation mobile : la navigation reste visible si JavaScript est indisponible.
const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#navigation');

if (menuButton && navigation) {
  document.documentElement.classList.add('menu-ready');
  menuButton.hidden = false;

  function closeMenu() {
    menuButton.setAttribute('aria-expanded', 'false');
    navigation.classList.remove('is-open');
  }

  menuButton.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!isOpen));
    navigation.classList.toggle('is-open', !isOpen);
  });

  // Échap referme le menu et rend le focus au bouton qui l’a ouvert.
  navigation.addEventListener('keydown', event => {
    if (event.key === 'Escape' && navigation.classList.contains('is-open')) {
      closeMenu();
      menuButton.focus();
    }
  });

  // Un retour depuis une largeur de bureau repart avec un état mobile cohérent.
  const mobileQuery = window.matchMedia('(max-width: 700px)');
  mobileQuery.addEventListener('change', closeMenu);
}
