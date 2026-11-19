// Affiche les aides selon la catégorie. Le serveur valide et envoie le message.
const contactForm = document.querySelector('#contact-form');

if (contactForm) {
  const category = contactForm.elements.type_demande;
  const message = contactForm.elements.message;
  const consent = contactForm.elements.autorisation_publication;
  const wordCount = document.querySelector('#contact-word-count');

  function updateRequest() {
    const isNoel = category.value === 'noel';
    const isProposal = isNoel || category.value === 'microfiction';
    const count = (message.value.match(/[\p{L}\p{N}]+(?:[’'-][\p{L}\p{N}]+)*/gu) || []).length;

    consent.closest('label').hidden = !isProposal;
    consent.disabled = !isProposal;
    consent.required = isProposal;
    if (wordCount) {
      wordCount.textContent = count + ' / 350 mots';
    }
    message.setCustomValidity(isNoel && count > 350 ? 'Votre histoire de Noël doit contenir au maximum 350 mots.' : '');
  }

  category.addEventListener('change', () => {
    consent.checked = false;
    updateRequest();
  });
  message.addEventListener('input', updateRequest);
  updateRequest();
}