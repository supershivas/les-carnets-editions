<?php
/**
 * Formulaire de recherche — utilisé par get_search_form() (404, index).
 */
if (!defined('ABSPATH')) exit;
$id = 'recherche-' . wp_unique_id();
?>
<form role="search" method="get" class="searchform" action="<?php echo esc_url(home_url('/')); ?>">
  <label for="<?php echo esc_attr($id); ?>"><?php echo lescarnets_txt('recherche_label'); ?></label>
  <input type="search" id="<?php echo esc_attr($id); ?>" name="s"
         value="<?php echo esc_attr(get_search_query()); ?>"
         placeholder="<?php echo lescarnets_txt_attr('recherche_exemple'); ?>">
  <button type="submit"><?php echo lescarnets_txt('recherche_bouton'); ?></button>
</form>
