/*
 * La loupe de l'en-tête déplie son champ.
 *
 * Premier clic : on ouvre et on donne le focus, sans envoyer le formulaire —
 * il n'y aurait rien à envoyer. Ensuite le bouton redevient ce qu'il est,
 * un bouton d'envoi. Échap referme, et un clic ailleurs aussi, mais
 * seulement si le champ est vide : on ne fait pas disparaître une recherche
 * en cours de frappe.
 */
(function () {
  var form = document.querySelector('.head-search');
  if (!form) return;

  var champ  = form.querySelector('input[type=search]');
  var bouton = form.querySelector('.hs-btn');
  if (!champ || !bouton) return;

  function ouvrir() {
    form.classList.add('is-open');
    bouton.setAttribute('aria-expanded', 'true');
    champ.focus();
  }

  function fermer() {
    if (!form.classList.contains('is-open')) return;
    form.classList.remove('is-open');
    bouton.setAttribute('aria-expanded', 'false');
  }

  bouton.addEventListener('click', function (e) {
    if (!form.classList.contains('is-open')) {
      e.preventDefault();
      ouvrir();
    } else if (champ.value.trim() === '') {
      // Loupe cliquée sur un champ vide : on referme plutôt que d'envoyer
      // une recherche sans mot.
      e.preventDefault();
      fermer();
    }
  });

  form.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { fermer(); bouton.focus(); }
  });

  document.addEventListener('click', function (e) {
    if (form.contains(e.target)) return;
    if (champ.value.trim() === '') fermer();
  });

  // Le champ quitté vide par tabulation se referme aussi
  champ.addEventListener('blur', function () {
    setTimeout(function () {
      if (form.contains(document.activeElement)) return;
      if (champ.value.trim() === '') fermer();
    }, 120);
  });
})();
