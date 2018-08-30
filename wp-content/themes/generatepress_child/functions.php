<?php
/**
 * GeneratePress child theme functions and definitions.
 *
 * Add your custom PHP in this file.
 * Only edit this file if you have direct access to it on your server (to fix errors if they happen).
 */

if (!defined('ABSPATH')) die();

add_filter('show_admin_bar', '__return_false');

function generatepress_child_enqueue_scripts() {
	if ( is_rtl() ) {
		wp_enqueue_style( 'generatepress-rtl', trailingslashit( get_template_directory_uri() ) . 'rtl.css' );
	}
	wp_enqueue_script( 'gpc-js', get_stylesheet_directory_uri() . '/js/gpc.js', array( 'jquery' ), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'generatepress_child_enqueue_scripts', 100 );


add_action('generate_after_footer_widgets', 'gpc_after_footer_widgets', 10);
function gpc_after_footer_widgets() { ?>
	<div class="gpc-footer-social wow bounceInUp">
		<span><i class="fa fa-facebook fa-2x" aria-hidden="true"></i></span>
		<span><i class="fa fa-twitter fa-2x" aria-hidden="true"></i></span>
		<span><i class="fa fa-youtube fa-2x" aria-hidden="true"></i></span>
	</div>

<?php }

/********************************************************************************************
*********** Animate and WoW ****************************************************************
********************************************************************************************/
//* Enqueue Animate.CSS and WOW.js
add_action( 'wp_enqueue_scripts', 'gpc_enqueue_scripts' );
function gpc_enqueue_scripts() {
	wp_enqueue_style( 'animate', get_stylesheet_directory_uri() . '/css/animate.min.css' );
	wp_enqueue_script( 'wow', get_stylesheet_directory_uri() . '/js/wow.min.js', array(), '', true );
}

//* Enqueue script to activate WOW.js
add_action('wp_enqueue_scripts', 'gpc_wow_init_in_footer' );
function gpc_wow_init_in_footer() {
	add_action( 'print_footer_scripts', 'wow_init' );
}

//* Add JavaScript before </body>
function wow_init() {
	?>
	<script type='text/javascript'>
		new WOW().init();
	</script>
	<?php
}
/********** END of Animate and Wow *********************************************************/
