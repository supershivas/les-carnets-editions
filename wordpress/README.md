# Le greffon WordPress — l'image du carnet

Sur l'accueil de [lescarnets.fr](https://lescarnets.fr), chaque carnet est représenté par une
vignette. Le thème prend celle du **dernier croquis publié** : l'image du carnet change donc
toute seule dès qu'on publie. `carnets-vignettes/` permet de la choisir une fois pour toutes.

## Installer

1. Copier le dossier `carnets-vignettes/` dans `wp-content/plugins/` du site.
2. Extensions → activer **Les Carnets — image du carnet**.

Rien à modifier dans le thème : le greffon substitue l'image au vol sur l'accueil
(filtre `post_thumbnail_id`, actif uniquement sur la page d'accueil).

## Choisir une image

**Articles → Images des carnets.** Un écran, tous les carnets, groupés par région, avec
l'image qu'ils montrent aujourd'hui et la mention *image choisie* ou *dernier publié*.

Cliquer un carnet ouvre ses croquis en planche contact : un clic sur l'un d'eux en fait
l'image du carnet. Le bouton **Revenir au dernier croquis publié** rend la main au
comportement d'origine.

Le même choix est rappelé, avec un lien vers cet écran, en bas de l'écran d'édition de la
catégorie (Articles → Catégories → modifier).

## Sous le capot

- Un carnet est une **catégorie feuille** (`carnets / afrique / senegal`) : une catégorie qui
  porte des croquis et n'a pas d'enfant.
- Le choix est rangé dans le term meta `carnet_vignette` de cette catégorie — la valeur est
  l'identifiant de la pièce jointe, pas une URL, donc les recadrages suivent.
- Sans choix enregistré, le greffon ne fait rien : le thème reprend son dernier publié.
- Désactiver le greffon rend l'accueil à son état d'avant, sans rien perdre : les choix
  restent en base et reviennent à la réactivation.
