/*
 * Feuilletage — défilement natif (scroll-snap).
 * Le navigateur gère l'accroche et l'animation ; le JS ne fait qu'observer
 * quel croquis est au centre pour mettre à jour l'URL, la progression,
 * le compteur et la carte de lecture.
 */
(function () {
  var reader = document.querySelector('.reader');
  if (!reader) return;

  var rail   = document.getElementById('reader-rail');
  var countE = document.getElementById('reader-count');
  var hint   = document.getElementById('reader-hint');
  var plates = Array.prototype.slice.call(reader.querySelectorAll('.plate'));
  var N = plates.length;
  if (!N) return;

  var start = parseInt(reader.getAttribute('data-start'), 10) || 0;
  var cur = -1, hintHidden = false;

  function goTo(i) {
    i = Math.max(0, Math.min(N - 1, i));
    plates[i].scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  /* Barre de progression, adaptative.
   * Un point par croquis tant qu'ils tiennent dans le rail (max-height:70dvh).
   * Au-delà, les points débordaient en overflow:hidden : sur un carnet de 224
   * croquis, 2905px de points étaient tronqués à 703px, le point actif et la
   * fin du carnet devenaient invisibles, et le rail laissait croire à un
   * carnet de ~54 croquis. On bascule alors sur un filet continu à curseur,
   * qui reste juste quel que soit le nombre de planches. */
  var DOT_STRIDE = 13;   // 6px de point + 7px de gouttière (cf. style.css)
  var railRoom   = reader.clientHeight * 0.7;
  var useDots    = (N * DOT_STRIDE) <= railRoom;
  var dots = [], track = null, thumb = null;

  if (useDots) {
    plates.forEach(function (p, i) {
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', 'Croquis ' + (i + 1) + ' sur ' + N);
      b.addEventListener('click', function () { goTo(i); });
      rail.appendChild(b);
    });
    dots = Array.prototype.slice.call(rail.children);
  } else {
    rail.classList.add('is-bar');
    track = document.createElement('div');
    track.className = 'rail-track';
    track.setAttribute('role', 'slider');
    track.setAttribute('tabindex', '0');
    track.setAttribute('aria-label', 'Progression dans le carnet');
    track.setAttribute('aria-valuemin', '1');
    track.setAttribute('aria-valuemax', String(N));
    thumb = document.createElement('div');
    thumb.className = 'rail-thumb';
    track.appendChild(thumb);
    rail.appendChild(track);
    // Cliquer n'importe où sur le filet saute à la position correspondante
    track.addEventListener('click', function (e) {
      var r = track.getBoundingClientRect();
      if (!r.height) return;
      var ratio = (e.clientY - r.top) / r.height;
      goTo(Math.round(ratio * (N - 1)));
    });
  }

  function setCurrent(i) {
    if (i === cur) return;
    cur = i;
    if (dots.length) {
      dots.forEach(function (d, k) { d.classList.toggle('on', k === i); });
    } else if (track) {
      var r = track.getBoundingClientRect();
      var h = Math.max(24, Math.round(r.height / N));
      thumb.style.height = h + 'px';
      thumb.style.top = Math.round((i / Math.max(1, N - 1)) * (r.height - h)) + 'px';
      track.setAttribute('aria-valuenow', String(i + 1));
      track.setAttribute('aria-valuetext', 'Croquis ' + (i + 1) + ' sur ' + N);
    }
    countE.textContent = (i + 1) + ' / ' + N;
    var pl = plates[i];
    var url = pl.getAttribute('data-url');
    var ttl = pl.getAttribute('data-title');
    if (url && window.history && history.replaceState) {
      history.replaceState(null, '', url);
      // Nom du site fourni par PHP (pas de chaîne codée en dur)
      var site = (window.LC_READER && window.LC_READER.site) || document.title;
      if (ttl) { document.title = ttl + ' — ' + site; }
    }
    if (window.LCReaderMap) {
      var lat = pl.getAttribute('data-lat');
      var lon = pl.getAttribute('data-lon');
      if (lat && lon) { window.LCReaderMap.show(parseFloat(lat), parseFloat(lon)); }
      else { window.LCReaderMap.hide(); }
    }
  }

  // Observe quelle planche occupe le centre du défileur
  var io = new IntersectionObserver(function (entries) {
    var best = null, bestRatio = 0;
    entries.forEach(function (e) {
      if (e.isIntersecting && e.intersectionRatio > bestRatio) {
        bestRatio = e.intersectionRatio; best = e.target;
      }
    });
    if (best) {
      var i = plates.indexOf(best);
      if (i >= 0) setCurrent(i);
    }
  }, { root: reader, threshold: [0.5, 0.75] });
  plates.forEach(function (p) { io.observe(p); });

  /* --------------------------------------------------------------
   * Légende longue : bornée à quelques lignes, dépliable à la demande.
   *
   * C'est la cause du défilement trop vif : une planche plus haute que
   * l'écran sort du cadre de l'accroche — la spec rend alors toute position
   * valide à l'intérieur — et le moindre geste emportait au croquis suivant.
   * En bornant la note, chaque planche tient dans l'écran et l'accroche
   * redevient franche. Une « résistance » au défilement aurait lutté contre
   * l'accroche native du navigateur (effet de latence) sans régler la cause.
   * ------------------------------------------------------------ */
  function setupCaptions(scope) {
    (scope || reader).querySelectorAll('.cap .x').forEach(function (x) {
      if (x.getAttribute('data-lc-cap')) return;
      x.setAttribute('data-lc-cap', '1');

      x.classList.add('is-clamped');
      // Rien à déplier : on retire le bornage plutôt que d'ajouter un bouton
      if (x.scrollHeight <= x.clientHeight + 2) {
        x.classList.remove('is-clamped');
        return;
      }

      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'cap-more';
      b.textContent = 'lire la suite';
      b.setAttribute('aria-expanded', 'false');
      var planche = x.closest('.plate');

      // Le dégradé de fin s'effface une fois la note lue jusqu'en bas
      function majFin() {
        var fin = x.scrollTop + x.clientHeight >= x.scrollHeight - 2;
        x.classList.toggle('at-end', fin);
      }
      x.addEventListener('scroll', majFin, { passive: true });

      b.addEventListener('click', function () {
        var ouvert = x.classList.toggle('is-expanded');
        x.classList.toggle('is-clamped', !ouvert);
        // La classe sur la PLANCHE laisse l'image se réduire pendant la
        // lecture : on gagne la place de la note sans barre de défilement.
        if (planche) planche.classList.toggle('is-note-open', ouvert);
        b.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        b.textContent = ouvert ? 'replier' : 'lire la suite';
        if (ouvert) { x.scrollTop = 0; majFin(); }
      });
      x.insertAdjacentElement('afterend', b);
    });
  }

  // Les métriques dépendent de la police : on mesure une fois qu'elle est là
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () { setupCaptions(); });
  } else {
    setupCaptions();
  }

  /* --------------------------------------------------------------
   * Hydratation progressive : les planches posées en « stub » par
   * single.php sont chargées en lot à l'approche du lecteur.
   * ------------------------------------------------------------ */
  (function () {
    var cfg   = window.LC_READER || {};
    var stubs = plates.filter(function (p) { return p.classList.contains('is-stub'); });
    if (!stubs.length) return;

    // Repli : une planche qu'on ne peut pas hydrater reste atteignable
    // par un lien classique plutôt que de rester un rond qui tourne.
    function degrade(els) {
      els.forEach(function (el) {
        var a = document.createElement('a');
        a.className = 'stub-fallback voice';
        a.href = el.getAttribute('data-url');
        a.textContent = el.getAttribute('data-title') || 'Voir ce croquis';
        el.textContent = '';
        el.appendChild(a);
        el.classList.remove('is-stub');
      });
    }

    if (!window.fetch || !cfg.ajax) { degrade(stubs); return; }

    var asked = {};

    function send(els) {
      var ids = els.map(function (el) { return el.getAttribute('data-id'); });
      var url = cfg.ajax + '?action=lescarnets_plates&ids=' + encodeURIComponent(ids.join(','));
      if (cfg.dest) url += '&dest=' + encodeURIComponent(cfg.dest);

      fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) {
          if (!j || !j.success || !j.data) throw new Error('réponse inattendue');
          var missing = [];
          els.forEach(function (el) {
            var html = j.data[el.getAttribute('data-id')];
            if (!html) { missing.push(el); return; }
            el.innerHTML = html;
            el.classList.remove('is-stub');
            setupCaptions(el);
          });
          if (missing.length) degrade(missing);
        })
        .catch(function () { degrade(els); });
    }

    var io2 = new IntersectionObserver(function (entries) {
      var batch = [];
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        var el = e.target, id = el.getAttribute('data-id');
        if (!id || asked[id]) return;
        asked[id] = true;
        batch.push(el);
        io2.unobserve(el);
      });
      // L'endpoint borne les lots à 12 : on découpe en deçà
      for (var i = 0; i < batch.length; i += 8) send(batch.slice(i, i + 8));
    }, { root: reader, rootMargin: '250% 0px' });

    stubs.forEach(function (p) { io2.observe(p); });
  })();

  // Masque le repère au premier défilement
  reader.addEventListener('scroll', function () {
    if (!hintHidden) { hint.classList.add('hide'); hintHidden = true; }
  }, { passive: true });

  // Clavier : flèches, page, début/fin
  window.addEventListener('keydown', function (e) {
    // On ne capture pas les raccourcis navigateur ni la frappe dans un champ
    if (e.metaKey || e.ctrlKey || e.altKey) return;
    var t = e.target;
    if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
    // Espace sur un bouton/lien focalisé = activation, pas défilement
    if (e.key === ' ' && t && /^(BUTTON|A)$/.test(t.tagName)) return;
    var go = null;
    if (e.key === 'ArrowDown' || e.key === 'PageDown' || e.key === ' ') go = Math.min(N - 1, cur + 1);
    else if (e.key === 'ArrowUp' || e.key === 'PageUp') go = Math.max(0, cur - 1);
    else if (e.key === 'Home') go = 0;
    else if (e.key === 'End') go = N - 1;
    if (go !== null) { e.preventDefault(); plates[go].scrollIntoView({ behavior: 'smooth', block: 'center' }); }
  });

  // Bouton « carte » (mobile) : ouvre/ferme le panneau et le rafraîchit
  var mapToggle = document.getElementById('reader-map-toggle');
  if (mapToggle) {
    mapToggle.addEventListener('click', function () {
      var open = reader.classList.toggle('map-open');
      mapToggle.setAttribute('aria-label', open ? 'Masquer la carte' : 'Afficher la carte');
      if (open && window.LCReaderMap) { setTimeout(function () { window.LCReaderMap.refresh(); }, 60); }
    });
  }

  // Position de départ (sans animation) sur le croquis demandé
  function jumpToStart() {
    var target = plates[start];
    var top = target.offsetTop - (reader.clientHeight - target.clientHeight) / 2;
    reader.scrollTop = Math.max(0, top);
    setCurrent(start);
  }
  // Attendre que la 1re image ait une taille pour un placement correct
  var firstImg = plates[start].querySelector('img');
  if (firstImg && !firstImg.complete) {
    firstImg.addEventListener('load', jumpToStart, { once: true });
    setTimeout(jumpToStart, 400); // filet de sécurité
  } else {
    jumpToStart();
  }
})();
