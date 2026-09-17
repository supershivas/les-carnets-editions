<?php
/**
 * Les textes des pages générées, réglables dans l'admin.
 *
 * Le thème compose lui-même l'accueil, les pages de carnet, la carte, le
 * feuilletage : leurs textes ne viennent d'aucun article, ils étaient donc
 * écrits en dur dans les gabarits. Ils sont déclarés ici, avec leur valeur
 * d'origine pour défaut, et réglables dans
 * Apparence → Personnaliser → Textes du site.
 *
 * Deux règles tiennent l'ensemble :
 *   — un champ vidé reprend le texte d'origine, jamais du vide ;
 *   — un texte à trou (« %d croquis ») supporte qu'on l'abîme : trou retiré,
 *     la phrase s'affiche sans son nombre ; trou mal recopié, c'est le texte
 *     d'origine qui est composé. Dans aucun cas la page ne casse.
 */

if (!defined('ABSPATH')) exit;

/**
 * Les balises tolérées dans un champ riche.
 */
function lescarnets_txt_balises() {
    return array(
        'em'     => array(),
        'i'      => array(),
        'strong' => array(),
        'b'      => array(),
        'br'     => array(),
        'span'   => array('class' => array()),
        'a'      => array('href' => array(), 'title' => array(), 'target' => array(), 'rel' => array()),
    );
}

/**
 * Tous les textes réglables : section, libellé, valeur d'origine, nature.
 *
 * type  'texte'  une ligne, échappée à l'affichage
 *       'riche'  plusieurs lignes, quelques balises tolérées
 * aide  ce que le champ attend, notamment ses trous (%d, %s)
 */
function lescarnets_textes_defs() {
    static $defs = null;
    if (null !== $defs) return $defs;

    $defs = array(

        /* ---- Accueil ------------------------------------------------ */
        'accueil_intro' => array(
            'section' => 'accueil',
            'libelle' => 'Phrase d’accueil',
            'type'    => 'riche',
            'defaut'  => "Dix-sept ans de croquis pris sur le vif, <em>d'une ville à l'autre</em>.\nChaque destination est un carnet.",
            'aide'    => 'En haut de l’accueil, au-dessus des continents. Balises autorisées : em, strong, a, br.',
        ),
        'index_compte' => array(
            'section' => 'accueil',
            'libelle' => 'Compte d’un continent',
            'type'    => 'texte',
            'defaut'  => '%1$d croquis · %2$d carnets',
            'aide'    => '%1$d est le nombre de croquis, %2$d le nombre de carnets. Gardez les deux.',
        ),

        /* ---- En-tête et pied ---------------------------------------- */
        'site_titre' => array(
            'section' => 'chrome',
            'libelle' => 'Titre, en haut de chaque page',
            'type'    => 'texte',
            'defaut'  => 'Les carnets',
        ),
        'site_soustitre' => array(
            'section' => 'chrome',
            'libelle' => 'Suite du titre, en gris',
            'type'    => 'texte',
            'defaut'  => '— Jérôme Agostini',
        ),
        'pied_marque' => array(
            'section' => 'chrome',
            'libelle' => 'Pied de page',
            'type'    => 'texte',
            'defaut'  => 'Les carnets',
        ),
        'pied_mention' => array(
            'section' => 'chrome',
            'libelle' => 'Suite du pied de page',
            'type'    => 'texte',
            'defaut'  => '— croquis de voyage, 2002–2019',
        ),

        /* ---- Barre de tri (accueil et carnets) ----------------------- */
        'tri_label' => array(
            'section' => 'tri',
            'libelle' => 'Intitulé',
            'type'    => 'texte',
            'defaut'  => 'trier',
        ),
        'tri_date' => array(
            'section' => 'tri',
            'libelle' => 'Bouton « par date »',
            'type'    => 'texte',
            'defaut'  => 'date',
        ),
        'tri_nom' => array(
            'section' => 'tri',
            'libelle' => 'Bouton « par nom »',
            'type'    => 'texte',
            'defaut'  => 'nom',
        ),

        /* ---- Page d'un carnet --------------------------------------- */
        'fil_accueil' => array(
            'section' => 'carnet',
            'libelle' => 'Premier maillon du fil d’Ariane',
            'type'    => 'texte',
            'defaut'  => 'Accueil',
        ),
        'carnet_compte' => array(
            'section' => 'carnet',
            'libelle' => 'Compte des croquis',
            'type'    => 'texte',
            'defaut'  => '%d croquis',
            'aide'    => '%d est le nombre de croquis du carnet. Gardez-le.',
        ),
        'carnet_vide' => array(
            'section' => 'carnet',
            'libelle' => 'Carnet sans croquis',
            'type'    => 'texte',
            'defaut'  => 'Ce carnet est encore vide.',
        ),
        'croquis_sans_titre' => array(
            'section' => 'carnet',
            'libelle' => 'Croquis sans titre',
            'type'    => 'texte',
            'defaut'  => 'sans titre',
            'aide'    => 'Sous la vignette d’un croquis auquel aucun titre n’a été donné.',
        ),

        /* ---- Carte générale ----------------------------------------- */
        'carte_compte' => array(
            'section' => 'carte',
            'libelle' => 'Compte des croquis situés',
            'type'    => 'texte',
            'defaut'  => '%d croquis situés',
            'aide'    => '%d est le nombre de croquis placés sur la carte. Gardez-le.',
        ),

        /* ---- Feuilletage (page d'un croquis) ------------------------- */
        'lecture_indice' => array(
            'section' => 'lecture',
            'libelle' => 'Invitation à faire défiler',
            'type'    => 'texte',
            'defaut'  => '↓ faites défiler',
        ),
        'lecture_carte' => array(
            'section' => 'lecture',
            'libelle' => 'Bouton de la carte',
            'type'    => 'texte',
            'defaut'  => 'carte',
        ),
        'lecture_retour' => array(
            'section' => 'lecture',
            'libelle' => 'Retour, quand le croquis n’a pas de carnet',
            'type'    => 'texte',
            'defaut'  => 'Retour',
            'aide'    => 'D’ordinaire le lien porte le nom du carnet ; ce texte ne sert qu’à défaut.',
        ),

        /* ---- Recherche, listes, page introuvable --------------------- */
        'recherche_label' => array(
            'section' => 'service',
            'libelle' => 'Intitulé du champ de recherche',
            'type'    => 'texte',
            'defaut'  => 'Rechercher un croquis',
        ),
        'recherche_exemple' => array(
            'section' => 'service',
            'libelle' => 'Exemple, en gris dans le champ',
            'type'    => 'texte',
            'defaut'  => 'ville, pays, motif…',
        ),
        'recherche_bouton' => array(
            'section' => 'service',
            'libelle' => 'Bouton de recherche',
            'type'    => 'texte',
            'defaut'  => 'Chercher',
        ),
        'recherche_titre' => array(
            'section' => 'service',
            'libelle' => 'Titre d’une page de résultats',
            'type'    => 'texte',
            'defaut'  => 'Recherche : %s',
            'aide'    => '%s est ce qui a été cherché. Gardez-le.',
        ),
        'liste_titre' => array(
            'section' => 'service',
            'libelle' => 'Titre d’une liste sans nom',
            'type'    => 'texte',
            'defaut'  => 'Les carnets',
        ),
        'liste_vide' => array(
            'section' => 'service',
            'libelle' => 'Liste sans résultat',
            'type'    => 'texte',
            'defaut'  => 'Rien à afficher.',
        ),
        'err404_titre' => array(
            'section' => 'service',
            'libelle' => 'Page introuvable — titre',
            'type'    => 'texte',
            'defaut'  => 'Page introuvable',
        ),
        'err404_texte' => array(
            'section' => 'service',
            'libelle' => 'Page introuvable — explication',
            'type'    => 'riche',
            'defaut'  => "Cette adresse ne correspond à aucun croquis. Peut-être un lien ancien,\nou une faute de frappe.",
        ),
        'err404_retour' => array(
            'section' => 'service',
            'libelle' => 'Page introuvable — lien de retour',
            'type'    => 'texte',
            'defaut'  => '← Revenir à l’accueil des carnets',
        ),
    );

    return $defs;
}

/** Les sections, dans l'ordre où elles se présentent dans l'admin. */
function lescarnets_textes_sections() {
    return array(
        'accueil' => array('Accueil',            'La phrase du haut et le compte affiché sous chaque continent.'),
        'chrome'  => array('En-tête et pied',    'Présents sur toutes les pages, sauf en feuilletage.'),
        'tri'     => array('Barre de tri',       'Sur l’accueil et sur chaque page de carnet.'),
        'carnet'  => array('Page d’un carnet',   'Fil d’Ariane, compte des croquis, carnet vide.'),
        'carte'   => array('Carte générale',     'La page qui porte le modèle « Carte générale ».'),
        'lecture' => array('Feuilletage',        'La page d’un croquis, en galerie plein écran.'),
        'service' => array('Recherche et erreurs', 'Champ de recherche, listes vides, page introuvable.'),
    );
}

/**
 * Le texte réglé pour cette clé, prêt à être affiché.
 *
 * Les arguments qui suivent la clé remplissent les trous (%d, %s).
 *
 *   echo lescarnets_txt('carnet_compte', 42);
 */
function lescarnets_txt($cle) {
    $defs = lescarnets_textes_defs();
    if (!isset($defs[$cle])) return '';

    $def = $defs[$cle];
    $val = get_theme_mod('lescarnets_txt_' . $cle, $def['defaut']);
    if (!is_string($val) || '' === trim($val)) {
        $val = $def['defaut'];   // champ vidé : on revient au texte d'origine
    }

    $args = array_slice(func_get_args(), 1);
    if ($args) {
        // Un trou effacé ou mal recopié ferait lever une ValueError en PHP 8 :
        // on retombe sur le texte d'origine plutôt que de casser la page.
        try {
            $val = vsprintf($val, $args);
        } catch (\Throwable $e) {
            try { $val = vsprintf($def['defaut'], $args); }
            catch (\Throwable $e2) { /* on garde la valeur telle quelle */ }
        }
    }

    return ('riche' === $def['type'])
        ? wpautop(wp_kses($val, lescarnets_txt_balises()))
        : esc_html($val);
}

/** La même chose, sans balises ni mise en paragraphe : pour un attribut. */
function lescarnets_txt_attr($cle) {
    $defs = lescarnets_textes_defs();
    if (!isset($defs[$cle])) return '';
    $val = get_theme_mod('lescarnets_txt_' . $cle, $defs[$cle]['defaut']);
    if (!is_string($val) || '' === trim($val)) $val = $defs[$cle]['defaut'];
    return esc_attr(wp_strip_all_tags($val));
}

/* --------------------------------------------------------------
 * L'écran de réglage
 * ------------------------------------------------------------ */

function lescarnets_textes_customize($wp_customize) {
    $wp_customize->add_panel('lescarnets_textes', array(
        'title'       => 'Textes du site',
        'description' => 'Les textes que le thème compose lui-même, page par page. '
                       . 'Un champ laissé vide reprend le texte d’origine.',
        'priority'    => 20,
    ));

    foreach (lescarnets_textes_sections() as $cle => $section) {
        $wp_customize->add_section('lescarnets_txt_s_' . $cle, array(
            'title'       => $section[0],
            'description' => $section[1],
            'panel'       => 'lescarnets_textes',
        ));
    }

    foreach (lescarnets_textes_defs() as $cle => $def) {
        $id = 'lescarnets_txt_' . $cle;
        $riche = ('riche' === $def['type']);

        $wp_customize->add_setting($id, array(
            'default'           => $def['defaut'],
            'type'              => 'theme_mod',
            'capability'        => 'edit_theme_options',
            'transport'         => 'refresh',
            'sanitize_callback' => $riche
                ? 'lescarnets_txt_sanitize_riche'
                : 'lescarnets_txt_sanitize_texte',
        ));

        $wp_customize->add_control($id, array(
            'label'       => $def['libelle'],
            'description' => isset($def['aide']) ? $def['aide'] : '',
            'section'     => 'lescarnets_txt_s_' . $def['section'],
            'type'        => $riche ? 'textarea' : 'text',
        ));
    }
}
add_action('customize_register', 'lescarnets_textes_customize');

function lescarnets_txt_sanitize_texte($val) {
    return sanitize_text_field($val);
}

function lescarnets_txt_sanitize_riche($val) {
    return trim(wp_kses($val, lescarnets_txt_balises()));
}

/* --------------------------------------------------------------
 * Un raccourci dans le menu, là où on cherche « les textes »
 * ------------------------------------------------------------ */

function lescarnets_textes_menu() {
    add_theme_page(
        'Textes du site',
        'Textes du site',
        'edit_theme_options',
        'lescarnets-textes',
        'lescarnets_textes_redirige'
    );
}
add_action('admin_menu', 'lescarnets_textes_menu');

/** La page n'existe que pour ouvrir le personnalisateur au bon endroit. */
function lescarnets_textes_redirige() {
    $url = add_query_arg(
        array(
            'autofocus[panel]' => 'lescarnets_textes',
            'url'              => rawurlencode(home_url('/')),
        ),
        admin_url('customize.php')
    );
    echo '<div class="wrap"><h1>Textes du site</h1>';
    echo '<p><a class="button button-primary" href="' . esc_url($url) . '">Ouvrir l’écran des textes</a></p>';
    echo '<script>window.location.href=' . wp_json_encode($url) . ';</script></div>';
}
