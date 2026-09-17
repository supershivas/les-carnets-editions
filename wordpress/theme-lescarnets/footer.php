<?php if (!defined('ABSPATH')) exit; ?>
<?php if (!is_single()) : ?>
<footer class="site-foot wrap">
  <div class="brand voice"><?php echo lescarnets_txt('pied_marque'); ?> <span><?php echo lescarnets_txt('pied_mention'); ?></span></div>
  <nav aria-label="Menu de pied de page">
    <?php
    if (has_nav_menu('pied')) {
        wp_nav_menu(array('theme_location'=>'pied','container'=>false,'menu_class'=>'menu','depth'=>1));
    } else {
        echo '<a href="'.esc_url(home_url('/a-propos/')).'">À propos</a>';
        echo '<a href="'.esc_url(home_url('/contact/')).'">Contact</a>';
        echo '<a href="'.esc_url(home_url('/politique-de-confidentialite/')).'">Confidentialité</a>';
    }
    ?>
  </nav>
</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
