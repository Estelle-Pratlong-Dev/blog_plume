// Éditeur progressif : le champ HTML reste utilisable si JavaScript est désactivé.
const form = document.querySelector('#edition');
const source = document.querySelector('#contenu-source');
const editor = document.querySelector('#editeur');
const titleInput = document.querySelector('#titre');
const fileInput = document.querySelector('#document');
const status = document.querySelector('#import-status, #editor-status');
let dirty = false;
let savedRange = null;

// La description n’est obligatoire que si une image est présente ou en cours d’ajout.
if (form) {
  const imageField = form.querySelector('#image');
  const imageDescription = form.querySelector('#image-description');
  const removeImage = form.querySelector('[name="retirer_image"]');
  const requiredMark = form.querySelector('#image-required-mark');
  if (imageField && imageDescription) {
    function updateImageRequirement() {
      const newImage = imageField.files.length > 0;
      const keptImage = imageDescription.dataset.hasImage === 'true' && !removeImage?.checked;
      imageDescription.required = newImage || keptImage;
      if (requiredMark) requiredMark.hidden = !imageDescription.required;
      imageDescription.setAttribute('aria-required', String(imageDescription.required));
    }
    updateImageRequirement();
    imageField.addEventListener('change', updateImageRequirement);
    removeImage?.addEventListener('change', updateImageRequirement);
  }
}

// Met à jour les jours réservés si l’année de l’Avent change.
if (form) {
  const adventYear = form.querySelector('#annee-avent');
  const adventDay = form.querySelector('#jour');
  if (adventYear && adventDay) {
    const reservedDays = JSON.parse(form.dataset.adventDays || '{}');
    adventYear.addEventListener('input', () => {
      const reserved = reservedDays[adventYear.value] || {};
      Array.from(adventDay.options).filter(option => option.value).forEach(option => {
        const reason = reserved[option.value];
        option.disabled = Boolean(reason);
        option.textContent = 'Jour ' + option.value + (reason ? ' — ' + reason : '');
      });
      if (adventDay.selectedOptions[0]?.disabled) adventDay.value = '';
    });
  }
}

// Nettoie aussi le collage avant son insertion ; le serveur filtre à nouveau à l’enregistrement.
function cleanHtml(html) {
  const documentHtml = new DOMParser().parseFromString(html, 'text/html');
  const allowed = new Set(['P', 'H2', 'H3', 'STRONG', 'B', 'EM', 'I', 'U', 'BR', 'UL', 'OL', 'LI', 'BLOCKQUOTE', 'SPAN', 'SUP']);
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
      const dialogueList = node.classList.contains('dialogue-list');
      const alignment = node.style.textAlign;
      const className = ['align-center', 'align-right', 'align-justify'].find(value => node.classList.contains(value));
      if (className) {
        element.className = className;
      } else if (['center', 'right', 'justify'].includes(alignment)) {
        element.className = `align-${alignment}`;
      }
      if (node.tagName === 'P' && node.classList.contains('docx-note')) {
        element.classList.add('docx-note');
      }
      ['docx-extrait', 'docx-vedette'].forEach(name => {
        if (node.classList.contains(name)) element.classList.add(name);
      });
      if (fontClass) {
        element.classList.add(fontClass);
      }
      if (dialogueList && node.tagName === 'UL') {
        element.classList.add('dialogue-list');
      }
      if (['P', 'H2', 'H3', 'LI', 'BLOCKQUOTE'].includes(node.tagName)) {
        ['margin-left', 'margin-right', 'text-indent'].forEach(property => {
          const value = node.style.getPropertyValue(property).trim();
          const match = value.match(/^(-?\d+(?:\.\d+)?)em$/);
          if (match && Number(match[1]) >= (property === 'text-indent' ? -8 : 0) && Number(match[1]) <= 8) {
            element.style.setProperty(property, value);
          }
        });
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
  const isBlankParagraph = paragraph => paragraph.textContent.replace(/[\s ​]/gu, '') === '';

  function updateEditorSpacing() {
    editor.querySelectorAll('p').forEach(paragraph => {
      paragraph.classList.toggle('reading-gap', isBlankParagraph(paragraph));
    });
  }

  editor.innerHTML = cleanHtml(source.value);
  updateEditorSpacing();
  editor.addEventListener('input', updateEditorSpacing);

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
    fontPreview.innerHTML = cleanHtml(editor.innerHTML);
    fontPreview.querySelectorAll('p').forEach(paragraph => {
      if (isBlankParagraph(paragraph)) {
        paragraph.replaceChildren();
        paragraph.className = 'reading-gap';
        paragraph.removeAttribute('style');
        paragraph.setAttribute('aria-hidden', 'true');
      }
    });
    document.querySelector('#preview').hidden = false;
    document.querySelector('#preview').scrollIntoView({ block: 'start' });
  });

  if (fileInput) {
    // L’original DOCX reste temporaire ; seul le contenu filtré rejoint l’éditeur.
    const importButton = document.querySelector('#importer-document');
    const importNotice = document.querySelector('#import-success');
    let importNoticeTimer;

    async function importDocument(file) {
      if (importButton.disabled) return;
      if (!file || !file.name.toLowerCase().endsWith('.docx') || file.size > 15 * 1024 * 1024) {
        window.clearTimeout(importNoticeTimer);
        importNotice.hidden = true;
        status.classList.add('import-problem');
        status.setAttribute('role', 'alert');
        status.textContent = file?.size > 15 * 1024 * 1024
          ? 'Le document Word est trop lourd. La limite est de 15 Mo. Réduisez la taille des images dans Word avant de réessayer.'
          : 'Format refusé. Sélectionnez un document Word .docx. Les fichiers .doc et PDF ne sont pas acceptés.';
        status.scrollIntoView({ block: 'center' });
        return;
      }
      const constraint = form.querySelector('#consigne');
      const imageInput = form.querySelector('#image');
      if ((editor.textContent.trim() || titleInput.value.trim() || constraint?.value.trim() || imageInput.files.length)
        && !window.confirm('Importer le document Word et remplacer les champs qu’il renseigne ?')) {
        return;
      }
      window.clearTimeout(importNoticeTimer);
      importNotice.hidden = true;
      status.classList.remove('import-problem');
      status.setAttribute('role', 'status');
      importButton.disabled = true;
      status.textContent = `Lecture de « ${file.name} »…`;
      const data = new FormData();
      data.append('document', file);
      data.append('categorie', form.elements.categorie.value);
      data.append('csrf', form.elements.csrf.value);
      try {
        const response = await fetch(form.dataset.importUrl, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-CSRF-Token': form.elements.csrf.value } });
        if (response.status === 413) {
          throw new Error('Le serveur refuse ce fichier car il est trop lourd. Réduisez sa taille ou faites vérifier la limite de dépôt de l’hébergement.');
        }
        if (response.status === 401 || response.status === 403) {
          throw new Error('Votre session a expiré. Enregistrez le texte déjà saisi puis reconnectez-vous.');
        }
        if (!response.headers.get('Content-Type')?.includes('application/json')) {
          throw new Error('La session a expiré ou le fichier est trop volumineux. Enregistrez votre texte puis reconnectez-vous.');
        }
        const result = await response.json();
        if (!response.ok) {
          throw new Error(result.erreur || 'Le document n’a pas pu être lu.');
        }
        editor.innerHTML = cleanHtml(result.html);
        updateEditorSpacing();
        if (fontField) fontField.value = 'defaut';
        updateFont();
        if (result.titre) titleInput.value = result.titre;
        if (constraint && result.contrainte) constraint.value = result.contrainte;
        if (result.image) {
          try {
            const bytes = Uint8Array.from(atob(result.image.base64), char => char.charCodeAt(0));
            const transfer = new DataTransfer();
            transfer.items.add(new File([bytes], result.image.nom, { type: result.image.mime }));
            imageInput.files = transfer.files;
            const removeImage = form.querySelector('[name="retirer_image"]');
            if (removeImage) removeImage.checked = false;
            form.querySelector('#image-description').value = '';
            form.querySelector('#image-credit').value = '';
            imageInput.dispatchEvent(new Event('change'));
          } catch {
            result.avertissements.push('Ajoutez l’image manuellement dans ce navigateur.');
          }
        }
        dirty = true;
        savedRange = null;
        importNotice.hidden = result.avertissements.length > 0;
        if (result.avertissements.length > 0) {
          status.classList.add('import-problem');
          status.setAttribute('role', 'alert');
          status.scrollIntoView({ block: 'center' });
        }
        importNoticeTimer = window.setTimeout(() => {
          importNotice.hidden = true;
        }, 6500);
        status.textContent = `« ${file.name} » importé. Vérifiez les champs avant d’enregistrer. ${result.avertissements.join(' ')}`;
      } catch (error) {
        status.classList.add('import-problem');
        status.setAttribute('role', 'alert');
        status.textContent = error.message;
        status.scrollIntoView({ block: 'center' });
      } finally {
        importButton.disabled = false;
      }
    }
    importButton.addEventListener('click', () => importDocument(fileInput.files[0]));
    const dropZone = document.querySelector('#docx-drop');
    // Autoriser le dépôt sur toute la zone Word, y compris par-dessus le sélecteur de fichier.
    dropZone.addEventListener('dragover', event => {
      event.preventDefault();
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = importButton.disabled ? 'none' : 'copy';
      }
      dropZone.classList.toggle('is-dragging', !importButton.disabled);
    }, true);
    dropZone.addEventListener('dragleave', event => {
      if (!(event.relatedTarget instanceof Node) || !dropZone.contains(event.relatedTarget)) {
        dropZone.classList.remove('is-dragging');
      }
    });
    dropZone.addEventListener('drop', event => {
      event.preventDefault();
      dropZone.classList.remove('is-dragging');
      if (importButton.disabled) return;

      const files = event.dataTransfer?.files;
      if (!files?.length) {
        status.textContent = 'Aucun fichier reçu. Déposez un DOCX depuis votre explorateur de fichiers ou sélectionnez-le avec le bouton.';
        return;
      }
      if (files.length !== 1) {
        status.textContent = 'Déposez un seul fichier Word à la fois.';
        return;
      }
      importDocument(files[0]);
    }, true);
  }

  window.addEventListener('beforeunload', event => {
    if (dirty) {
      event.preventDefault();
      event.returnValue = '';
    }
  });
}
