// Éditeur progressif : le champ HTML reste utilisable si JavaScript est désactivé.
const form = document.querySelector('#edition');
const source = document.querySelector('#contenu-source');
const editor = document.querySelector('#editeur');
const titleInput = document.querySelector('#titre');
const fileInput = document.querySelector('#document');
const status = document.querySelector('#import-status');
let dirty = false;
let savedRange = null;

// La description n’est obligatoire que si une image est présente ou en cours d’ajout.
if (form) {
  const imageField = form.querySelector('#image');
  const imageDescription = form.querySelector('#image-description');
  const removeImage = form.querySelector('[name="retirer_image"]');
  if (imageField && imageDescription) {
    function updateImageRequirement() {
      const newImage = imageField.files.length > 0;
      const keptImage = imageDescription.dataset.hasImage === 'true' && !removeImage?.checked;
      imageDescription.required = newImage || keptImage;
      imageDescription.setAttribute('aria-required', String(imageDescription.required));
    }
    updateImageRequirement();
    imageField.addEventListener('change', updateImageRequirement);
    removeImage?.addEventListener('change', updateImageRequirement);
  }
}

// Adapte les champs sans effacer les valeurs lorsque l’autrice change de rubrique.
if (form) {
  const categorySelect = form.querySelector('#categorie');
  const categoryGroups = form.querySelectorAll('[data-category-only]');
  if (categorySelect) {
    function updateCategoryFields() {
      categoryGroups.forEach(group => {
        const applicable = group.dataset.categoryOnly.split(' ').includes(categorySelect.value);
        group.hidden = !applicable;
        group.querySelectorAll('input, select, textarea').forEach(field => {
          field.disabled = !applicable;
          field.required = applicable && field.dataset.required === 'true';
        });
      });
    }
    updateCategoryFields();
    categorySelect.addEventListener('change', updateCategoryFields);
  }
}

// Nettoie aussi le collage avant son insertion ; le serveur filtre à nouveau à l’enregistrement.
function cleanHtml(html) {
  const documentHtml = new DOMParser().parseFromString(html, 'text/html');
  const allowed = new Set(['P', 'H2', 'H3', 'STRONG', 'B', 'EM', 'I', 'U', 'BR', 'UL', 'OL', 'LI', 'BLOCKQUOTE', 'SPAN']);
  const blocked = new Set(['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'SVG', 'MATH', 'TEMPLATE', 'FORM', 'INPUT', 'IMG', 'VIDEO', 'AUDIO']);
  function cleanNode(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      return document.createTextNode(node.textContent);
    }
    const fragment = document.createDocumentFragment();
    if (node.nodeType !== Node.ELEMENT_NODE || blocked.has(node.tagName)) {
      return fragment;
    }
    const element = allowed.has(node.tagName) ? document.createElement(node.tagName.toLowerCase()) : fragment;
    if (element instanceof HTMLElement) {
      const allowedFontKeys = JSON.parse(form?.dataset.fonts || '[]');
      const fontClass = allowedFontKeys.map(key => 'reading-font-' + key).find(name => node.classList.contains(name));
      const alignment = node.style.textAlign;
      const className = ['align-center', 'align-right', 'align-justify'].find(value => node.classList.contains(value));
      if (className) {
        element.className = className;
      } else if (['center', 'right', 'justify'].includes(alignment)) {
        element.className = `align-${alignment}`;
      }
      if (fontClass) {
        element.classList.add(fontClass);
      }
    }
    node.childNodes.forEach(child => element.append(cleanNode(child)));
    return element;
  }
  const result = document.createElement('div');
  documentHtml.body.childNodes.forEach(node => result.append(cleanNode(node)));
  return result.innerHTML;
}

if (form && source && editor) {
  document.querySelector('#rich-editor').hidden = false;
  document.querySelector('#preview-button').hidden = false;
  source.hidden = true;
  source.required = false;
  editor.innerHTML = cleanHtml(source.value);

  // Ancien choix global conservé sans sélecteur ; les polices Word restent dans les portions importées.
  const fontField = document.querySelector('#police');
  const fontPreview = document.querySelector('#preview-content');
  const allowedFontKeys = JSON.parse(form.dataset.fonts || '[]');
  function updateFont() {
    const chosen = allowedFontKeys.includes(fontField?.value) ? fontField.value : 'defaut';
    [editor, fontPreview].filter(Boolean).forEach(element => {
      allowedFontKeys.forEach(font => element.classList.remove('reading-font-' + font));
      element.classList.add('reading-font-' + chosen);
    });
  }
  updateFont();

  // Conserve la sélection pendant l’utilisation du clavier dans la barre d’outils.
  document.addEventListener('selectionchange', () => {
    const selection = window.getSelection();
    if (selection.rangeCount && editor.contains(selection.anchorNode)) {
      savedRange = selection.getRangeAt(0).cloneRange();
    }
  });
  document.querySelectorAll('[data-command]').forEach(button => {
    button.addEventListener('mousedown', event => event.preventDefault());
    button.addEventListener('click', () => {
      editor.focus();
      if (savedRange) {
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(savedRange);
      }
      document.execCommand(button.dataset.command, false, button.dataset.value || null);
      dirty = true;
    });
  });

  editor.addEventListener('paste', event => {
    event.preventDefault();
    const html = event.clipboardData.getData('text/html');
    if (html) {
      document.execCommand('insertHTML', false, cleanHtml(html));
    } else {
      const container = document.createElement('div');
      event.clipboardData.getData('text/plain').split(/\r?\n/).forEach(line => {
        const paragraph = document.createElement('p');
        paragraph.textContent = line;
        container.append(paragraph);
      });
      document.execCommand('insertHTML', false, container.innerHTML);
    }
    dirty = true;
  });

  // Seuls les documents Word passent par la zone de dépôt ; pas de HTML glissé dans l’éditeur.
  editor.addEventListener('drop', event => event.preventDefault());
  form.addEventListener('input', () => { dirty = true; });
  form.addEventListener('submit', event => {
    if (!editor.textContent.trim()) {
      event.preventDefault();
      status.textContent = 'Ajoutez du texte avant d’enregistrer.';
      editor.focus();
      return;
    }
    source.value = cleanHtml(editor.innerHTML);
    dirty = false;
  });
  document.querySelector('#preview-button').addEventListener('click', () => {
    document.querySelector('#preview-content').innerHTML = cleanHtml(editor.innerHTML);
    document.querySelector('#preview').hidden = false;
    document.querySelector('#preview').scrollIntoView({ block: 'start' });
  });

  // L’original DOCX reste temporaire ; seul le contenu filtré rejoint l’éditeur.
  async function importDocument(file) {
    if (!file || !file.name.toLowerCase().endsWith('.docx') || file.size > 5 * 1024 * 1024) {
      status.textContent = 'Choisissez un fichier .docx de moins de 5 Mo.';
      return;
    }
    if (editor.textContent.trim() && !window.confirm('Remplacer le contenu actuel par le document Word ? Les changements non enregistrés de ce texte seront remplacés.')) {
      return;
    }
    const button = document.querySelector('#importer-document');
    button.disabled = true;
    status.textContent = 'Lecture du document en cours…';
    const data = new FormData();
    data.append('document', file);
    data.append('csrf', form.elements.csrf.value);
    try {
      const response = await fetch(form.dataset.importUrl, { method: 'POST', body: data, credentials: 'same-origin' });
      if (!response.headers.get('Content-Type')?.includes('application/json')) {
        throw new Error('La session a expiré ou le fichier est trop volumineux. Enregistrez votre texte puis reconnectez-vous.');
      }
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.erreur || 'Le document n’a pas pu être lu.');
      }
      editor.innerHTML = cleanHtml(result.html);
      if (fontField) fontField.value = 'defaut';
      updateFont();
      if (!titleInput.value.trim()) {
        titleInput.value = result.titre.slice(0, 180);
      }
      dirty = true;
      savedRange = null;
      status.textContent = `Document importé, pas encore enregistré. ${result.avertissements.join(' ')}`;
    } catch (error) {
      status.textContent = error.message;
    } finally {
      button.disabled = false;
    }
  }
  document.querySelector('#importer-document').addEventListener('click', () => importDocument(fileInput.files[0]));
  const dropZone = document.querySelector('#docx-drop');
  dropZone.addEventListener('dragover', event => {
    event.preventDefault();
    dropZone.classList.add('is-dragging');
  });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('is-dragging'));
  dropZone.addEventListener('drop', event => {
    event.preventDefault();
    dropZone.classList.remove('is-dragging');
    importDocument(event.dataTransfer.files[0]);
  });
  window.addEventListener('beforeunload', event => {
    if (dirty) {
      event.preventDefault();
      event.returnValue = '';
    }
  });
}
