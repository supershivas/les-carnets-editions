<?php
/**
 * Résultats de recherche — même grille que les carnets, avec sous chaque
 * vignette le carnet et le lieu : sur une page de résultats, c'est ce qui
 * situe un croquis qu'on ne reconnaît pas au titre.
 */
if (!defined('ABSPATH')) exit;
get_header();

$terme = get_search_query();
$n     = (int) $GLOBALS['wp_query']->found_posts;
?>
<main class="wrap" id="contenu" tabindex="-1">

  <header class="carnet-head">
    <h1 class="voice"><?php echo lescarnets_txt('recherche_titre', $terme); ?></h1>
    <?php if ($terme !== '' && $n > 0): /* à zéro, le texte plus bas le dit mieux */ ?>
      <div class="meta"><?php
        echo (1 === $n) ? lescarnets_txt('recherche_compte_un') : lescarnets_txt('recherche_compte', $n);
      ?></div>
    <?php endif; ?>
  </header>

  <?php /* Pas de second champ ici quand il y a des résultats : celui de
           l'en-tête est juste au-dessus, déjà rempli. */ ?>

  <?php if (have_posts()): ?>

    <div class="croquis-grid">
      <?php while (have_posts()): the_post();
            $t   = get_the_title();
            $ctx = lescarnets_recherche_contexte(get_the_ID());
      ?>
        <a class="croquis-cell" href="<?php the_permalink(); ?>">
          <span class="frame">
            <?php if (has_post_thumbnail()) the_post_thumbnail('carnet_vignette', array('loading'=>'lazy','alt'=>$t)); ?>
          </span>
          <span class="t<?php echo $t ? '' : ' mute'; ?> voice"><?php
            echo $t ? esc_html($t) : lescarnets_txt('croquis_sans_titre');
          ?></span>
          <?php if ($ctx): ?><span class="ctx"><?php echo esc_html($ctx); ?></span><?php endif; ?>
        </a>
      <?php endwhile; ?>
    </div>

    <?php the_posts_pagination(array('mid_size'=>1,'prev_text'=>'précédent','next_text'=>'suivant')); ?>

  <?php else: ?>

    <div class="page-body">
      <p class="voice"><?php echo lescarnets_txt('recherche_vide'); ?></p>
      <div class="voice"><?php echo lescarnets_txt('recherche_conseil'); ?></div>
      <?php get_search_form(); ?>
      <p><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo lescarnets_txt('err404_retour'); ?></a></p>
    </div>

  <?php endif; ?>

</main>
<?php get_footer(); ?>
