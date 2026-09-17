<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>document.documentElement.className=document.documentElement.className.replace(/\bno-js\b/,'has-js');</script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#contenu">Aller au contenu</a>
<?php
// L'en-tête est masqué en feuilletage (le single a son propre chrome de galerie)
if (!is_single()) : ?>
<header class="site-head wrap">
  <a class="site-title" href="<?php echo esc_url(home_url('/')); ?>">
    <?php echo lescarnets_txt('site_titre'); ?> <span><?php echo lescarnets_txt('site_soustitre'); ?></span>
  </a>
  <nav class="site-nav" aria-label="Menu principal">
    <?php
    if (has_nav_menu('principal')) {
        wp_nav_menu(array('theme_location'=>'principal','container'=>false,'menu_class'=>'menu','depth'=>1));
    } else {
        // Repli si aucun menu n'est encore défini dans l'admin
        echo '<a href="'.esc_url(home_url('/a-propos/')).'">À propos</a>';
        echo '<a href="'.esc_url(home_url('/contact/')).'">Contact</a>';
    }
    ?>
  </nav>
</header>
<?php endif; ?>
