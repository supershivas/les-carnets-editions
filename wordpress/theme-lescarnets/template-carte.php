<?php
/**
 * Template Name: Carte générale
 *
 * Créez une page (ex. « Carte »), et dans « Attributs de page » →
 * « Modèle », choisissez « Carte générale ».
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<main class="wrap" id="contenu" tabindex="-1">
  <header class="carnet-head">
    <h1 class="voice"><?php single_post_title(); ?></h1>
    <?php
    $ids = lescarnets_all_geo_ids();
    $pts = lescarnets_points_for($ids);
    ?>
    <div class="meta"><?php echo lescarnets_txt('carte_compte', count($pts)); ?></div>
  </header>

  <?php lescarnets_render_map('carte-generale', 'general', $pts); ?>

  <?php while (have_posts()): the_post(); if (trim(get_the_content())): ?>
    <div class="page-body voice" style="padding-top:32px"><?php the_content(); ?></div>
  <?php endif; endwhile; ?>
</main>
<?php get_footer(); ?>
