/*
 * Métabox de localisation — pin déplaçable dans l'éditeur d'article.
 * Glisser le pin ou cliquer sur la carte met à jour les champs Lat/Lon.
 */
(function () {
  if (typeof L === 'undefined') return;

  var mapEl = document.getElementById('lc-admin-map');
  var latI  = document.getElementById('lc-lat');
  var lonI  = document.getElementById('lc-lon');
  var clear = document.getElementById('lc-clear');
  if (!mapEl || !latI || !lonI) return;

  // Même pin que côté public (mise en forme dans css/pin.css)
  function pin() {
    return L.divIcon({
      className: 'lc-pin',
      html: '<span class="lc-pin-shape"><span class="lc-pin-bg"></span></span>',
      iconSize: [0, 0],
      iconAnchor: [0, 0]
    });
  }

  var hasLat = latI.value !== '' && !isNaN(parseFloat(latI.value));
  var hasLon = lonI.value !== '' && !isNaN(parseFloat(lonI.value));
  var start = (hasLat && hasLon) ? [parseFloat(latI.value), parseFloat(lonI.value)] : [46.6, 2.2];
  var zoom  = (hasLat && hasLon) ? 11 : 4;

  var map = L.map(mapEl).setView(start, zoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 18
  }).addTo(map);

  var marker = null;
  function place(lat, lon) {
    if (!marker) {
      marker = L.marker([lat, lon], { draggable: true, icon: pin() }).addTo(map);
      marker.on('dragend', function () {
        var p = marker.getLatLng();
        set(p.lat, p.lng);
      });
    } else {
      marker.setLatLng([lat, lon]);
    }
  }
  function set(lat, lon) {
    latI.value = lat.toFixed(6);
    lonI.value = lon.toFixed(6);
    coord();
  }

  /* --------------------------------------------------------------
   * La carte en grand
   * ------------------------------------------------------------ */
  var wrap    = document.getElementById('lc-map-wrap');
  var ouvrir  = document.getElementById('lc-plein');
  var fermer  = document.getElementById('lc-plein-fermer');
  var lecture = document.getElementById('lc-map-coord');

  function coord() {
    if (!lecture) return;
    lecture.textContent = (latI.value && lonI.value)
      ? 'Point : ' + latI.value + ', ' + lonI.value
      : 'Cliquez sur la carte pour poser le point.';
  }

  // L'éditeur de blocs empile ses panneaux dans un contexte à lui : un
  // élément en position fixe laissé dans la métabox passe SOUS la colonne
  // de droite. On sort donc la carte du document le temps de l'agrandir,
  // et on la remet exactement où elle était — la carte Leaflet, elle, ne
  // bouge pas : ni recréée, ni rechargée, le point posé est conservé.
  var ancre = null;

  function plein(on) {
    if (!wrap) return;

    if (on && !ancre) {
      ancre = document.createComment('carte du croquis');
      wrap.parentNode.insertBefore(ancre, wrap);
      document.body.appendChild(wrap);
    } else if (!on && ancre) {
      ancre.parentNode.insertBefore(wrap, ancre);
      ancre.parentNode.removeChild(ancre);
      ancre = null;
    }

    wrap.classList.toggle('est-plein', on);
    document.body.classList.toggle('lc-carte-plein', on);
    if (ouvrir) ouvrir.setAttribute('aria-expanded', on ? 'true' : 'false');

    // Le conteneur a changé de taille : Leaflet ne s'en aperçoit pas seul.
    // Le centre est relevé AVANT pour être remis après, sinon l'agrandissement
    // décale la vue vers le coin haut-gauche.
    var centre = map.getCenter();
    map.invalidateSize();
    map.setView(centre, map.getZoom(), { animate: false });

    if (on) {
      // En grand, on voit assez pour viser : on se rapproche d'un cran si
      // la vue est encore large, mais jamais au point de perdre le repère.
      if (marker && map.getZoom() < 15) map.setView(marker.getLatLng(), 15, { animate: false });
      if (fermer) fermer.focus();
    } else if (ouvrir) {
      ouvrir.focus();
    }
  }

  ouvrir && ouvrir.addEventListener('click', function () { plein(true); });
  fermer && fermer.addEventListener('click', function () { plein(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && wrap && wrap.classList.contains('est-plein')) plein(false);
  });

  if (hasLat && hasLon) { place(start[0], start[1]); }

  map.on('click', function (e) {
    place(e.latlng.lat, e.latlng.lng);
    set(e.latlng.lat, e.latlng.lng);
  });

  clear && clear.addEventListener('click', function () {
    if (marker) { map.removeLayer(marker); marker = null; }
    latI.value = ''; lonI.value = '';
    coord();
  });

  // Saisie manuelle dans les champs : la carte suit (coordonnées collées
  // depuis une autre source, correction à la main…)
  function fromFields() {
    var la = parseFloat(String(latI.value).replace(',', '.'));
    var lo = parseFloat(String(lonI.value).replace(',', '.'));
    if (isNaN(la) || isNaN(lo)) return;
    if (la < -90 || la > 90 || lo < -180 || lo > 180) return;
    place(la, lo);
    map.setView([la, lo], Math.max(map.getZoom(), 9));
  }
  latI.addEventListener('change', fromFields);
  lonI.addEventListener('change', fromFields);

  coord();

  // La carte peut s'initialiser masquée (métabox repliée) : on rafraîchit
  setTimeout(function () { map.invalidateSize(); }, 300);
})();
