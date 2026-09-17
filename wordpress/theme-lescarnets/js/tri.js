/*
 * Tri interactif — réordonne les vignettes déjà affichées, sans recharger.
 * « date » = dernier croquis en tête (décroissant). « nom » = alphabétique.
 * Chaque barre de tri agit sur la grille qui la suit immédiatement.
 */
(function () {
  var bars = document.querySelectorAll('.sortbar');
  if (!bars.length) return;

  bars.forEach(function (bar) {
    var grid = bar.nextElementSibling;
    while (grid && !(grid.classList.contains('grid') || grid.classList.contains('croquis-grid'))) {
      grid = grid.nextElementSibling;
    }
    if (!grid) return;

    var items = Array.prototype.slice.call(grid.children);
    var buttons = bar.querySelectorAll('button');

    function sort(mode) {
      var arr = items.slice();
      arr.sort(function (a, b) {
        if (mode === 'name') {
          return (a.getAttribute('data-name') || '').localeCompare(
                 (b.getAttribute('data-name') || ''), 'fr', { sensitivity: 'base' });
        }
        // date décroissante
        return (parseInt(b.getAttribute('data-date') || '0', 10)) -
               (parseInt(a.getAttribute('data-date') || '0', 10));
      });
      arr.forEach(function (el) { grid.appendChild(el); });
    }

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        buttons.forEach(function (b) { b.classList.toggle('on', b === btn); });
        sort(btn.getAttribute('data-sort'));
      });
    });
  });
})();
