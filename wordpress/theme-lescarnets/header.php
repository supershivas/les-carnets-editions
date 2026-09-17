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
  <form class="head-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="head-s"><?php echo lescarnets_txt('recherche_label'); ?></label>
    <input type="search" id="head-s" name="s" value="<?php echo esc_attr(get_search_query()); ?>"
           placeholder="<?php echo lescarnets_txt_attr('recherche_exemple'); ?>">
    <button type="submit" aria-label="<?php echo lescarnets_txt_attr('recherche_bouton'); ?>">
      <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">
        <circle cx="8.5" cy="8.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.6"/>
        <line x1="12.8" y1="12.8" x2="17.5" y2="17.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>
  </form>
</header>
<?php endif; ?>
