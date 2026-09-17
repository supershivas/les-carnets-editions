<?php
/**
 * Page introuvable — au lieu du repli index.php (« Rien à afficher »),
 * on propose une recherche et un retour vers les carnets.
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<main class="wrap" id="contenu" tabindex="-1">
  <header class="carnet-head">
    <h1 class="voice"><?php echo lescarnets_txt('err404_titre'); ?></h1>
    <div class="desc voice"><?php echo lescarnets_txt('err404_texte'); ?></div>
  </header>

  <div class="page-body">
    <?php get_search_form(); ?>
    <p><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo lescarnets_txt('err404_retour'); ?></a></p>
  </div>
</main>
<?php get_footer(); ?>
