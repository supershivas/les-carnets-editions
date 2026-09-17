<?php
/**
 * Les Carnets — fonctions du thème
 */

if (!defined('ABSPATH')) exit;

/* Les textes des pages générées, réglables dans l'admin */
require_once get_template_directory() . '/inc/textes.php';

/* La recherche : croquis seulement, et elle regarde aussi lieu et carnet */
require_once get_template_directory() . '/inc/recherche.php';

/* --------------------------------------------------------------
 * Réglages de base du thème
 * ------------------------------------------------------------ */
function lescarnets_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('automatic-feed-links');

    // Tailles d'images : vignette carrée pour l'index, grand format pour la galerie
    add_image_size('carnet_vignette', 640, 640, true);   // carré, recadré
    add_image_size('carnet_planche', 1600, 1600, false); // grand, non recadré (feuilletage)

    register_nav_menus(array(
        'principal' => 'Menu principal',
        'pied'      => 'Menu de pied de page',
    ));
}
add_action('after_setup_theme', 'lescarnets_setup');

/* --------------------------------------------------------------
 * Styles & scripts
 * ------------------------------------------------------------ */
function lescarnets_assets() {
    // Polices auto-hébergées (voir css/fonts.css) : aucun appel tiers
    wp_enqueue_style(
        'lescarnets-fonts',
        get_template_directory_uri() . '/css/fonts.css',
        array(),
        wp_get_theme()->get('Version')
    );
    wp_enqueue_style('lescarnets-style', get_stylesheet_uri(), array('lescarnets-fonts'), wp_get_theme()->get('Version'));

    // Sur une page de croquis : Leaflet + carte de lecture + feuilletage
    if (is_single()) {
        wp_enqueue_style('leaflet');
        wp_enqueue_style('lescarnets-pin');
        wp_enqueue_script('leaflet');
        wp_enqueue_script('lescarnets-carte', get_template_directory_uri() . '/js/carte-public.js', array('leaflet'), wp_get_theme()->get('Version'), true);
        wp_localize_script('leaflet', 'LC_LEAFLET', array('img' => get_template_directory_uri() . '/vendor/leaflet/images/'));
        wp_enqueue_script('lescarnets-feuilletage', get_template_directory_uri() . '/js/feuilletage.js', array('lescarnets-carte'), wp_get_theme()->get('Version'), true);

        $dest = lescarnets_post_destination(get_queried_object_id());
        wp_localize_script('lescarnets-feuilletage', 'LC_READER', array(
            'site' => get_bloginfo('name'),
            'ajax' => admin_url('admin-ajax.php'),
            'dest' => $dest ? (int) $dest->term_id : 0,
        ));
    }

    // Les cartes de category.php / template-carte.php sont rendues en pleine
    // page : sans cet appel ici, leur CSS Leaflet ne serait imprimée qu'au
    // pied de page (carte non stylée pendant le premier rendu).
    if (is_category() || is_page_template('template-carte.php')) {
        wp_enqueue_style('leaflet');
        wp_enqueue_style('lescarnets-pin');
        // Les cartes de carnet comme la carte generale regroupent : on
        // charge la feuille d'animation en amont pour eviter qu'elle ne
        // s'imprime qu'au pied de page.
        wp_enqueue_style('leaflet-cluster');
    }

    // La loupe de l'en-tête. Pas en feuilletage : l'en-tête y est masqué.
    if (!is_single()) {
        wp_enqueue_script('lescarnets-recherche', get_template_directory_uri() . '/js/recherche.js', array(), wp_get_theme()->get('Version'), true);
    }

    // Le tri interactif sur l'accueil et les pages carnet
    if (is_front_page() || is_home() || is_category()) {
        wp_enqueue_script('lescarnets-tri', get_template_directory_uri() . '/js/tri.js', array(), wp_get_theme()->get('Version'), true);
    }
}
add_action('wp_enqueue_scripts', 'lescarnets_assets');

/* --------------------------------------------------------------
 * Ordre des continents sur l'accueil (par poids de corpus).
 * Adaptez les slugs si vos catégories continent diffèrent.
 * ------------------------------------------------------------ */
function lescarnets_continents() {
    return array('europe', 'ameriques', 'afrique', 'asie');
}

/* --------------------------------------------------------------
 * Continents ordonnés par récence de leur dernier croquis.
 * Renvoie un tableau de WP_Term (le plus récent en tête).
 * ------------------------------------------------------------ */
function lescarnets_continents_by_recency() {
    // Termes déclarés dans lescarnets_continents()…
    $terms = array();
    foreach (lescarnets_continents() as $slug) {
        $t = get_term_by('slug', $slug, 'category');
        if ($t && !is_wp_error($t)) $terms[] = $t;
    }

    // …ou, si aucun ne correspond (slugs différents dans cette installation),
    // repli sur les catégories de plus haut niveau qui contiennent des croquis.
    // Sans ce repli, l'accueil s'affiche vide et sans message d'erreur.
    if (empty($terms)) {
        $roots = get_categories(array('parent' => 0, 'hide_empty' => true));
        $carnets = get_term_by('slug', 'carnets', 'category');
        if ($carnets && !is_wp_error($carnets)) {
            // Arborescence /category/carnets/<continent>/… : on descend d'un cran
            $roots = get_categories(array('parent' => $carnets->term_id, 'hide_empty' => true));
        }
        $terms = $roots ? $roots : array();
    }

    $items = array();
    foreach ($terms as $t) {
        $latest = get_posts(array(
            'cat'            => $t->term_id,   // inclut les catégories descendantes
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ));
        $ts = $latest ? (int) get_post_time('U', true, $latest[0]) : 0;
        $items[] = array('term' => $t, 'ts' => $ts);
    }
    usort($items, function ($a, $b) { return $b['ts'] - $a['ts']; });
    return array_map(function ($i) { return $i['term']; }, $items);
}

/* --------------------------------------------------------------
 * Index des carnets : continents (par récence) et leurs destinations
 * en vignettes triables. Rendu par l'accueil ET par la catégorie racine
 * « carnets », pour que /category/carnets/ montre un sommaire lisible
 * au lieu des 1300 croquis à la suite.
 * ------------------------------------------------------------ */
function lescarnets_render_index() {
    foreach (lescarnets_continents_by_recency() as $continent) {
        $dests = lescarnets_destinations($continent->slug);
        if (empty($dests)) continue;

        // Date du dernier croquis de chaque destination, puis tri par date desc.
        foreach ($dests as $d) { $d->latest_ts = lescarnets_term_latest_ts($d->term_id); }
        usort($dests, function ($a, $b) { return $b->latest_ts - $a->latest_ts; });

        $total = 0; foreach ($dests as $d) { $total += $d->count; }
        ?>
    <section class="continent">
      <div class="continent-head">
        <h2 class="voice"><?php echo esc_html($continent->name); ?></h2>
        <span class="count"><?php echo lescarnets_txt('index_compte', intval($total), count($dests)); ?></span>
      </div>

      <div class="sortbar">
        <span class="lbl"><?php echo lescarnets_txt('tri_label'); ?></span>
        <button type="button" data-sort="date" class="on"><?php echo lescarnets_txt('tri_date'); ?></button>
        <button type="button" data-sort="name"><?php echo lescarnets_txt('tri_nom'); ?></button>
      </div>

      <div class="grid">
        <?php foreach ($dests as $term):
              $cover = lescarnets_term_cover($term);
        ?>
          <a class="vignette" href="<?php echo esc_url(get_category_link($term->term_id)); ?>"
             data-date="<?php echo intval($term->latest_ts); ?>"
             data-name="<?php echo esc_attr($term->name); ?>">
            <span class="frame">
              <?php if ($cover) { echo $cover; } else { ?>
                <span class="empty"><?php echo esc_html($term->name); ?></span>
              <?php } ?>
            </span>
            <span class="label">
              <span class="name voice"><?php echo esc_html($term->name); ?></span>
              <span class="n"><?php echo intval($term->count); ?></span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
        <?php
    }
}

/* Vrai si le terme est la catégorie racine « carnets » (le fonds entier) */
function lescarnets_is_root_carnets($term) {
    return $term && isset($term->slug) && $term->slug === 'carnets' && (int) $term->parent === 0;
}

/* --------------------------------------------------------------
 * Horodatage du dernier croquis d'une catégorie (pour tri/date).
 * ------------------------------------------------------------ */
function lescarnets_term_latest_ts($term_id) {
    $latest = get_posts(array(
        'cat'            => $term_id,
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ));
    return $latest ? (int) get_post_time('U', true, $latest[0]) : 0;
}

/* --------------------------------------------------------------
 * Destinations (carnets) d'un continent : toutes les catégories
 * descendantes qui contiennent au moins un croquis.
 * Renvoie un tableau de WP_Term, triées par nombre décroissant.
 * (Le réglage fin de l'ordre viendra plus tard.)
 * ------------------------------------------------------------ */
function lescarnets_destinations($continent_slug) {
    $continent = get_term_by('slug', $continent_slug, 'category');
    if (!$continent || is_wp_error($continent)) return array();

    $terms = get_categories(array(
        'child_of'   => $continent->term_id,
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ));

    // On ne garde que les catégories « feuilles » (sans enfant qui a des posts),
    // pour que Roma apparaisse comme carnet et non Italia > Roma en double.
    $ids_with_children = array();
    foreach ($terms as $t) {
        if ($t->parent) $ids_with_children[$t->parent] = true;
    }
    $leaves = array();
    foreach ($terms as $t) {
        if (!isset($ids_with_children[$t->term_id])) $leaves[] = $t;
    }
    return $leaves;
}

/* --------------------------------------------------------------
 * Image mise en avant du croquis le plus récent d'une catégorie,
 * pour illustrer la vignette de destination.
 * ------------------------------------------------------------ */
function lescarnets_term_cover($term, $size = 'carnet_vignette') {
    $q = new WP_Query(array(
        'cat'                 => $term->term_id,
        'posts_per_page'      => 1,
        'meta_key'            => '_thumbnail_id', // en priorité un post avec image
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ));
    $html = '';
    if ($q->have_posts()) {
        $q->the_post();
        if (has_post_thumbnail()) {
            // WordPress échappe déjà les attributs : ne pas pré-échapper
            $html = get_the_post_thumbnail(get_the_ID(), $size, array('alt' => $term->name, 'loading' => 'lazy'));
        }
    }
    wp_reset_postdata();
    return $html;
}

/* --------------------------------------------------------------
 * Catégorie « destination » d'un post = sa catégorie la plus
 * profonde (la plus spécifique). Sert de contexte au feuilletage.
 * ------------------------------------------------------------ */
function lescarnets_post_destination($post_id = null) {
    $cats = get_the_category($post_id);
    if (empty($cats)) return null;
    // On écarte la catégorie racine « carnets » si présente
    $best = null; $best_depth = -1;
    foreach ($cats as $c) {
        $depth = count(get_ancestors($c->term_id, 'category'));
        if ($depth > $best_depth) { $best_depth = $depth; $best = $c; }
    }
    return $best;
}

/* --------------------------------------------------------------
 * Bornes de dates d'un carnet (pour l'en-tête destination)
 * ------------------------------------------------------------ */
function lescarnets_carnet_years($term_id) {
    $first = get_posts(array('cat'=>$term_id,'posts_per_page'=>1,'orderby'=>'date','order'=>'ASC','no_found_rows'=>true));
    $last  = get_posts(array('cat'=>$term_id,'posts_per_page'=>1,'orderby'=>'date','order'=>'DESC','no_found_rows'=>true));
    if (!$first || !$last) return '';
    $y0 = get_the_date('Y', $first[0]);
    $y1 = get_the_date('Y', $last[0]);
    return ($y0 === $y1) ? $y0 : "$y0 – $y1";
}

/* --------------------------------------------------------------
 * Classe « feuilletage » sur le <body> des pages croquis
 * (bascule l'ambiance papier → galerie sombre dans style.css)
 * ------------------------------------------------------------ */
add_filter('body_class', function($classes){
    if (is_single()) $classes[] = 'feuilletage';
    return $classes;
});

/* ==============================================================
 * FEUILLETAGE — chargement progressif des planches
 *
 * Un carnet peut compter plus de 100 croquis. Tout rendre côté serveur
 * signifie autant d'exécutions du filtre `the_content` et autant de
 * balises <img> dans le HTML. On ne rend donc qu'une fenêtre autour du
 * croquis demandé ; le reste est posé en « stub » (même hauteur, URL et
 * titre connus) et hydraté en AJAX à l'approche du lecteur.
 * ============================================================ */

/* Nombre de planches rendues de chaque côté du croquis demandé */
function lescarnets_plate_window() {
    return (int) apply_filters('lescarnets_plate_window', 6);
}

/* Corps d'une planche (figure + légende), sans la <section> englobante.
 * Utilisé par single.php au premier rendu et par l'endpoint AJAX ensuite,
 * pour que les deux chemins produisent exactement le même balisage. */
function lescarnets_plate_inner_html($p, $dest = null, $htag = 'h2', $eager = false) {
    $pid   = $p->ID;
    $title = get_the_title($pid);
    $alt   = $title ?: ($dest ? $dest->name : get_bloginfo('name'));

    // Les attributs sont échappés par WordPress : on passe le texte brut
    $attr = array(
        'alt'      => $alt,
        'loading'  => $eager ? 'eager' : 'lazy',
        'decoding' => 'async',
    );
    $img = get_the_post_thumbnail($pid, 'carnet_planche', $attr);
    // Repli sur l'image pleine taille si la déclinaison n'est pas générée
    if (!$img) $img = get_the_post_thumbnail($pid, 'large', $attr);

    // `the_content` a besoin du $post courant (blocs, shortcodes). On
    // restaure l'état précédent à la main : cette fonction tourne aussi
    // en AJAX, où il n'y a pas de boucle principale à réinitialiser.
    $saved = isset($GLOBALS['post']) ? $GLOBALS['post'] : null;
    $GLOBALS['post'] = $p;
    setup_postdata($p);
    $raw = apply_filters('the_content', $p->post_content);
    $GLOBALS['post'] = $saved;
    if ($saved) setup_postdata($saved);

    $text = trim(preg_replace('/<img[^>]*>/i', '', $raw));
    $text = trim(strip_tags($text, '<em><strong><a><br>'));

    // .plate-in se dimensionne sur l'image : la légende se ferre donc à
    // gauche SUR le bord gauche du croquis, et non sur un axe arbitraire.
    // Hiérarchie : titre, puis date (une date de voyage se lit avant la
    // note), puis le texte.
    $out  = '<div class="plate-in"><figure>' . $img . '</figure><div class="cap">';
    if ($title) {
        $out .= '<' . $htag . ' class="voice">' . esc_html($title) . '</' . $htag . '>';
    }
    // Le lieu fait partie de l'information du croquis : la carte ne suffit
    // pas (elle est optionnelle, et absente sur mobile replié).
    $lieu = lescarnets_lieu($pid);
    $out .= '<div class="d">';
    if ($lieu !== '') {
        $out .= '<span class="lieu">' . esc_html($lieu) . '</span><span class="sep">·</span>';
    }
    $out .= '<time datetime="' . esc_attr(get_the_date('c', $pid)) . '">'
          . esc_html(get_the_date('j F Y', $pid)) . '</time></div>';
    if ($text) {
        $out .= '<div class="x voice">' . wp_kses_post($text) . '</div>';
    }
    $out .= '</div></div>';

    return $out;
}

/* Endpoint AJAX : renvoie le corps des planches demandées, par lot.
 * Lecture seule de contenu déjà public — pas de nonce, mais on vérifie
 * que chaque ID est bien un article publié, et on borne la taille du lot. */
function lescarnets_ajax_plates() {
    $raw = isset($_GET['ids']) ? (string) $_GET['ids'] : '';
    $ids = array_filter(array_map('absint', explode(',', $raw)));
    $ids = array_slice(array_unique($ids), 0, 12);
    if (empty($ids)) {
        wp_send_json_error(array('message' => 'Aucun identifiant.'), 400);
    }

    $dest = null;
    if (!empty($_GET['dest'])) {
        $t = get_term(absint($_GET['dest']), 'category');
        if ($t && !is_wp_error($t)) $dest = $t;
    }

    // Une requête pour les articles, une pour les métas, une pour les termes
    lescarnets_prime_caches($ids);

    $out = array();
    foreach ($ids as $id) {
        $p = get_post($id);
        if (!$p || $p->post_type !== 'post' || $p->post_status !== 'publish') continue;
        if (post_password_required($p)) continue;
        $out[$id] = lescarnets_plate_inner_html($p, $dest);
    }
    wp_send_json_success($out);
}
add_action('wp_ajax_lescarnets_plates', 'lescarnets_ajax_plates');
add_action('wp_ajax_nopriv_lescarnets_plates', 'lescarnets_ajax_plates');

/* ==============================================================
 * COORDONNÉES — post meta natives, sans plugin
 *
 * Les clés réellement présentes dans le fonds (1347 croquis renseignés) :
 *   carnet_lat / carnet_lon  coordonnées décimales, séparateur point
 *   carnet_lieu              nom du lieu (« Paris », « Dans le tram 8 »)
 *   carnet_precision         'lieu' (point précis) | 'carnet' (approché)
 *   carnet_mobile            '1' si le croquis a été fait en mouvement
 *
 * Le thème ne lit et n'écrit que les trois premières ; precision et mobile
 * sont laissées intactes. Les noms sont centralisés ici pour qu'un
 * changement de schéma ne se cherche pas dans tout le fichier.
 * ============================================================ */
function lescarnets_meta_keys() {
    return array(
        'lat'  => 'carnet_lat',
        'lon'  => 'carnet_lon',
        'lieu' => 'carnet_lieu',
    );
}

/* Enregistre les méta (typées, exposées à l'API REST).
 * carnet_lieu est déclarée en lecture seule côté thème : on l'affiche,
 * on ne l'écrit jamais — pas de sanitize_callback qui pourrait altérer
 * ce qu'un futur import y dépose. */
function lescarnets_register_geo_meta() {
    $k = lescarnets_meta_keys();
    foreach (array($k['lat'], $k['lon']) as $key) {
        register_post_meta('post', $key, array(
            'type'              => 'number',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'lescarnets_sanitize_coord',
            'auth_callback'     => function () { return current_user_can('edit_posts'); },
        ));
    }
    register_post_meta('post', $k['lieu'], array(
        'type'         => 'string',
        'single'       => true,
        'show_in_rest' => true,
    ));
}
add_action('init', 'lescarnets_register_geo_meta');

/* Amorce des caches d'articles en quelques requetes.
 * _prime_post_caches() n'existe que depuis WordPress 6.1 : sur une
 * installation anterieure on retombe sur les fonctions historiques, plutot
 * que de provoquer une erreur fatale au deploiement. */
function lescarnets_prime_caches($ids) {
    if (empty($ids)) return;
    if (function_exists('_prime_post_caches')) {
        _prime_post_caches($ids, true, true);
        return;
    }
    _prime_post_caches_fallback($ids);
}

function _prime_post_caches_fallback($ids) {
    global $wpdb;
    $in = implode(',', array_map('intval', $ids));
    $posts = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE ID IN ($in)");
    update_post_cache($posts);
    update_meta_cache('post', $ids);
    update_object_term_cache($ids, 'post');
}

/* Nom du lieu d'un croquis, ou '' */
function lescarnets_lieu($post_id) {
    $k = lescarnets_meta_keys();
    $v = get_post_meta($post_id, $k['lieu'], true);
    return is_string($v) ? trim($v) : '';
}

/* Sanitize d'une coordonnée.
 * WordPress passe le nom de la méta en 2e argument aux sanitize_callback
 * de register_post_meta : on s'en sert pour borner la latitude à ±90 et la
 * longitude à ±180, au lieu d'un ±180 commun qui laissait passer une
 * latitude de 150. Le formulaire de la métabox passe le nom lui aussi. */
function lescarnets_sanitize_coord($v, $meta_key = '', $object_type = '') {
    if ($v === '' || $v === null) return '';
    $v = str_replace(',', '.', (string) $v);
    if (!is_numeric($v)) return '';
    $v = (float) $v;
    $k = lescarnets_meta_keys();
    $max = ($meta_key === $k['lat']) ? 90 : 180;
    if ($v < -$max || $v > $max) return '';
    return $v;
}

/* Coordonnées d'un post : array('lat'=>..,'lon'=>..) ou null.
 * Les clés du tableau restent 'lat'/'lon' : c'est le contrat interne du
 * thème (data-lat dans single.php, points de carte), indépendant du nom
 * des méta en base. */
function lescarnets_coords($post_id) {
    $k = lescarnets_meta_keys();
    $lat = get_post_meta($post_id, $k['lat'], true);
    $lon = get_post_meta($post_id, $k['lon'], true);
    if ($lat === '' || $lat === null || $lon === '' || $lon === null) return null;
    if (!is_numeric($lat) || !is_numeric($lon)) return null;
    $lat = (float) $lat; $lon = (float) $lon;
    // Garde-fou : une paire hors bornes n'est pas placée sur la carte
    if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) return null;
    return array('lat' => $lat, 'lon' => $lon);
}

/* Liste de points [lat, lon, url, titre, lieu] pour une liste d'IDs */
function lescarnets_points_for($post_ids) {
    $points = array();
    foreach ($post_ids as $pid) {
        $c = lescarnets_coords($pid);
        if (!$c) continue;
        $points[] = array(
            round($c['lat'], 5),
            round($c['lon'], 5),
            get_permalink($pid),
            // get_the_title() passe par wptexturize : le titre revient avec
            // des entités (« l&rsquo;île »). Côté carte, l'étiquette est
            // posée via textContent, qui afficherait l'entité telle quelle.
            // On décode donc ici ; l'injection est impossible puisque le JS
            // n'insère jamais ce texte comme du HTML.
            html_entity_decode(get_the_title($pid), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            lescarnets_lieu($pid),
        );
    }
    return $points;
}

/* Tous les croquis géolocalisés (pour la carte générale) */
function lescarnets_all_geo_ids() {
    return get_posts(array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => lescarnets_meta_keys()['lat'],
        'no_found_rows'  => true,
    ));
}

/* --------------------------------------------------------------
 * Chargement de Leaflet (local) là où une carte est nécessaire
 * ------------------------------------------------------------ */
function lescarnets_leaflet_assets() {
    $base = get_template_directory_uri() . '/vendor/leaflet/';
    wp_register_style('leaflet', $base . 'leaflet.css', array(), '1.9.4');
    wp_register_script('leaflet', $base . 'leaflet.js', array(), '1.9.4', true);
    wp_register_style('leaflet-cluster', $base . 'MarkerCluster.css', array('leaflet'), '1.5.3');
    wp_register_style('leaflet-cluster-def', $base . 'MarkerCluster.Default.css', array('leaflet-cluster'), '1.5.3');
    wp_register_script('leaflet-cluster', $base . 'leaflet.markercluster.js', array('leaflet'), '1.5.3', true);

    // Le pin est partagé entre le site et l'éditeur : il est décrit dans son
    // propre fichier, et dépend de leaflet.css pour l'ordre de cascade.
    wp_register_style(
        'lescarnets-pin',
        get_template_directory_uri() . '/css/pin.css',
        array('leaflet'),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'lescarnets_leaflet_assets', 5);
add_action('admin_enqueue_scripts', 'lescarnets_leaflet_assets', 5);

/* Rend une carte publique : place la donnée + le script d'init */
/* Au-dela de ce nombre de points, on regroupe. Un carnet comme Roma
 * compte 224 croquis dans la meme ville : sans regroupement les pins se
 * superposent et la carte devient illisible. */
function lescarnets_cluster_threshold() {
    return (int) apply_filters('lescarnets_cluster_threshold', 8);
}

function lescarnets_render_map($id, $mode, $points, $center = null, $zoom = null) {
    if (empty($points) && $mode !== 'single') return;

    // Le regroupement depend du NOMBRE de points, plus du mode de carte :
    // un carnet dense en a autant besoin que la carte generale.
    $cluster = count($points) > lescarnets_cluster_threshold();

    $data = array('mode' => $mode, 'points' => $points, 'cluster' => $cluster);
    if ($center) $data['center'] = $center;
    if ($zoom)   $data['zoom']   = $zoom;

    wp_enqueue_style('leaflet');
    wp_enqueue_script('leaflet');
    if ($cluster) {
        // MarkerCluster.css porte les transitions de regroupement : requis.
        // MarkerCluster.Default.css ne porte que les cercles vert/jaune/orange
        // d'origine : on ne le charge pas, l'habillage est dans css/pin.css.
        wp_enqueue_style('leaflet-cluster');
        wp_enqueue_script('leaflet-cluster');
    }
    wp_enqueue_style('lescarnets-pin');
    wp_enqueue_script('lescarnets-carte', get_template_directory_uri() . '/js/carte-public.js', array('leaflet'), wp_get_theme()->get('Version'), true);
    wp_localize_script('leaflet', 'LC_LEAFLET', array('img' => get_template_directory_uri() . '/vendor/leaflet/images/'));

    // serialize_precision vaut 17 sur certaines installations (c'est le cas
    // du php.ini de LocalWP) : un -3.3 stocké ressort alors en
    // -3.2999999999999998 dans le JSON. Même nombre à la précision près,
    // mais 17 octets perdus par coordonnée — soit ~45 Ko sur la carte
    // générale et ses 1347 points. -1 demande la plus courte écriture qui
    // reconvertit à l'identique. On restaure la valeur d'origine aussitôt.
    $prev = @ini_set('serialize_precision', '-1');
    $json = wp_json_encode($data);
    if ($prev !== false) @ini_set('serialize_precision', $prev);

    echo '<div class="carte carte--' . esc_attr($mode) . '" id="' . esc_attr($id) . '"></div>';
    echo '<script type="application/json" class="carte-data" data-for="' . esc_attr($id) . '">'
        . $json . '</script>';
}

/* ==============================================================
 * MÉTABOX — pin déplaçable dans l'éditeur d'article
 * ============================================================ */
function lescarnets_geo_metabox() {
    add_meta_box('lescarnets_geo', 'Localisation du croquis', 'lescarnets_geo_metabox_html', 'post', 'side', 'default');
}
add_action('add_meta_boxes', 'lescarnets_geo_metabox');

function lescarnets_geo_metabox_html($post) {
    wp_nonce_field('lescarnets_geo_save', 'lescarnets_geo_nonce');
    $k    = lescarnets_meta_keys();
    $lat  = get_post_meta($post->ID, $k['lat'], true);
    $lon  = get_post_meta($post->ID, $k['lon'], true);
    $lieu = lescarnets_lieu($post->ID);
    // Contexte non modifiable ici, mais utile pour juger le point
    $prec   = get_post_meta($post->ID, 'carnet_precision', true);
    $mobile = get_post_meta($post->ID, 'carnet_mobile', true);
    ?>
    <?php if ($lieu !== ''): ?>
      <p class="lc-lieu">
        <span class="dashicons dashicons-location" aria-hidden="true"></span>
        <strong><?php echo esc_html($lieu); ?></strong>
        <?php if ($prec === 'carnet' || $mobile === '1'): ?>
          <span class="lc-lieu-note"><?php
            echo esc_html($mobile === '1'
                ? 'croquis fait en mouvement — point approché'
                : 'situé au carnet, pas au lieu exact');
          ?></span>
        <?php endif; ?>
      </p>
    <?php endif; ?>
    <p class="lc-aide">Déplacez le pin, ou cliquez sur la carte pour poser le point.</p>
    <div id="lc-admin-map"></div>
    <p class="lc-champs">
      <label>Lat<br><input type="text" id="lc-lat" name="lc_lat"
        value="<?php echo esc_attr($lat); ?>" inputmode="decimal" autocomplete="off"></label>
      <label>Lon<br><input type="text" id="lc-lon" name="lc_lon"
        value="<?php echo esc_attr($lon); ?>" inputmode="decimal" autocomplete="off"></label>
    </p>
    <p style="margin:8px 0 0"><button type="button" class="button" id="lc-clear">Retirer le point</button></p>
    <?php
}

function lescarnets_geo_save($post_id) {
    if (!isset($_POST['lescarnets_geo_nonce']) || !wp_verify_nonce($_POST['lescarnets_geo_nonce'], 'lescarnets_geo_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $k = lescarnets_meta_keys();
    foreach (array($k['lat'] => 'lc_lat', $k['lon'] => 'lc_lon') as $meta => $field) {
        // Champ ABSENT du POST : la métabox n'a pas été soumise, on ne
        // touche à rien. Sans ce garde-fou, un enregistrement dans un
        // contexte où la métabox n'est pas rendue effacerait une
        // coordonnée réelle — et 1347 croquis en sont pourvus.
        if (!isset($_POST[$field])) continue;

        $val = trim(wp_unslash($_POST[$field]));
        if ($val === '') {
            // Champ présent et vidé = « Retirer le point », geste explicite
            delete_post_meta($post_id, $meta);
            continue;
        }
        $clean = lescarnets_sanitize_coord($val, $meta);
        // Saisie invalide : on la refuse au lieu d'écraser par du vide
        if ($clean === '') continue;
        update_post_meta($post_id, $meta, $clean);
    }
}
add_action('save_post', 'lescarnets_geo_save');

/* Charge Leaflet + le script d'admin sur l'écran d'édition d'article */
function lescarnets_admin_geo_assets($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'post') return;
    wp_enqueue_style('leaflet');
    wp_enqueue_style('lescarnets-pin');
    wp_enqueue_style('lescarnets-admin-geo', get_template_directory_uri() . '/css/admin-geo.css',
        array('lescarnets-pin'), wp_get_theme()->get('Version'));
    wp_enqueue_script('leaflet');
    wp_enqueue_script('lescarnets-carte-admin', get_template_directory_uri() . '/js/carte-admin.js', array('leaflet'), wp_get_theme()->get('Version'), true);
    wp_localize_script('leaflet', 'LC_LEAFLET', array('img' => get_template_directory_uri() . '/vendor/leaflet/images/'));
}
add_action('admin_enqueue_scripts', 'lescarnets_admin_geo_assets');
