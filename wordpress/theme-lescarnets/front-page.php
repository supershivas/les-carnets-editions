<?php
/**
 * Accueil — continents ordonnés par récence du dernier croquis,
 * destinations en vignettes, triables (date par défaut, ou nom).
 */
if (!defined('ABSPATH')) exit;
get_header();
?>

<main class="wrap" id="contenu" tabindex="-1">

  <div class="home-intro voice"><?php echo lescarnets_txt('accueil_intro'); ?></div>

  <?php lescarnets_render_index(); ?>

</main>

<?php get_footer(); ?>
