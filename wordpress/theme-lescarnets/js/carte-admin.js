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
  }

  if (hasLat && hasLon) { place(start[0], start[1]); }

  map.on('click', function (e) {
    place(e.latlng.lat, e.latlng.lng);
    set(e.latlng.lat, e.latlng.lng);
  });

  clear && clear.addEventListener('click', function () {
    if (marker) { map.removeLayer(marker); marker = null; }
    latI.value = ''; lonI.value = '';
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

  // La carte peut s'initialiser masquée (métabox repliée) : on rafraîchit
  setTimeout(function () { map.invalidateSize(); }, 300);
})();
