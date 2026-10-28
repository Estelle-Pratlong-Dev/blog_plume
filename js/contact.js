// Prépare un message local : aucun appel réseau ni enregistrement des champs.
const contactForm = document.querySelector('#contact-form');
const contactSubmit = document.querySelector('#contact-submit');
const contactStatus = document.querySelector('#contact-status');

if (contactForm && contactSubmit && contactStatus) {
  const recipient = contactForm.dataset.recipient;
  contactSubmit.disabled = !recipient;
  if (!recipient) {
    contactStatus.textContent = 'Les coordonnées de contact seront disponibles prochainement.';
  }

  contactForm.addEventListener('submit', event => {
    event.preventDefault();
    if (!recipient || !contactForm.reportValidity()) {
      return;
    }
    const name = contactForm.elements.nom.value.trim();
    const email = contactForm.elements.email.value.trim();
    const subject = contactForm.elements.objet.value.trim();
    const message = contactForm.elements.message.value.trim();
    if (!name || !subject || !message) {
      contactStatus.textContent = 'Complétez votre nom, l’objet et le message avant de continuer.';
      return;
    }
    const category = contactForm.elements.type_demande.selectedOptions[0].textContent.trim();
    const body = [`Catégorie : ${category}`, '', message, '', '—', `Nom : ${name}`, `E-mail : ${email}`].join('\r\n');
    const destination = encodeURIComponent(recipient).replace(/%40/g, '@');
    const mailto = `mailto:${destination}?subject=${encodeURIComponent('[' + category + '] ' + contactForm.dataset.siteName + ' — ' + subject)}&body=${encodeURIComponent(body)}`;
    contactStatus.textContent = 'Le message est préparé pour votre messagerie. Vérifiez-le puis envoyez-le depuis celle-ci. Si elle ne s’ouvre pas, utilisez le lien e-mail indiqué sur cette page.';
    window.location.href = mailto;
  });
}
