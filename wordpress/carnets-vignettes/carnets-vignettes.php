<?php
/**
 * Plugin Name: Les Carnets — image du carnet
 * Description: Choisir le croquis qui représente chaque carnet sur l'accueil de lescarnets.fr, au lieu de subir le dernier publié.
 * Version:     1.0
 * Author:      Jérôme Agostini
 * License:     GPL-2.0-or-later
 *
 * Un carnet = une catégorie feuille (ex. « carnets / afrique / senegal »).
 * Le choix est rangé dans un term meta ; sans choix, le comportement d'origine
 * (le dernier croquis publié) reste en place.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CARNETS_VIGNETTE_META = 'carnet_vignette';

/* -------------------------------------------------------------------------
 * Le fonds : quelles catégories sont des carnets
 * ---------------------------------------------------------------------- */

/**
 * Les carnets : les catégories qui portent des croquis et n'ont pas d'enfant.
 *
 * @return WP_Term[]
 */
function carnets_vignettes_carnets() {
	$termes = get_categories(
		array(
			'hide_empty' => true,
			'orderby'    => 'name',
		)
	);

	$parents = array();
	foreach ( $termes as $t ) {
		if ( $t->parent ) {
			$parents[ $t->parent ] = true;
		}
	}

	return array_values(
		array_filter(
			$termes,
			static function ( $t ) use ( $parents ) {
				return empty( $parents[ $t->term_id ] );
			}
		)
	);
}

/**
 * Le carnet d'un croquis : sa catégorie la plus profonde.
 *
 * @param int $post_id
 * @return int 0 si on n'en trouve pas.
 */
function carnets_vignettes_carnet_du_croquis( $post_id ) {
	$cats = get_the_category( $post_id );
	if ( empty( $cats ) ) {
		return 0;
	}

	$profondeur = static function ( $term_id ) {
		$n = 0;
		while ( $term_id && $n < 10 ) {
			$t = get_term( $term_id, 'category' );
			if ( ! $t || is_wp_error( $t ) || ! $t->parent ) {
				break;
			}
			$term_id = $t->parent;
			$n++;
		}
		return $n;
	};

	$meilleur = 0;
	$score    = -1;
	foreach ( $cats as $c ) {
		$d = $profondeur( $c->term_id );
		if ( $d > $score ) {
			$score    = $d;
			$meilleur = $c->term_id;
		}
	}

	return $meilleur;
}

/**
 * L'image choisie pour un carnet, ou 0 si aucune.
 *
 * @param int $term_id
 * @return int identifiant de la pièce jointe
 */
function carnets_vignettes_choix( $term_id ) {
	$id = (int) get_term_meta( $term_id, CARNETS_VIGNETTE_META, true );

	return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
}

/**
 * L'image affichée aujourd'hui pour un carnet : le choix, sinon le dernier publié.
 *
 * @param int $term_id
 * @return int
 */
function carnets_vignettes_courante( $term_id ) {
	$id = carnets_vignettes_choix( $term_id );
	if ( $id ) {
		return $id;
	}

	$derniers = get_posts(
		array(
			'numberposts'      => 1,
			'cat'              => $term_id,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
			'fields'           => 'ids',
		)
	);

	return $derniers ? (int) get_post_thumbnail_id( $derniers[0] ) : 0;
}

/* -------------------------------------------------------------------------
 * L'accueil : substituer l'image choisie, sans toucher au thème
 * ---------------------------------------------------------------------- */

add_filter( 'post_thumbnail_id', 'carnets_vignettes_filtre_accueil', 10, 2 );

/**
 * Sur l'accueil, la vignette d'un carnet est celle qu'on a choisie.
 *
 * Le thème affiche la miniature du dernier croquis publié ; on la remplace
 * au vol quand un choix existe pour le carnet de ce croquis.
 *
 * @param int|false           $thumbnail_id
 * @param int|WP_Post|null    $post
 * @return int|false
 */
function carnets_vignettes_filtre_accueil( $thumbnail_id, $post ) {
	if ( is_admin() || ! is_front_page() ) {
		return $thumbnail_id;
	}

	$post = get_post( $post );
	if ( ! $post ) {
		return $thumbnail_id;
	}

	$carnet = carnets_vignettes_carnet_du_croquis( $post->ID );
	if ( ! $carnet ) {
		return $thumbnail_id;
	}

	$choix = carnets_vignettes_choix( $carnet );

	return $choix ? $choix : $thumbnail_id;
}

/* -------------------------------------------------------------------------
 * L'écran « Images des carnets »
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', 'carnets_vignettes_menu' );

function carnets_vignettes_menu() {
	add_submenu_page(
		'edit.php',
		'Images des carnets',
		'Images des carnets',
		'edit_others_posts',
		'carnets-vignettes',
		'carnets_vignettes_ecran'
	);
}

function carnets_vignettes_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'carnets-vignettes' ), $args ), admin_url( 'edit.php' ) );
}

function carnets_vignettes_ecran() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Accès refusé.' );
	}

	carnets_vignettes_traiter_choix();

	$carnet = isset( $_GET['carnet'] ) ? (int) $_GET['carnet'] : 0;

	echo '<div class="wrap">';
	if ( $carnet ) {
		carnets_vignettes_ecran_carnet( $carnet );
	} else {
		carnets_vignettes_ecran_liste();
	}
	echo '</div>';
}

/**
 * Enregistre un choix (ou son retrait) puis renvoie sur l'écran d'où l'on vient.
 */
function carnets_vignettes_traiter_choix() {
	if ( ! isset( $_GET['choisir'] ) && ! isset( $_GET['defaut'] ) ) {
		return;
	}

	$carnet = isset( $_GET['carnet'] ) ? (int) $_GET['carnet'] : 0;
	if ( ! $carnet || ! check_admin_referer( 'carnet_vignette_' . $carnet ) ) {
		return;
	}

	if ( isset( $_GET['defaut'] ) ) {
		delete_term_meta( $carnet, CARNETS_VIGNETTE_META );
		$message = 'defaut';
	} else {
		$croquis = (int) $_GET['choisir'];
		$image   = get_post_thumbnail_id( $croquis );
		if ( ! $image ) {
			return;
		}
		update_term_meta( $carnet, CARNETS_VIGNETTE_META, $image );
		$message = 'choisi';
	}

	wp_safe_redirect( carnets_vignettes_url( array( 'carnet' => $carnet, 'fait' => $message ) ) );
	exit;
}

function carnets_vignettes_ecran_liste() {
	$carnets = carnets_vignettes_carnets();

	echo '<h1>Images des carnets</h1>';
	echo '<p class="description" style="max-width:46em">Chaque carnet montre une image sur l\'accueil. '
		. 'Par défaut c\'est le dernier croquis publié — il change donc tout seul. '
		. 'Choisissez-en une et elle ne bouge plus.</p>';

	$groupes = array();
	foreach ( $carnets as $c ) {
		$parent = $c->parent ? get_term( $c->parent, 'category' ) : null;
		$nom    = ( $parent && ! is_wp_error( $parent ) ) ? $parent->name : 'Sans région';
		$groupes[ $nom ][] = $c;
	}
	ksort( $groupes );

	foreach ( $groupes as $region => $liste ) {
		echo '<h2>' . esc_html( $region ) . '</h2>';
		echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:18px;margin-bottom:28px">';
		foreach ( $liste as $c ) {
			$image = carnets_vignettes_courante( $c->term_id );
			$fixe  = (bool) carnets_vignettes_choix( $c->term_id );
			echo '<a href="' . esc_url( carnets_vignettes_url( array( 'carnet' => $c->term_id ) ) ) . '" '
				. 'style="display:block;text-decoration:none;color:inherit">';
			echo '<span style="display:block;background:#f0f0f1;aspect-ratio:4/3;overflow:hidden">';
			if ( $image ) {
				echo wp_get_attachment_image( $image, 'medium', false, array( 'style' => 'width:100%;height:100%;object-fit:cover' ) );
			}
			echo '</span>';
			echo '<strong style="display:block;margin-top:6px">' . esc_html( $c->name ) . '</strong>';
			echo '<span style="color:#646970;font-size:12px">' . (int) $c->count . ' croquis · '
				. ( $fixe ? 'image choisie' : 'dernier publié' ) . '</span>';
			echo '</a>';
		}
		echo '</div>';
	}
}

function carnets_vignettes_ecran_carnet( $term_id ) {
	$carnet = get_term( $term_id, 'category' );
	if ( ! $carnet || is_wp_error( $carnet ) ) {
		echo '<h1>Carnet introuvable</h1>';
		return;
	}

	$choix = carnets_vignettes_choix( $term_id );

	echo '<h1>' . esc_html( $carnet->name ) . '</h1>';
	echo '<p><a href="' . esc_url( carnets_vignettes_url() ) . '">← Tous les carnets</a></p>';

	if ( isset( $_GET['fait'] ) ) {
		$txt = ( 'defaut' === $_GET['fait'] ) ? 'Retour au dernier croquis publié.' : 'Image du carnet enregistrée.';
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $txt ) . '</p></div>';
	}

	echo '<p class="description">Cliquez le croquis qui doit représenter ce carnet sur l\'accueil.</p>';

	if ( $choix ) {
		$retour = wp_nonce_url(
			carnets_vignettes_url( array( 'carnet' => $term_id, 'defaut' => 1 ) ),
			'carnet_vignette_' . $term_id
		);
		echo '<p><a class="button" href="' . esc_url( $retour ) . '">Revenir au dernier croquis publié</a></p>';
	}

	$croquis = get_posts(
		array(
			'numberposts' => -1,
			'cat'         => $term_id,
			'orderby'     => 'date',
			'order'       => 'ASC',
		)
	);

	echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-top:16px">';
	foreach ( $croquis as $p ) {
		$image = (int) get_post_thumbnail_id( $p->ID );
		if ( ! $image ) {
			continue;
		}
		$actif = ( $image === $choix );
		$lien  = wp_nonce_url(
			carnets_vignettes_url( array( 'carnet' => $term_id, 'choisir' => $p->ID ) ),
			'carnet_vignette_' . $term_id
		);
		echo '<a href="' . esc_url( $lien ) . '" style="display:block;text-decoration:none;color:inherit;'
			. 'outline:' . ( $actif ? '3px solid #2271b1' : '1px solid #dcdcde' ) . ';outline-offset:2px">';
		echo wp_get_attachment_image( $image, 'medium', false, array( 'style' => 'width:100%;height:150px;object-fit:cover;display:block' ) );
		echo '<span style="display:block;padding:6px;font-size:12px;line-height:1.3">'
			. esc_html( get_the_title( $p ) )
			. '<br><span style="color:#646970">' . esc_html( get_the_date( 'j M Y', $p ) )
			. ( $actif ? ' · image du carnet' : '' ) . '</span></span>';
		echo '</a>';
	}
	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Rappel sur l'écran de la catégorie
 * ---------------------------------------------------------------------- */

add_action( 'category_edit_form_fields', 'carnets_vignettes_champ_categorie' );

function carnets_vignettes_champ_categorie( $term ) {
	$image = carnets_vignettes_courante( $term->term_id );
	$fixe  = (bool) carnets_vignettes_choix( $term->term_id );
	?>
	<tr class="form-field">
		<th scope="row">Image du carnet</th>
		<td>
			<?php if ( $image ) : ?>
				<?php echo wp_get_attachment_image( $image, 'thumbnail', false, array( 'style' => 'display:block;margin-bottom:8px' ) ); ?>
			<?php endif; ?>
			<p>
				<?php echo $fixe ? 'Image choisie.' : 'Par défaut : le dernier croquis publié.'; ?>
				<a href="<?php echo esc_url( carnets_vignettes_url( array( 'carnet' => $term->term_id ) ) ); ?>">Changer l'image de ce carnet</a>
			</p>
		</td>
	</tr>
	<?php
}
