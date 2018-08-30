<?php

/**
 * Theme fine-tuning tools.
 *
 */
class GPC_Tools{

	/**
	 * Make clone magic method private, so nobody can clone instance.
	 */
	private function __clone() {}

	/**
	 * Make sleep magic method private, so nobody can serialize instance.
	 */
	private function __sleep() {}

	/**
	 * Make wakeup magic method private, so nobody can unserialize instance.
	 */
	private function __wakeup() {}

	/**
	 * Call this method to get singleton
	 */
	public static function getInstance(){
	  	static $instance = false;
	  	if( $instance === false ){
			// Late static binding (PHP 5.3+)
			$instance = new static();
	  	}

	  	return $instance;
	}

	/**
	 * Make constructor private, so nobody can call "new Class".
	 */
	private function __construct() {

		$this->logout_page = '/';

		// Remove wordpress admin bar in the frontend.
		add_filter( 'show_admin_bar', '__return_false' );

		// Redirect to home page after logout.
		add_action( 'wp_logout', array( $this, 'gpc_logout_redirect' ) );

		// Add custom class to body element.
		add_filter( 'body_class', array( $this, 'gpc_add_body_classes' ) );

		// Disable emojis and dashicons
		add_action( 'init', array( $this, 'gpc_disable_emojis' ) );

		// Spinner when downloading the page.
		add_action( 'wp_head', array( $this, 'gpc_show_spinner' ), 1 );
		add_action( 'wp_footer', array( $this, 'gpc_remove_spinner' ), PHP_INT_MAX );

		// Add slider in footer with high priority and later move it into place using jQuery
		add_action( 'wp_footer', array( $this, 'gpc_add_slider' ), 1 );

		// Combine all wordpress native js files
		//add_action( 'wp_enqueue_scripts', array( $this, 'gpc_combine_wp_scripts' ) );

		// Add async and defer tags to scripts.
		add_filter( 'script_loader_tag', array( $this, 'gpc_add_async_defer' ), PHP_INT_MAX, 2 );

		// Enque all scripts in footer
		add_action( 'wp_enqueue_scripts', array( $this, 'gpc_scripts_in_footer' ), 1);

		// Cache busting css/js
		add_filter( 'script_loader_src', array( $this, 'gpc_filename_based_cache_busting' ), PHP_INT_MAX );
		add_filter( 'style_loader_src', array( $this, 'gpc_filename_based_cache_busting' ), PHP_INT_MAX );
		add_action( 'init', array( $this, 'gpc_add_rewrite_rule' ) );

		// Add inline critical css
		add_action( 'wp_head', array( $this 'gpc_add_critical_css' ), 1 );

		// Asynchronously load css (google page speed insights)
		//add_action( 'wp_print_styles', array( $this, 'gpc_async_css' ), PHP_INT_MAX);
		//add_action( 'generate_before_header', array( $this, 'gpc_async_css_script' ), 1);

		// Asynchronously load css (filament group)
		add_action( 'wp_print_styles', array( $this, 'gpc_async_css' ) );
	}

	// Redirect to home page after logout.
	function gpc_logout_redirect() {
		wp_redirect( home_url($this->logout_page) );
		exit();
	}

	// Add custom class to body element.
	function gpc_add_body_classes($classes) {
		global $post;
		$post_id = $post->ID;
		$classes[] = get_post_meta($post_id, 'body_class', true);
		return $classes;
	}

	// Disable emojis and dashicons
	function gpc_disable_emojis() {
		if( is_admin() ) return;
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	}

	// Show Spinner when downloading the page.
	function gpc_show_spinner() {
		?>
		<script type='text/javascript'>document.documentElement.className = 'js gpc-loading';</script>
		<style type='text/css'>body{-webkit-transition: opacity 0.1s ease-in;transition: opacity 0.1s ease-in;} html.gpc-loading body{height: 100vh; opacity: 0;-webkit-transition:none;transition:none;}
		<?php if( is_page( array('dummy-page') ) ) : ?>
			@keyframes spinner{to {transform: rotate(360deg);}} html.gpc-loading:after {content: '';box-sizing: border-box;position:absolute;top: 50%;left: 50%;width: 50px;height: 50px;margin-top: -10px;margin-left: -10px;border-radius: 50%;border: 2px solid #ccc;border-top-color: #2EA3F2;animation: spinner .6s linear infinite;}
		<?php endif; ?>
		</style>
		<?php
	}

	// Remove spinner after downloading the page and append slider if present.
	function gpc_remove_spinner() {
		/*echo '<script type="text/javascript">window.onload = function(e){document.documentElement.classList.remove("gpc-loading");}</script>';*/
		?>
		<script>document.addEventListener("readystatechange", function(e){if(document.readyState != "loading")document.documentElement.classList.remove("gpc-loading");})</script>
		<script>jQuery('#gpc-slider').appendTo('#gpc-home-banner');</script>
		<?php
	}

	// Add slider in footer with high priority and later move it into place using jQuery
	function gpc_add_slider() {
		if( is_front_page() ) {
		?>
		<div id="gpc-slider">
			<?php //echo do_shortcode('[rev_slider alias="news-gallery5"]'); ?>
		</div>
		<?php
		}
	}

	/**********************************************************************************
	**************** Enqueue and dequeue scripts and styles ***************************
	**********************************************************************************/
	// Combine all wordpress native js files
	function gpc_combine_wp_scripts() {
		global $concatenate_scripts, $compress_scripts, $compress_css;
		$concatenate_scripts = 1;
		$compress_scripts = 1;
		$compress_css = 1;
		define('ENFORCE_GZIP', true);
	}

	// Add async and defer tags to scripts.
	function gpc_add_async_defer($tag, $handle) {
		if( is_admin() ) return $tag;
		$async = array();
		$normal = array('jquery-core');
		if ( in_array( $handle, $async ) ) {
			$tag = str_replace( ' src', ' async=\'async\' src', $tag );
		} elseif ( in_array( $handle, $normal ) ) {
			$tag = $tag;	// no changes
		} else {
			$tag = str_replace( ' src', ' defer=\'defer\' src', $tag );
		}
		return $tag;
	}

	// Enque all scripts in footer
	function gpc_scripts_in_footer() {
		if( is_admin() ) return;

		// Custom Scripting to Move JavaScript from the Head to the Footer
		remove_action('wp_head', 'wp_print_scripts');
		remove_action('wp_head', 'wp_print_head_scripts', 9);
		remove_action('wp_head', 'wp_enqueue_scripts', 1);

		add_action('wp_footer', 'wp_print_scripts');
		add_action('wp_footer', 'wp_print_head_scripts');
		add_action('wp_footer', 'wp_enqueue_scripts');
	}

	/**
	 * Moves the `ver` query string of the source into
	 * the filename. Doesn't change admin scripts/styles and sources
	 * with more than the `ver` arg.
	 *
	 * @param  string $src The original source.
	 * @return string
	 */
	add_filter( 'script_loader_src', 'gpc_filename_based_cache_busting', PHP_INT_MAX );
	add_filter( 'style_loader_src', 'gpc_filename_based_cache_busting', PHP_INT_MAX );
	function gpc_filename_based_cache_busting( $src ) {
		// Don't touch admin scripts.
		if ( is_admin() ) return $src;

		$_src = $src;
		$htp = is_ssl() ? 'https:' : 'http:';
		if ( '//' === substr( $_src, 0, 2 ) ) $_src = $htp . $_src;

		$_src = parse_url( $_src );
		// Give up if malformed URL.
		if ( false === $_src ) return $src;

		// Check if it's a local URL.
		$wp = parse_url( home_url() );
		if ( isset( $_src['host'] ) && $_src['host'] !== $wp['host'] ) return $src;

		// Add version number to the filename
		/*return preg_replace(
			'/\.(js|css)\?ver=(.+)$/',
			'.$2.$1',
			$src
		);*/

		// Add timestamp to the filename
		$file_path = $_SERVER['DOCUMENT_ROOT'] . $_src['path'];
		return preg_replace(
			'/\.(min.js|min.css|js|css)($|\?.*$)/',
			'.' . filemtime($file_path) . '.$1',
			$src
		);
	}

	// Modrewrite rule for cachebusting css/js file names
	function gpc_add_rewrite_rule()
	{
		// Add cookie to indicate full stylesheet is already downloaded
		setcookie( 'gpc_fullCSS', 'true', 7 * DAYS_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );

	    // Remember to flush the rules once manually after you added this code!
	    add_rewrite_rule(
	        // The regex to match the incoming URL
	        '(.+)\.([0-9]+)\.(min.js|min.css|js|css)$',
	        // The URL we would like to actually fetch
	        '$1.$3',
	        // This is a rather specific URL, so we add it to the top of the list
	        // Otherwise, the "catch-all" rules at the bottom (for pages and attachments) will "win"
	        'top'
	    );
	}

	// Add inline critical css
	function gpc_add_critical_css() {
		//if ( isset($_COOKIE['gpc_fullCSS']) && $_COOKIE['gpc_fullCSS'] === 'true' ) return;
		echo '<style type="text/css">';
		echo file_get_contents(get_stylesheet_directory_uri() . "/critical.css");
		echo '</style>';
	}

	// Asynchronously load css (google page speed insights)
	function gpc_async_css( $handles = false ) {
		if( is_admin() ) return;
		global $wp_styles;
		if ( ! ( $wp_styles instanceof WP_Styles ) ) {
			if ( ! $handles ) return array();
		}
		echo '<noscript id="deferred-styles">';
		wp_styles()->do_items();
		echo '</noscript>';
	}

	// Asynchronously load css script (google page speed insights)
	function gpc_async_css_script() {
		?>
		<script>var loadDeferredStyles = function() {var addStylesNode = document.getElementById('deferred-styles');var replacement = document.createElement('strong');replacement.innerHTML = addStylesNode.textContent; document.body.appendChild(replacement); addStylesNode.parentElement.removeChild(addStylesNode);};var raf = requestAnimationFrame || mozRequestAnimationFrame || webkitRequestAnimationFrame || msRequestAnimationFrame;if(raf) raf(function() { window.setTimeout(loadDeferredStyles, 0); });else window.addEventListener('load', loadDeferredStyles);
		</script>
		<?php
	}

	// Asynchronously load css (filament group)
	function gpc_async_css() {
		$jscript = '<script>//! loadCSS. [c]2017 Filament Group, Inc. MIT License.
		!function(a){"use strict";var b=function(b,c,d){function e(a){return h.body?a():void setTimeout(function(){e(a)})}function f(){i.addEventListener&&i.removeEventListener("load",f),i.media=d||"all"}var g,h=a.document,i=h.createElement("link");if(c)g=c;else{var j=(h.body||h.getElementsByTagName("head")[0]).childNodes;g=j[j.length-1]}var k=h.styleSheets;i.rel="stylesheet",i.href=b,i.media="only x",e(function(){g.parentNode.insertBefore(i,c?g:g.nextSibling)});var l=function(a){for(var b=i.href,c=k.length;c--;)if(k[c].href===b)return a();setTimeout(function(){l(a)})};return i.addEventListener&&i.addEventListener("load",f),i.onloadcssdefined=l,l(f),i};"undefined"!=typeof exports?exports.loadCSS=b:a.loadCSS=b}("undefined"!=typeof global?global:this);
		//! loadCSS rel=preload polyfill. [c]2017 Filament Group, Inc. MIT License.
		!function(a){if(a.loadCSS){var b=loadCSS.relpreload={};if(b.support=function(){try{return a.document.createElement("link").relList.supports("preload")}catch(b){return!1}},b.poly=function(){for(var b=a.document.getElementsByTagName("link"),c=0;c<b.length;c++){var d=b[c];"preload"===d.rel&&"style"===d.getAttribute("as")&&(a.loadCSS(d.href,d,d.getAttribute("media")),d.rel=null)}},!b.support()){b.poly();var c=a.setInterval(b.poly,300);a.addEventListener&&a.addEventListener("load",function(){b.poly(),a.clearInterval(c)}),a.attachEvent&&a.attachEvent("onload",function(){a.clearInterval(c)})}}}(this);</script>';

		global $wp_styles;

		if($wp_styles) {
			$styles = wp_clone( $wp_styles );
			$styles->all_deps($styles->queue);
			$styles->do_concat = true;
			$styles->do_items();
			$noscript_html = '<noscript>' . $styles->print_html . '</noscript>';
			$html = str_replace("stylesheet", 'preload\' as=\'style\' onload=\'this.rel="stylesheet"', $styles->print_html);
			echo $html;
			echo $jscript;
			echo $noscript_html;

			$wp_styles->done = $styles->done;
		}
	}
}
