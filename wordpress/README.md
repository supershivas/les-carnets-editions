# Ce qui va sur lescarnets.fr

Le site WordPress n'est pas dans ce dépôt : il tourne chez l'hébergeur, avec son thème
`lescarnets`. Ce dossier contient les fichiers à y **déposer**, et la marche à suivre.

| Fichier d'ici | Où il va sur le site | Ce qu'il apporte |
|---|---|---|
| `carnets-vignettes/` | extension (`wp-content/plugins/`) | choisir le croquis qui représente chaque carnet sur l'accueil |
| `theme-lescarnets/js/carte-public.js` | `wp-content/themes/lescarnets/js/carte-public.js` | les pins d'une même coordonnée ne se chevauchent plus |
| `theme-lescarnets/css/pin.css` | `wp-content/themes/lescarnets/css/pin.css` | la mise en forme qui va avec |

---

## 1. Choisir l'image d'un carnet

### Installer l'extension

1. Faire un zip du dossier `carnets-vignettes/` (ou prendre celui qui vous a été envoyé).
2. Sur le site : **Extensions → Ajouter une extension → Téléverser une extension**,
   choisir le zip, **Installer maintenant**, puis **Activer**.

### S'en servir

**Articles → Images des carnets.** Un seul écran : tous les carnets, groupés par région,
avec l'image que chacun montre aujourd'hui et la mention *image choisie* ou *dernier publié*.

Cliquer un carnet — Soudan, par exemple — ouvre ses croquis en planche contact. Un clic sur
l'un d'eux en fait l'image du carnet : retour immédiat à la liste des carnets, un toast dit
*L'image du carnet Soudan a bien été changée*, et la vignette du carnet est cerclée d'ocre le
temps qu'on la regarde. Le bouton **Revenir au dernier croquis publié** rend la main au
comportement d'origine, avec son propre toast.

Le rappel et le lien vers cet écran sont aussi en bas de l'écran d'édition de la catégorie
(Articles → Catégories → un carnet → Modifier).

### Sous le capot

- Un carnet est une **catégorie feuille** (`carnets / afrique / soudan`) : une catégorie qui
  porte des croquis et n'a pas d'enfant.
- Le choix est rangé dans le term meta `carnet_vignette` de cette catégorie — c'est
  l'identifiant de la pièce jointe, pas une URL, donc les recadrages suivent.
- Rien à modifier dans le thème : le filtre `post_thumbnail_id` substitue l'image au vol,
  uniquement sur l'accueil.
- Sans choix enregistré, l'extension ne fait rien. La désactiver rend l'accueil à son état
  d'avant sans rien perdre : les choix restent en base.

---

## 2. Les pins qui se chevauchent sur la carte

**Le diagnostic.** Sur `lescarnets.fr/carte/`, 921 des 1 347 croquis partagent leur
coordonnée avec un autre — le géocodage est souvent au lieu ou au carnet, pas au mètre.
Jusqu'à **33 croquis sur un même point**. Les quatre croquis de Busua, par exemple, sont
tous les quatre à 4.79 / −1.95. Les pins se posaient exactement les uns sur les autres :
seul le dernier dessiné répondait au clic, et son étiquette ouverte passait par-dessus
ses voisins.

**La correction.** Une coordonnée = un pin, qui porte son nombre. Ouvert, il liste tous ses
croquis, chacun avec son titre cliquable et son lieu. Et pendant qu'une étiquette est
ouverte, les autres pins s'effacent à 25 % : l'étiquette est large, elle ne se lit plus
par-dessus une forêt de gouttes. Les coordonnées uniques gardent exactement l'aspect d'avant.

### Déposer les deux fichiers

Par **Apparence → Éditeur de fichiers de thème** (le plus court, sans FTP) :

1. Dans la liste de droite, ouvrir `js/carte-public.js`.
2. Tout sélectionner, coller le contenu de `theme-lescarnets/js/carte-public.js`,
   **Mettre à jour le fichier**.
3. Recommencer avec `css/pin.css`.
4. Recharger `lescarnets.fr/carte/` en vidant le cache — **Ctrl+Maj+R** (⌘+Maj+R sur Mac) :
   le numéro de version des fichiers n'a pas bougé, le navigateur garde sinon l'ancien.

Si l'éditeur de fichiers est absent (certains hébergeurs le coupent), passer par FTP et
écraser les deux fichiers aux mêmes emplacements.
