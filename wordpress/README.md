# Ce qui va sur lescarnets.fr

Le site WordPress n'est pas dans ce dépôt : il tourne chez l'hébergeur, avec son thème
`lescarnets`. Ce dossier contient les fichiers à y **déposer**, et la marche à suivre.

| Dossier d'ici | Où il va sur le site | Ce qu'il apporte |
|---|---|---|
| `carnets-vignettes/` | extension (`wp-content/plugins/`) | choisir le croquis qui représente chaque carnet sur l'accueil |
| `theme-lescarnets/` | le thème (`wp-content/themes/lescarnets/`) | **le thème complet, version 1.4** : les textes des pages générées réglables dans l'admin, et les pins d'une même coordonnée qui ne se chevauchent plus sur la carte |

Le thème est maintenant versionné ici : c'est cette copie qui fait foi. Toute retouche se
fait dans `theme-lescarnets/`, puis on renvoie le thème sur le site.

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

Les deux fichiers concernés (`js/carte-public.js`, `css/pin.css`) sont dans le thème livré
plus bas : les poser, c'est installer le thème.

---

## 3. Les textes des pages générées

**Le problème.** L'accueil, les pages de carnet, la carte, le feuilletage et la page
introuvable sont composés par le thème, pas par des articles : leurs textes n'avaient
aucun champ dans WordPress, ils étaient écrits en dur dans les gabarits.

**Maintenant.** **Apparence → Personnaliser → Textes du site** (ou Apparence → Textes du
site, qui y mène directement). Sept sections, une par endroit du site :

| Section | Ce qu'on y règle |
|---|---|
| Accueil | la phrase du haut, le compte affiché sous chaque continent |
| En-tête et pied | le titre du site et sa suite en gris, la mention de pied de page |
| Barre de tri | « trier », « date », « nom » — sur l'accueil et les pages de carnet |
| Page d'un carnet | fil d'Ariane, compte des croquis, carnet vide, croquis sans titre |
| Carte générale | « %d croquis situés » |
| Feuilletage | l'invitation à défiler, le bouton carte, le retour par défaut |
| Recherche et erreurs | champ de recherche, listes vides, page introuvable |

On écrit dans le champ, l'aperçu à droite suit, on publie. Deux garde-fous :

- un champ vidé **reprend le texte d'origine** — on ne peut pas se retrouver avec du blanc ;
- les textes à trou (`%d croquis`) supportent qu'on les abîme : trou retiré, la phrase
  s'affiche sans son nombre ; trou mal recopié, c'est le texte d'origine qui est composé.
  La page ne casse jamais.

La phrase d'accueil et l'explication de la page introuvable acceptent quelques balises
(`em`, `strong`, `a`, `br`) ; tout le reste est neutralisé.

Les textes réglés vivent en base (`theme_mod`) : ils survivent au remplacement du thème.

---

## 4. La recherche

**Le problème.** Le site n'avait aucun champ de recherche — la fonction existait dans
WordPress, rien n'y menait. Et telle quelle, elle ne cherchait que dans le titre et le
texte : `?s=busua` ne rendait rien, alors que quatre croquis portent Busua comme **lieu**.

**Maintenant :**

- **une loupe** dans l'en-tête, sur toutes les pages (pas en feuilletage, qui n'a pas
  d'en-tête) : au clic, le champ se déplie vers la gauche en un tiers de seconde et prend
  le focus. Échap le referme, un clic ailleurs aussi — mais seulement s'il est vide, pour
  ne pas escamoter une recherche en cours de frappe. Sur une page de résultats il est
  déjà ouvert, portant le mot cherché. Replié, il sort du parcours clavier ;
- **en mobile**, l'en-tête s'empile : la loupe prend sa propre ligne, et le champ déplié
  occupe toute la largeur. Sa taille de texte passe à 16 px, en dessous de quoi iOS
  zoome la page à la mise au point ;
- sans JavaScript, le champ est simplement là, ouvert : la recherche marche avant
  l'animation ;
- la recherche regarde **quatre champs** : titre, texte, lieu (`carnet_lieu`) et nom du
  carnet. Chaque mot tapé doit se retrouver quelque part, pas forcément au même endroit :
  « café rome » trouve un croquis intitulé « Le café du matin » classé dans Roma. Une
  recherche entre guillemets reste d'un seul tenant ;
- une page de résultats à elle (`search.php`) : le compte, la grille, et sous chaque
  vignette **le carnet et le lieu** — c'est ce qui situe un croquis qu'on ne reconnaît
  pas au titre. Sans résultat, elle dit ce sur quoi la recherche porte ;
- la recherche ne rend que des croquis, par pages de 48 ; les pages (À propos, Contact)
  n'encombrent plus les résultats.

Ses textes sont réglables comme les autres, section *Recherche et erreurs*.

### Un bug corrigé au passage

En 1.2, le titre d'une page de résultats affichait
« Recherche : https://lescarnets.fr/wp-content/themes/lescarnets ». `get_theme_mod()`
remplace de lui-même un `%s` trouvé dans une **valeur par défaut** par l'adresse du
dossier du thème — c'est prévu pour les chemins d'images, et ça mangeait le trou de
« Recherche : %s ». Le texte d'origine n'est plus passé à `get_theme_mod()` : il sert de
repli après coup.

---

### Installer le thème

1. Faire un zip de `theme-lescarnets/` **renommé `lescarnets`** (ou prendre celui qui vous
   a été envoyé) — WordPress se fie au nom du dossier contenu dans l'archive.
2. **Apparence → Thèmes → Ajouter un thème → Téléverser un thème** → **Installer maintenant**.
3. WordPress voit qu'il est déjà là et propose **Remplacer l'actuel par le téléversé** :
   c'est ce qu'il faut. Il affiche au passage l'ancienne version → 1.4.
4. Le thème reste actif, rien d'autre à faire. La version passant à 1.4, les navigateurs
   reprennent d'eux-mêmes les fichiers de la carte — pas besoin de vider le cache.
