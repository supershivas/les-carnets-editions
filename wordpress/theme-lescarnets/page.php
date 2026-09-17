<?php
/**
 * Pages statiques (À propos, Contact…).
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<main class="wrap" id="contenu" tabindex="-1">
  <?php while (have_posts()): the_post(); ?>
    <article class="page-body">
      <h1 class="voice"><?php the_title(); ?></h1>
      <div class="voice"><?php the_content(); ?></div>
    </article>
  <?php endwhile; ?>
</main>
<?php get_footer(); ?>
