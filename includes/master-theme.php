<?php

unset($_GET['s']);

/**
 * Removes the clutter in wp_head() method by disabling filters/actions
 * @return void
 */
function removeWPHeadClutter () {

	remove_action('wp_head', 'rsd_link'); // remove really simple discovery link
	remove_action('wp_head', 'wp_generator'); // remove wordpress version

	remove_action('wp_head', 'feed_links', 2); // remove rss feed links (make sure you add them in yourself if youre using feedblitz or an rss service)
	remove_action('wp_head', 'feed_links_extra', 3); // removes all extra rss feed links

	remove_action('wp_head', 'index_rel_link'); // remove link to index page
	remove_action('wp_head', 'wlwmanifest_link'); // remove wlwmanifest.xml (needed to support windows live writer)

	remove_action('wp_head', 'start_post_rel_link', 10, 0); // remove random post link
	remove_action('wp_head', 'parent_post_rel_link', 10, 0); // remove parent post link
	remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // remove the next and previous post links
	remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

	remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0); // Remove shortlink

	// Remove the REST API lines from the HTML Header
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );

	// Remove the REST API endpoint.
	remove_action( 'rest_api_init', 'wp_oembed_register_route' );

	// Turn off oEmbed auto discovery.
	add_filter( 'embed_oembed_discover', '__return_false' );

	// Don't filter oEmbed results.
	remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );

	// Remove oEmbed discovery links.
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

	// Remove oEmbed-specific JavaScript from the front-end and back-end.
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );

	// Remove Emoji stuff
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('admin_print_styles', 'print_emoji_styles');
}
// Call above method
removeWPHeadClutter();

// Remove the custom CSS post-type
add_action('customize_register', 'prefixRemoveCSSSection', 15);
function prefixRemoveCSSSection ($wp_customize) {
	$wp_customize->remove_section('custom_css');
}

// Change the max-sizes of the media sizes to more suitable sizes
add_action("after_switch_theme", function(){
	update_option('thumbnail_size_w', 250);
	update_option('thumbnail_size_h', 250);

	update_option('medium_size_w', 960);
	update_option('medium_size_h', 960);

	update_option('large_size_w', 1440);
	update_option('large_size_h', 1440);
});

// Allow for a header menu
register_nav_menus([
	'header' => 'Header menu', 
]);

// Allow thumbnail (feature image) support for theme
add_theme_support('post-thumbnails');

// Theme customs
add_theme_support('custom-logo');

// Add theme support for title
add_theme_support('title-tag');

/**
 * Register custom post types
 * @return void
 */
function registerPostTypes () {
	
	//

}
add_action('init', 'registerPostTypes');

/**
 * Register custom taxonomies
 * @return void
 */
function registerTaxonomies () {

	//
	
}
add_action('init', 'registerTaxonomies');


/**
 * Changes the ID of the navigation list element to include the post slug
 * @param  string 	$id
 * @param  WP_Post 	$item
 * @param  array 	$args
 * @return string
 */
function clearNavMenuItemId ($id, $item, $args) {
	$page = new Page($item->object_id);
    return "nav-item-{$page->name}";
}
add_filter('nav_menu_item_id', 'clearNavMenuItemId', 10, 3);


/**
 * Adds a "current-menu-item" class to navigation item for its sub-pages
 * @param string 	$classes
 * @param WP_Post 	$item
 */
function addCurrentNavClass ($classes, $item) {
	if (!is_404()) {
		// Getting the current post details
		global $post;
		
		// Getting the post type of the current post
		$current_post_type = get_post_type_object(get_post_type($post->ID));
		$current_post_type_slug = $current_post_type->rewrite['slug'];

			
		// Getting the URL of the menu item
		$menu_slug = strtolower(trim($item->url));
		
		// If the menu item URL contains the current post types slug add the current-menu-item class
		if (strpos($menu_slug,$current_post_type_slug) !== false || (home_url() == trim($menu_slug, "/") && $current_post_type_slug == 'work'))
			$classes[] = 'current-menu-item';
	}
	// Return the corrected set of classes to be added to the menu item
	return $classes;
}
add_action('nav_menu_css_class', 'addCurrentNavClass', 10, 2 );