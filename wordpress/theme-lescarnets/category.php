<?php
/**
 * Page destination (carnet) — en-tête + grille de croquis triable.
 * L'URL WordPress d'origine (/category/carnets/.../soudan/) est conservée.
 * On affiche tout le carnet sur une page pour que le tri couvre l'ensemble.
 */
if (!defined('ABSPATH')) exit;
get_header();

$term  = get_queried_object();

/* Catégorie racine « carnets » : afficher ses 1300 croquis à la suite était
 * illisible. L'URL reste servie (liens et référencement préservés, aucune
 * redirection), mais elle présente le sommaire par continent. */
if (lescarnets_is_root_carnets($term)) : ?>
<main class="wrap" id="contenu" tabindex="-1">
  <header class="carnet-head">
    <h1 class="voice"><?php echo esc_html($term->name); ?></h1>
  </header>
  <?php lescarnets_render_index(); ?>
</main>
<?php
    get_footer();
    return;
endif;

$years = lescarnets_carnet_years($term->term_id);
$anc   = array_reverse(get_ancestors($term->term_id, 'category'));

$croquis = new WP_Query(array(
    'cat'            => $term->term_id,
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',   // date par défaut, plus récent en tête
    'no_found_rows'  => true,
));
?>

<main class="wrap" id="contenu" tabindex="-1">

  <nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo lescarnets_txt('fil_accueil'); ?></a>
    <?php foreach ($anc as $aid): $a = get_category($aid);
          // La racine « Carnets » n'est plus une page utile : hors du fil
          if (lescarnets_is_root_carnets($a)) continue; ?>
      <span class="sep">·</span><a href="<?php echo esc_url(get_category_link($aid)); ?>"><?php echo esc_html($a->name); ?></a>
    <?php endforeach; ?>
    <span class="sep">·</span><span><?php echo esc_html($term->name); ?></span>
  </nav>

  <header class="carnet-head">
    <h1 class="voice"><?php echo esc_html($term->name); ?></h1>
    <div class="meta">
      <?php
      // $term->count ne compte que les articles directs, alors que la grille
      // inclut les sous-catégories : on affiche le nombre réellement affiché.
      $n = $croquis->post_count;
      echo lescarnets_txt('carnet_compte', $n);
      if ($years) echo ' · ' . esc_html($years);
      ?>
    </div>
    <?php if (trim(strip_tags($term->description))): ?>
      <div class="desc voice"><?php echo wp_kses_post($term->description); ?></div>
    <?php endif; ?>
  </header>

  <?php
  // Carte du carnet : tous les croquis géolocalisés de la destination
  if ($croquis->have_posts()) {
      $ids = wp_list_pluck($croquis->posts, 'ID');
      $pts = lescarnets_points_for($ids);
      if ($pts) lescarnets_render_map('carte-carnet', 'carnet', $pts);
  }
  ?>

  <?php if ($croquis->have_posts()): ?>

    <div class="sortbar">
      <span class="lbl"><?php echo lescarnets_txt('tri_label'); ?></span>
      <button type="button" data-sort="date" class="on"><?php echo lescarnets_txt('tri_date'); ?></button>
      <button type="button" data-sort="name"><?php echo lescarnets_txt('tri_nom'); ?></button>
    </div>

    <div class="croquis-grid">
      <?php while ($croquis->have_posts()): $croquis->the_post();
            $t = get_the_title();
      ?>
        <a class="croquis-cell" href="<?php the_permalink(); ?>"
           data-date="<?php echo esc_attr(get_the_time('U')); ?>"
           data-name="<?php echo esc_attr($t); ?>">
          <span class="frame">
            <?php if (has_post_thumbnail()) {
              // WordPress échappe les attributs : on passe le titre brut
              the_post_thumbnail('carnet_vignette', array('loading'=>'lazy','alt'=>$t));
            } ?>
          </span>
          <span class="t<?php echo $t ? '' : ' mute'; ?> voice"><?php echo $t ? esc_html($t) : lescarnets_txt('croquis_sans_titre'); ?></span>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>

  <?php else: ?>
    <p class="voice"><?php echo lescarnets_txt('carnet_vide'); ?></p>
  <?php endif; ?>

</main>

<?php get_footer(); ?>
