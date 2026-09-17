/*
 * Cartes publiques — Leaflet, fond OpenStreetMap.
 * Lit la donnée injectée dans <script class="carte-data" data-for="ID">.
 * Un point = [lat, lon, url, titre, lieu].
 *
 * Deux croquis sur la même coordonnée ne font qu'UN pin : leurs étiquettes
 * se recouvraient et seul le dernier posé était cliquable. Le pin ouvert
 * les liste tous. Pendant qu'une étiquette est ouverte, les autres pins
 * s'effacent : l'étiquette est large, elle passait par-dessus les voisins.
 */
(function () {
  if (typeof L === 'undefined') return;

  var TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  var ATTR  = '© OpenStreetMap';

  /* --------------------------------------------------------------
   * Pin : cercle à pointe basse. Mise en forme dans css/pin.css ;
   * ici on ne produit que la structure. iconAnchor = [0,0] : l'origine
   * du wrapper est la pointe, donc le point exact de la coordonnée.
   * Les textes sont laissés VIDES : ils sont posés via textContent, pour
   * qu'un titre ou un lieu contenant « < » ou « & » ne puisse jamais
   * être interprété comme du balisage.
   * ------------------------------------------------------------ */
  function pinHtml(nbEntrees) {
    var h = '<span class="lc-pin-shape"><span class="lc-pin-bg"></span>';
    if (nbEntrees) {
      h += '<span class="lc-pin-label">';
      if (nbEntrees > 1) h += '<span class="lc-pin-n"></span>';
      for (var i = 0; i < nbEntrees; i++) {
        h += '<span class="lc-pin-item">' +
             '<span class="lc-pin-titre"></span>' +
             '<span class="lc-pin-lieu"></span>' +
             '</span>';
      }
      h += '</span>';
    }
    // Le compte reste lisible pin fermé : il se dessine dans la pastille.
    if (nbEntrees > 1) h += '<span class="lc-pin-compte"></span>';
    return h + '</span>';
  }

  function pin(nbEntrees) {
    return L.divIcon({
      className: 'lc-pin' + (nbEntrees > 1 ? ' lc-pin--multi' : ''),
      html: pinHtml(nbEntrees),
      iconSize: [0, 0],
      iconAnchor: [0, 0]
    });
  }

  // Un seul pin ouvert à la fois, toutes cartes confondues
  var openMarker = null;
  function closeOpen() {
    if (!openMarker) return;
    if (openMarker._icon) {
      openMarker._icon.classList.remove('is-open');
      openMarker._icon.querySelectorAll('.lc-pin-titre a').forEach(function (a) {
        a.setAttribute('tabindex', '-1');
      });
    }
    openMarker.setZIndexOffset(0);
    if (openMarker._lcMapEl) openMarker._lcMapEl.classList.remove('lc-a-un-pin-ouvert');
    openMarker = null;
  }

  /**
   * @param marker  le marqueur Leaflet
   * @param groupe  les points partageant la coordonnée : [[lat,lon,url,titre,lieu], …]
   */
  function makeExpandable(marker, groupe) {
    var n = groupe.length;

    marker.on('add', function () {
      var el = marker._icon;
      if (!el) return;
      marker._lcMapEl = marker._map ? marker._map.getContainer() : null;

      el.setAttribute('role', 'button');
      el.setAttribute('tabindex', '0');
      el.setAttribute('aria-label', n > 1
        ? n + ' croquis à cet endroit'
        : (groupe[0][4] ? (groupe[0][3] + ' — ' + groupe[0][4]) : (groupe[0][3] || 'Voir le croquis')));

      var cpt = el.querySelector('.lc-pin-compte');
      if (cpt) cpt.textContent = n;
      var nEl = el.querySelector('.lc-pin-n');
      if (nEl) nEl.textContent = n + ' croquis ici';

      var items = el.querySelectorAll('.lc-pin-item');
      groupe.forEach(function (p, i) {
        var item = items[i];
        if (!item) return;
        var titre = p[3] || 'Voir le croquis';
        var lieu  = p[4] || '';

        var tEl = item.querySelector('.lc-pin-titre');
        if (tEl) {
          var a = document.createElement('a');
          a.href = p[2];
          a.textContent = titre;
          a.setAttribute('tabindex', '-1');   // atteignable seulement pin ouvert
          tEl.textContent = '';
          tEl.appendChild(a);
        }
        var lEl = item.querySelector('.lc-pin-lieu');
        if (lEl) {
          lEl.textContent = lieu;
          // Pas de ligne vide quand le lieu manque
          if (!lieu) lEl.hidden = true;
        }
      });
    });

    marker.on('click', function (e) {
      var el = marker._icon;
      if (!el) return;
      // Clic sur un lien déjà révélé : on laisse la navigation se faire
      var t = e.originalEvent && e.originalEvent.target;
      if (t && t.closest && t.closest('.lc-pin-titre a') && el.classList.contains('is-open')) {
        return;
      }
      if (e.originalEvent) L.DomEvent.preventDefault(e.originalEvent);

      var wasOpen = el.classList.contains('is-open');
      closeOpen();
      if (!wasOpen) {
        el.classList.add('is-open');
        marker.setZIndexOffset(1000);
        marker._lcMapEl = marker._map ? marker._map.getContainer() : null;
        // Les voisins s'effacent : l'étiquette ouverte leur passe dessus.
        if (marker._lcMapEl) marker._lcMapEl.classList.add('lc-a-un-pin-ouvert');
        openMarker = marker;
        el.querySelectorAll('.lc-pin-titre a').forEach(function (a) {
          a.setAttribute('tabindex', '0');
        });
      }
    });

    marker.on('keydown', function (e) {
      var ev = e.originalEvent;
      var k = ev && ev.key;
      if (k === 'Enter' || k === ' ') marker.fire('click', { originalEvent: ev });
    });

    marker.on('remove', function () {
      if (openMarker === marker) closeOpen();
    });

    return marker;
  }

  /* --------------------------------------------------------------
   * Regrouper les points qui partagent exactement la même coordonnée.
   * Le géocodage est souvent au lieu ou au carnet : deux tiers des
   * croquis tombent ainsi sur le point d'un autre.
   * ------------------------------------------------------------ */
  function grouper(pts) {
    var index = {}, groupes = [];
    pts.forEach(function (p) {
      var k = Number(p[0]).toFixed(5) + ',' + Number(p[1]).toFixed(5);
      if (!index[k]) { index[k] = []; groupes.push(index[k]); }
      index[k].push(p);
    });
    return groupes;
  }

  function marker(groupe) {
    var m = L.marker([groupe[0][0], groupe[0][1]], { icon: pin(groupe.length) });
    m.lcCount = groupe.length;
    return makeExpandable(m, groupe);
  }

  /* --------------------------------------------------------------
   * Icône de regroupement — on remplace celle de markercluster
   * (cercles vert / jaune / orange) par la pastille ocre du thème.
   * Le nombre affiché est celui des CROQUIS, pas des marqueurs : un
   * marqueur peut en porter plusieurs.
   * ------------------------------------------------------------ */
  function clusterIcon(c) {
    var n = 0;
    c.getAllChildMarkers().forEach(function (m) { n += (m.lcCount || 1); });
    var t = n < 10 ? 'p' : (n < 100 ? 'm' : 'g');
    var el = L.divIcon({
      className: 'lc-cluster lc-cluster--' + t,
      html: '<span class="lc-cluster-bg"></span><span class="lc-cluster-n"></span>',
      iconSize: [0, 0],
      iconAnchor: [0, 0]
    });
    // createIcon est appelé par Leaflet : on y pose le nombre en textContent
    var base = el.createIcon;
    el.createIcon = function () {
      var d = base.apply(this, arguments);
      var s = d.querySelector('.lc-cluster-n');
      if (s) s.textContent = n;
      d.setAttribute('aria-label', n + ' croquis regroupés');
      return d;
    };
    return el;
  }

  function initOne(el) {
    var id = el.id;
    var holder = document.querySelector('.carte-data[data-for="' + id + '"]');
    if (!holder) return;
    var data;
    try { data = JSON.parse(holder.textContent); } catch (e) { return; }
    var pts = data.points || [];

    var map = L.map(el, { scrollWheelZoom: false, attributionControl: true });
    L.tileLayer(TILES, { attribution: ATTR, maxZoom: 18 }).addTo(map);

    if (!pts.length && data.center) {
      map.setView([data.center[0], data.center[1]], data.zoom || 5);
      return;
    }

    var groupes = grouper(pts);

    // Le regroupement suit le drapeau posé par PHP (nombre de points),
    // plus le mode de carte : un carnet dense en a autant besoin.
    if (data.cluster && L.markerClusterGroup) {
      var cluster = L.markerClusterGroup({
        maxClusterRadius: 45,
        showCoverageOnHover: false,
        // Les coordonnées identiques sont déjà réunies en un seul pin ;
        // reste l'éventail pour des points très proches mais distincts.
        spiderfyOnMaxZoom: true,
        iconCreateFunction: clusterIcon
      });
      groupes.forEach(function (g) { cluster.addLayer(marker(g)); });
      map.addLayer(cluster);
      map.fitBounds(cluster.getBounds().pad(0.15));
      cluster.on('clusterclick', closeOpen);
    } else {
      var group = [];
      groupes.forEach(function (g) { group.push(marker(g).addTo(map)); });
      if (groupes.length === 1) {
        map.setView([groupes[0][0][0], groupes[0][0][1]], data.zoom || 12);
      } else if (group.length) {
        map.fitBounds(L.featureGroup(group).getBounds().pad(0.2));
      }
    }

    // Un clic sur le fond referme le pin ouvert
    map.on('click', closeOpen);

    // Le zoom molette ne s'active qu'après un clic (n'accroche pas le scroll)
    map.on('focus', function () { map.scrollWheelZoom.enable(); });
    map.on('blur',  function () { map.scrollWheelZoom.disable(); });
  }

  document.querySelectorAll('.carte').forEach(initOne);

  /* --------------------------------------------------------------
   * Carte de lecture (feuilletage) : une seule carte, recentrée à
   * chaque croquis. Exposée via window.LCReaderMap pour feuilletage.js.
   * ------------------------------------------------------------ */
  (function () {
    var el = document.getElementById('reader-map');
    if (!el) { window.LCReaderMap = null; return; }

    var map = null, mk = null, last = null;

    function ensure() {
      if (map) return;
      map = L.map(el, {
        zoomControl: false, attributionControl: false,
        dragging: false, scrollWheelZoom: false, doubleClickZoom: false,
        boxZoom: false, keyboard: false, tap: false,
      });
      L.tileLayer(TILES, { attribution: ATTR, maxZoom: 16 }).addTo(map);
    }

    // `offsetParent` vaut TOUJOURS null sur un élément position:fixed (spec
    // CSSOM View) : s'en servir comme test de visibilité empêchait la carte
    // de s'initialiser. getClientRects() distingue display:none du reste.
    function hidden() {
      return el.getClientRects().length === 0;
    }

    function render() {
      if (!last || hidden()) return;
      ensure();
      map.invalidateSize();
      map.setView(last, 9);
      if (!mk) { mk = L.marker(last, { icon: pin(0) }).addTo(map); }
      else { mk.setLatLng(last); }
    }

    window.LCReaderMap = {
      show: function (lat, lon) { last = [lat, lon]; el.classList.add('on'); render(); },
      hide: function () { el.classList.remove('on'); },
      refresh: render
    };
  })();
})();
