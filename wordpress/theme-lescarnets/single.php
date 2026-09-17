<?php
/**
 * Feuilletage — un croquis par écran, défilement natif (scroll-snap).
 * La légende est sous l'image. Une petite carte suit le croquis courant.
 *
 * Un carnet peut dépasser 100 croquis : seules les planches proches du
 * croquis demandé sont rendues ici. Les autres sont des « stubs » de même
 * hauteur, hydratés en AJAX par js/feuilletage.js à l'approche du lecteur.
 */
if (!defined('ABSPATH')) exit;
get_header();

$current_id = get_the_ID();
$dest = lescarnets_post_destination($current_id);

// On ne récupère que les IDs : la liste complète peut être longue, et on
// n'a besoin du contenu que pour la fenêtre rendue.
$args = array(
    'posts_per_page'      => -1,
    'orderby'             => 'date',
    'order'               => 'ASC',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
    'fields'              => 'ids',
);
if ($dest) $args['cat'] = $dest->term_id;
else       $args['p']   = $current_id;

$ids = get_posts($args);
if (empty($ids)) $ids = array($current_id);
$total = count($ids);

$start = array_search($current_id, $ids, true);
if ($start === false) $start = 0;

// Amorce les caches en 3 requêtes (articles + métas + termes) au lieu d'une
// par appel à get_the_title() / get_permalink() / lescarnets_coords().
lescarnets_prime_caches($ids);

$win = lescarnets_plate_window();
$lo  = max(0, $start - $win);
$hi  = min($total - 1, $start + $win);

$dest_link = $dest ? get_category_link($dest->term_id) : home_url('/');

$has_any_geo = false;
foreach ($ids as $id) { if (lescarnets_coords($id)) { $has_any_geo = true; break; } }
?>

<div class="reader" id="contenu" tabindex="-1" data-start="<?php echo intval($start); ?>">

  <?php foreach ($ids as $i => $pid):
      $title    = get_the_title($pid);
      $co       = lescarnets_coords($pid);
      $rendered = ($i >= $lo && $i <= $hi);
  ?>
    <section class="plate<?php echo $rendered ? '' : ' is-stub'; ?>"
             data-i="<?php echo intval($i); ?>"
             data-id="<?php echo intval($pid); ?>"
             data-url="<?php echo esc_url(get_permalink($pid)); ?>"
             data-title="<?php echo esc_attr($title); ?>"
             <?php if ($co): ?>data-lat="<?php echo esc_attr($co['lat']); ?>" data-lon="<?php echo esc_attr($co['lon']); ?>"<?php endif; ?>>
      <?php
      if ($rendered) {
          // Un seul <h1> par page : le croquis demandé
          echo lescarnets_plate_inner_html(
              get_post($pid),
              $dest,
              ($i === $start) ? 'h1' : 'h2',
              abs($i - $start) <= 1   // les voisins immédiats en eager
          );
      } else {
          echo '<span class="stub-mark" aria-hidden="true"></span>';
          echo '<span class="screen-reader-text">Croquis ' . intval($i + 1)
             . ' — chargement</span>';
      }
      ?>
    </section>
  <?php endforeach; ?>

  <?php /* Le lien de retour porte déjà le nom du carnet : un second
           libellé à droite faisait doublon, il est retiré. */ ?>
  <div class="reader-top">
    <a class="back" href="<?php echo esc_url($dest_link); ?>">← <?php echo $dest ? esc_html($dest->name) : lescarnets_txt('lecture_retour'); ?></a>
  </div>

  <div class="reader-fade top"></div>
  <div class="reader-fade bottom"></div>

  <?php if ($has_any_geo): ?>
    <button type="button" class="reader-map-toggle" id="reader-map-toggle" aria-label="Afficher la carte">
      <span class="dot"></span> <?php echo lescarnets_txt('lecture_carte'); ?>
    </button>
    <div class="reader-map" id="reader-map" aria-label="Localisation du croquis"></div>
  <?php endif; ?>

  <nav class="reader-rail" id="reader-rail" aria-label="Progression dans le carnet"></nav>
  <div class="reader-count" id="reader-count"></div>
  <div class="reader-hint" id="reader-hint"><?php echo lescarnets_txt('lecture_indice'); ?></div>

</div>

<?php get_footer(); ?>
