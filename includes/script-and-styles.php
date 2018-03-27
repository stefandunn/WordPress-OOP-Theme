<?php


// Set global directories to use
global $template_dir, $template_dir_uri;

$template_dir = get_template_directory();
$template_dir_uri = get_template_directory_uri();

/**
* Scripts to dequeue
*/
function dequeue_scripts () {
	// Do not deregister if debug bar is visible
	if (!isset($GLOBALS['debug_bar']) || !is_admin_bar_showing()) {
		wp_deregister_script('jquery');
		wp_deregister_script('jquery-ui-core');
		wp_deregister_script('jquery-migrate');
	}
	wp_deregister_script( 'wp-embed' );
}

/**
* Scripts to use
*/
function enqueue_scripts () {
	global $template_dir, $template_dir_uri;

	// Main theme
	if(file_exists($template_dir . '/js/src/main.min.js'))
		wp_enqueue_script('main', $template_dir_uri . '/js/src/main.min.js', [], null, true);
}

/**
* Stylesheets to use
*/
function enqueue_styles () {
	global $template_dir, $template_dir_uri;
	
	// Main theme
	if(file_exists($template_dir . '/css/src/main.min.css'))
		wp_enqueue_style('main', $template_dir_uri . '/css/src/main.min.css', [], null, false);
}
/**
 * Add styles to footer
 */
function prefix_add_footer_styles() {
};


/**
 * Loads scripts and styles asynchronously for those with #asyncload appended to `src` attribute
**/ 
function async_scripts_and_styles($url)
{
    if ( strpos( $url, '#asyncload') === false )
        return $url;
    else if ( is_admin() )
        return str_replace( '#asyncload', '', $url );
    else
	return str_replace( '#asyncload', '', $url )."' defer='defer"; 
    }
add_filter( 'clean_url', 'async_scripts_and_styles', 11, 1 );

// Load scripts and stylesheets through wordpress action
if(!is_admin() && $GLOBALS['pagenow'] !== 'wp-login.php') {
	add_action('wp_enqueue_scripts', 'enqueue_scripts');
	add_action('wp_enqueue_scripts', 'enqueue_styles');
	add_action('wp_print_scripts', 'dequeue_scripts', 100);
	add_action('get_footer', 'prefix_add_footer_styles');
}