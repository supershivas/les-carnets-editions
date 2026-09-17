<?php
/**
 * Repli générique (archives, recherche, flux non couverts ailleurs).
 * Affiche une grille de croquis identique à la page destination.
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<main class="wrap" id="contenu" tabindex="-1">
  <header class="carnet-head">
    <h1 class="voice"><?php
      if (is_search())      echo lescarnets_txt('recherche_titre', get_search_query());
      elseif (is_archive()) the_archive_title();
      else                  echo lescarnets_txt('liste_titre');
    ?></h1>
  </header>

  <?php if (have_posts()): ?>
    <div class="croquis-grid">
      <?php while (have_posts()): the_post(); ?>
        <a class="croquis-cell" href="<?php the_permalink(); ?>">
          <span class="frame">
            <?php if (has_post_thumbnail()) the_post_thumbnail('carnet_vignette', array('loading'=>'lazy')); ?>
          </span>
          <?php $t = get_the_title(); ?>
          <span class="t<?php echo $t ? '' : ' mute'; ?> voice"><?php echo $t ? esc_html($t) : lescarnets_txt('croquis_sans_titre'); ?></span>
        </a>
      <?php endwhile; ?>
    </div>
    <?php the_posts_pagination(array('mid_size'=>1,'prev_text'=>'précédent','next_text'=>'suivant')); ?>
  <?php else: ?>
    <p class="voice"><?php echo lescarnets_txt('liste_vide'); ?></p>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
