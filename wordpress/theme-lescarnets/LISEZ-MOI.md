# Les Carnets — thème WordPress

Thème classique sur mesure, **sans aucune dépendance externe** : Leaflet et
les polices sont embarqués en local. Il **conserve les URLs WordPress d'origine**
(`/category/carnets/.../soudan/`, `/AAAA/MM/JJ/slug/`) : aucune redirection,
référencement préservé. Aucune dépendance externe (Leaflet est embarqué en local).

## Contenu

- `front-page.php` — accueil : continents (ordre = dernier croquis le plus récent), destinations en vignettes triables
- `category.php` — carnet : en-tête + carte du carnet + grille de croquis triable
- `single.php` — feuilletage : un croquis par écran, carte de lecture qui suit
- `template-carte.php` — gabarit « Carte générale » (à assigner à une page)
- `404.php` · `searchform.php` — page introuvable et formulaire de recherche
- `js/feuilletage.js` — défilement contrôlé (un geste = une bascule), clavier, tactile, hydratation des planches
- `js/tri.js` — tri interactif date / nom
- `js/carte-public.js` · `js/carte-admin.js` — cartes publiques + pin déplaçable dans l'éditeur
- `css/pin.css` — le pin de carte et les pastilles de regroupement, partagés entre le site et l'éditeur
- `css/admin-geo.css` — habillage de la métabox « Localisation du croquis »
- `css/fonts.css` · `vendor/fonts/` — Spectral & Archivo auto-hébergées (SIL OFL)
- `vendor/leaflet/` — Leaflet + markercluster, en local

## Feuilletage des longs carnets

Certains carnets dépassent 100 croquis. `single.php` ne rend donc côté serveur
qu'une fenêtre de planches autour du croquis demandé (voir
`lescarnets_plate_window()`, filtrable). Les autres sont posées en « stub » de
même hauteur — l'URL, le titre et la barre de progression sont déjà justes — et
`js/feuilletage.js` les hydrate par lots via `admin-ajax.php`
(`action=lescarnets_plates`) à l'approche du lecteur. Si la requête échoue, la
planche devient un lien classique vers le croquis.

Le défilement utilise `scroll-snap-type: y mandatory` : on se cale toujours sur
une planche, jamais entre deux. Deux cales de 6dvh (`.reader::before/::after`)
rendent atteignables les points d'accroche du premier et du dernier croquis,
qui exigeraient sinon un défilement négatif ou au-delà de la fin.

La note de chaque croquis est bornée à quatre lignes, dépliable par « lire la
suite » : une planche plus haute que l'écran sort du cadre de l'accroche — la
spec rend alors toute position valide à l'intérieur — et le moindre geste
emportait au croquis suivant.

## Coordonnées

Stockées en **post meta natives**, sans ACF ni plugin. Les clés du fonds
(1347 croquis renseignés) :

| clé | rôle |
|---|---|
| `carnet_lat` · `carnet_lon` | coordonnées décimales, séparateur point |
| `carnet_lieu` | nom du lieu, affiché en légende de la métabox et sur le pin |
| `carnet_precision` | `lieu` (point précis) ou `carnet` (approché) |
| `carnet_mobile` | `1` si le croquis a été fait en mouvement |

Le thème lit les trois premières et n'écrit que `carnet_lat` / `carnet_lon` ;
`carnet_precision` et `carnet_mobile` sont affichées en contexte mais jamais
modifiées. Les noms sont centralisés dans `lescarnets_meta_keys()`.

Un pin déplaçable apparaît dans l'éditeur de chaque article. La sauvegarde
ignore un champ absent du POST plutôt que de l'effacer : sans ce garde-fou,
un enregistrement dans un contexte sans métabox supprimerait une coordonnée.

Au-delà de `lescarnets_cluster_threshold()` points (8 par défaut), les cartes
regroupent les pins — un carnet comme Roma compte 224 croquis dans la même
ville.

## Page « Carte »

Une page `carte` porte le gabarit « Carte générale » et figure au menu
principal. `/category/carnets/` ne liste plus les 1300 croquis à la suite :
l'URL reste servie, mais affiche le sommaire par continent.
