# Les Carnets — atelier d'édition

Le poste de travail éditorial de la collection **Les Carnets** : treize volumes de livres
tirés du fonds de croquis de Jérôme Agostini — **1 347 croquis, 51 carnets, 2002-2019**,
publiés sur [lescarnets.fr](https://lescarnets.fr).

Ce dépôt ne contient pas les images. Il contient le fonds sous forme de données, le
découpage de la collection, les sommaires arrêtés, et deux outils pour travailler dessus.

---

## Le site de la collection

```
site/index.html              La collection : les 13 volumes, le chemin de fer, les transversales
site/volumes/<slug>.html     Une page par volume (14 pages, réserve comprise)
```

Pages statiques, sans dépendance : ouvrez `site/index.html` directement dans un navigateur.
Elles sont **générées**, jamais écrites à la main — voir « Régénérer le site » plus bas.

Chaque page de volume donne le gisement réel (carnets, croquis, part muette, densité de
texte, années, lieux les plus dessinés), la sélection retenue, l'estimation de pages, et
pour les volumes arrêtés l'angle et la structure en sections.

## L'index des croquis

```
index.html                   Outil de consultation du fonds : recherche, timeline, carte, édition
```

Application d'une page qui charge `corpus_complet.json` : recherche plein texte, frise
mensuelle, filtres par thème, carnet et précision de géocodage, carte Leaflet, correction
de position, ajout de croquis, export/import des modifications en JSON. Protégée par un
mot de passe.

⚠️ Cet outil **doit être servi en HTTP**, pas ouvert par double-clic : il charge le corpus
par `fetch`, que le protocole `file://` bloque.

```bash
python3 -m http.server 8000   # puis http://localhost:8000/index.html
```

---

## Les données

| Fichier | Rôle |
|---|---|
| `corpus_complet.json` | **La source de vérité du fonds.** 1 347 entrées, une par croquis. |
| `volumes.json` | Le découpage éditorial : quels carnets font quel volume, sélection, angles, notes. |
| `collection-complete.md` | Le chiffrage de la collection en prose, volume par volume. |
| `rome-sommaire.md` | Sommaire arrêté du volume 2 — sélection, sections, textes d'ouverture et de clôture. |
| `haiti-sommaire.md` | Sommaire arrêté du volume 3 — même chose, en régime mixte. |

### Un croquis dans `corpus_complet.json`

```json
{
  "i": 0,                         // index
  "t": "Le San Petrone",          // titre
  "d": "2002-08-09", "y": 2002,   // date, année
  "c": "Corsica",                 // carnet (une destination = un carnet)
  "r": "Europe",                  // région
  "x": "",                        // texte d'accompagnement — vide = croquis « muet »
  "l": "Aïti",                    // lieu
  "f": "01_090802_SanPetrone.jpg",// fichier source
  "mob": false,                   // dessiné en mouvement
  "lat": 42.383, "lon": 9.283,    // position
  "p": "lieu",                    // précision du géocodage : lieu, ville, carnet…
  "h": [],                        // thèmes
  "url": "https://lescarnets.fr/2002/08/09/le-san-petrone/"
}
```

### Le découpage en volumes

Les 51 carnets sont répartis entre 13 volumes géographiques et une réserve, **sans
recouvrement et sans reste** : le générateur échoue si la somme ne fait pas exactement
1 347. Deux ajustements de classement sont portés par les données, pas par des chiffres
recopiés :

- `exclure_fichiers` / `inclure_fichiers` déplacent les **4 croquis mahorais de 2010**
  mal classés dans le carnet `Roma` vers le volume Océan Indien. C'est ce qui ramène Rome
  de 224 à 220 croquis. *À corriger aussi sur lescarnets.fr.*
- Le carnet `Métro Parisien` (4 croquis) est rattaché au volume **Paris**, alors que
  `collection-complete.md` le comptait dans *France hors Paris* — d'où 92 / 129 ici au
  lieu de 88 / 133.

---

## Régénérer le site

```bash
python3 tools/build_site.py
```

Le script lit `volumes.json` et `corpus_complet.json` et réécrit tout `site/`.
La séparation est stricte :

- **tous les chiffres du fonds** (croquis, muets, densité, années, lieux, bornes de dates)
  sont **recalculés** à chaque build depuis le corpus ;
- **seuls les choix éditoriaux** (croquis retenus, pages estimées, angle, sections, statut,
  notes) viennent de `volumes.json`.

Conséquence pratique : corriger le corpus suffit, les pages suivent. Et un chiffre affiché
sur le site ne peut pas diverger du fonds — il en sort.

Modifier un volume, c'est donc éditer `volumes.json` puis relancer le script ; n'éditez
jamais un fichier de `site/` à la main, il sera écrasé.

---

## Méthode de chiffrage

Pages estimées = croquis retenus + 8 pages liminaires et de fin, arrondi au multiple de 4.
Règle appliquée uniformément aux 13 volumes pour qu'ils soient comparables — pas une
promesse de maquette. Rome et Haïti, dont les sommaires sont arrêtés, portent en plus leur
fourchette réelle (88-96 p. et 88 p.), plus large parce qu'elle anticipe des doubles pages
sur les textes longs.

## Où en est la collection

| | |
|---|---|
| Sommaires arrêtés | Soudan (pilote), Rome, Haïti |
| À faire | les 10 autres volumes |
| Réserve | Éthiopie & Ouganda — 22 croquis, trop mince pour tenir seul |
| Hors compte | les transversales (café, eau, transport, portraits, fenêtres) et le best of de clôture |

Les croquis non retenus ne disparaissent pas : ils restent sur lescarnets.fr, qui est le
lieu patrimonial complet. Les livres en sont une sélection, pas un remplacement.
