<?php

// Set global directories to use
global $template_dir, $template_dir_uri;

$template_dir     = get_template_directory();
$template_dir_uri = get_template_directory_uri();

function add_enqueued_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false)
{
    wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
}

function add_enqueued_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
{
    wp_enqueue_style($handle, $src, $deps, $ver, $media);
}

/**
 * Scripts to dequeue
 */
function dequeue_scripts()
{
    // Do not deregister if debug bar is visible
    if (!isset($GLOBALS['debug_bar']) || !is_admin_bar_showing()) {
        wp_deregister_script('jquery');
        wp_deregister_script('jquery-ui-core');
        wp_deregister_script('jquery-migrate');
    }
    wp_deregister_script('wp-embed');
}

/**
 * Scripts to use
 */
function enqueue_scripts()
{
    global $template_dir, $template_dir_uri, $late_scripts;

    // Main theme
    if (file_exists($template_dir . '/js/src/main.min.js')) {
        wp_enqueue_script('main', $template_dir_uri . '/js/src/main.min.js?' . filemtime($template_dir . '/js/src/main.min.js') . '#asyncload', [], null, true);
    }

}

/**
 * Stylesheets to use
 */
function enqueue_styles()
{
}
/**
 * Add styles to footer
 */
function prefix_add_footer_styles()
{
    global $template_dir, $template_dir_uri;

    // Main Fonts
    wp_enqueue_style('main-font', 'https://fonts.googleapis.com/css?family=Open+Sans:400,600,700|Playfair+Display:400#asyncload', [], null, false);

    // Main theme
    if (file_exists($template_dir . '/css/src/main.min.css')) {
        wp_enqueue_style('main', $template_dir_uri . '/css/src/main.min.css?' . filemtime($template_dir . '/css/src/main.min.css') . '#asyncload', [], null, false);
    }

};

/**
 * Loads scripts and styles asynchronously
 **/
function async_scripts_and_styles($url)
{
    if (strpos($url, '#asyncload') === false) {
        return $url;
    } else if (is_admin()) {
        return str_replace('#asyncload', '', $url);
    } else {
        return str_replace('#asyncload', '', $url) . "' defer='defer";
    }

}
add_filter('clean_url', 'async_scripts_and_styles', 11, 1);

// Load scripts and stylesheets through wordpress action
if (!is_admin() && $GLOBALS['pagenow'] !== 'wp-login.php') {
    add_action('wp_enqueue_scripts', 'enqueue_scripts');
    add_action('wp_enqueue_scripts', 'enqueue_styles');
    add_action('wp_print_scripts', 'dequeue_scripts', 100);
    add_action('get_footer', 'prefix_add_footer_styles');
}
