<?php
/**
 * La recherche.
 *
 * WordPress ne cherche que dans le titre et le texte de l'article. Ici, un
 * croquis porte aussi son lieu (méta `carnet_lieu`) et son carnet (la
 * catégorie) : chercher « Busua » ou « Soudan » ne devait pas revenir vide
 * sous prétexte que le mot n'est pas dans la légende.
 *
 * Chaque mot tapé doit se retrouver quelque part — titre, texte, lieu ou
 * carnet — mais pas forcément au même endroit : « café rome » trouve un
 * croquis intitulé « Le café du matin » classé dans le carnet Roma.
 */

if (!defined('ABSPATH')) exit;

/**
 * La recherche ne porte que sur les croquis, et par pages de 48.
 */
function lescarnets_recherche_query($query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_search()) return;

    $query->set('post_type', 'post');
    $query->set('posts_per_page', 48);
    $query->set('orderby', 'date');
    $query->set('order', 'DESC');
}
add_action('pre_get_posts', 'lescarnets_recherche_query');

/**
 * Les mots de la recherche, au plus huit — au-delà, la requête coûte plus
 * qu'elle ne rend. Une recherche entre guillemets reste d'un seul tenant.
 */
function lescarnets_recherche_mots($terme) {
    $terme = trim($terme);
    if ('' === $terme) return array();

    if (preg_match('/^"(.+)"$/s', $terme, $m)) {
        return array(trim($m[1]));
    }

    $mots = preg_split('/[\s,]+/u', $terme, -1, PREG_SPLIT_NO_EMPTY);
    return array_slice($mots, 0, 8);
}

/**
 * Remplace la clause de recherche : mêmes mots, quatre champs.
 */
function lescarnets_recherche_clause($search, $query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_search()) return $search;

    $mots = lescarnets_recherche_mots($query->get('s'));
    if (empty($mots)) return $search;

    global $wpdb;
    $k     = lescarnets_meta_keys();
    $morceaux = array();

    foreach ($mots as $mot) {
        $like = '%' . $wpdb->esc_like($mot) . '%';
        $morceaux[] = $wpdb->prepare(
            "(
                {$wpdb->posts}.post_title LIKE %s
             OR {$wpdb->posts}.post_content LIKE %s
             OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} lcm
                    WHERE lcm.post_id = {$wpdb->posts}.ID
                      AND lcm.meta_key = %s
                      AND lcm.meta_value LIKE %s
                )
             OR EXISTS (
                    SELECT 1 FROM {$wpdb->term_relationships} lctr
                    INNER JOIN {$wpdb->term_taxonomy} lctt
                            ON lctt.term_taxonomy_id = lctr.term_taxonomy_id
                           AND lctt.taxonomy = 'category'
                    INNER JOIN {$wpdb->terms} lct ON lct.term_id = lctt.term_id
                    WHERE lctr.object_id = {$wpdb->posts}.ID
                      AND lct.name LIKE %s
                )
            )",
            $like, $like, $k['lieu'], $like, $like
        );
    }

    // Les EXISTS ne multiplient pas les lignes : pas de GROUP BY à ajouter,
    // donc pas de doublon quand un mot est à la fois dans le titre et le lieu.
    return ' AND (' . implode(' AND ', $morceaux) . ') ';
}
add_filter('posts_search', 'lescarnets_recherche_clause', 10, 2);

/**
 * Le lieu d'un croquis, pour l'afficher sous la vignette d'un résultat :
 * c'est souvent lui qui explique pourquoi le croquis est là.
 */
function lescarnets_recherche_contexte($post_id) {
    $bits = array();

    $dest = lescarnets_post_destination($post_id);
    if ($dest) $bits[] = $dest->name;

    $lieu = lescarnets_lieu($post_id);
    if ($lieu && (!$dest || $lieu !== $dest->name)) $bits[] = $lieu;

    return implode(' · ', $bits);
}
