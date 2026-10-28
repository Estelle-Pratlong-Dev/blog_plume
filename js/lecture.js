// Préférence de lecture locale : le réglage continue de fonctionner si le stockage est bloqué.
const readingText = document.querySelector('#texte-lecture');
const readingControls = document.querySelector('#reading-controls');
const smallerButton = document.querySelector('#reading-smaller');
const largerButton = document.querySelector('#reading-larger');
const sizeValue = document.querySelector('#reading-size-value');

if (readingText && readingControls && smallerButton && largerButton && sizeValue) {
  const preferenceKey = 'plume-reading-size-centered-v1';
  const minimum = 50;
  const maximum = 150;
  const step = 10;
  const defaultSize = 100;
  let currentSize = defaultSize;

  function updateSize(value, persist = true) {
    const parsed = Number(value);
    const percentage = Number.isFinite(parsed)
      ? Math.max(minimum, Math.min(maximum, Math.round(parsed / step) * step))
      : defaultSize;
    readingText.style.setProperty('--reading-scale', String(percentage / 100));
    currentSize = percentage;
    sizeValue.textContent = `Taille du texte : ${percentage} %`;
    smallerButton.disabled = percentage === minimum;
    largerButton.disabled = percentage === maximum;
    if (persist) {
      try {
        localStorage.setItem(preferenceKey, String(percentage));
      } catch {
        // Le navigateur peut refuser le stockage ; la taille reste appliquée à la page.
      }
    }
  }

  let savedSize = defaultSize;
  try {
    savedSize = localStorage.getItem(preferenceKey) ?? defaultSize;
  } catch {
    // Une préférence absente ou inaccessible conserve la taille confortable par défaut.
  }
  updateSize(savedSize, false);
  readingControls.hidden = false;
  smallerButton.addEventListener('click', () => updateSize(currentSize - step));
  largerButton.addEventListener('click', () => updateSize(currentSize + step));
}

// Dissuasion de la copie limitée aux œuvres, indépendante du réglage de taille.
const protectedText = document.querySelector('.protected-text');
if (protectedText) {
  ['copy', 'cut', 'contextmenu', 'dragstart'].forEach(eventName => {
    protectedText.addEventListener(eventName, event => event.preventDefault());
  });
}
