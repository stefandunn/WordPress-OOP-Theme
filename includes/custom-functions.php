<?php

global $headerLoaded, $header, $footer, $permalinks, $pages, $blogInfo;
$headerLoaded = false;
$permalinks   = []; // To be populated with found permalinks (to prevent too many database calls)
$pages        = []; // To be populated with pages found
$blogInfo     = []; // To be populated

/**
 * Clear rewrite rules if $_GET['clear_rewrite'] is true
 */
if (isset($_GET['clear_rewrite']) && $_GET['clear_rewrite'] == true) {
    flush_rewrite_rules(false);
}

/**
 * Get's the header, works for SPA and MPA
 * @return void
 */
function getTheHeader(): void
{
    global $headerLoaded, $header;

    // If header not yet loaded, load it.
    if ($headerLoaded == false) {
        // Now loaded, change flag
        $headerLoaded = true;

        get_header();
    }

    echo "<section id='" . thisPage()->name . "' class='page-container" . ((strpos(getTemplateName(), 'page') !== false) ? " a-page" : ((getTemplateName() == 'index') ? "a-post" : ((getTemplateName() == 'single-project') ? " a-project" : ""))) . "' data-url='" . thisPage()->permalink . "'>";
    
}

/**
 * Get's the footer, works for SPA and MPA
 * @return void
 */
global $footerLoaded;
$footerLoaded = false;

function getTheFooter(): void
{
    global $footerLoaded, $footer;

    echo "</section>";

    if ($footerLoaded == false) {

        // Now loaded, change flag
        $footerLoaded = true;

        // Get WP footer
        get_footer();
    }

}

/**
 * Returns links for a WP navigation menu
 * @param  string $position
 * @return array
 */
function getNavLinks(string $position): array
{
    $links       = wp_get_nav_menu_items($position);
    $parentLinks = [];
    // Loop through the parents
    foreach ($links as $link) {
        // If parent
        if ($link->menu_item_parent == '0') {

            // Convert to object (via doubnle casting)
            $link_obj = (object) (array) $link;

            // Find children
            $link_obj->children = getLinkChildren($link, $links);

            // Add to parent links
            $parentLinks[] = $link_obj;
        }
    }

    return $parentLinks;
}

/**
 * Finds a permalinnk for a post_type
 * @param  string $attr
 * @param  string $attrProp
 * @param  string $post_type
 * @return string
 */
function findPermalink(string $attr, string $attrProp = 'title', string $post_type = 'page'): string
{
    global $permalinks;

    // If already found, return it
    if (isset($permalinks[$attr][$attrProp])) {
        return $permalinks[$attr][$attrProp];
    }

    // Else, lets find it
    else {
        // Query database
        if (!array_key_exists($attr, $permalinks)) {
            $permalinks[$attr] = [$attrProp => page($attr, $attrProp, $post_type)->permalink];
        } else {
            $permalinks[$attr][$attrProp] = page($attr, $attrProp, $post_type)->permalink;
        }

        return $permalinks[$attr][$attrProp];
    }
}

/**
 * Finds a post_type entry using 'title' attribite (by default)
 * @param  string $attr
 * @param  string $attrProp
 * @param  string $postType
 * @return Page
 */
function page(string $attr, string $attrProp = 'title', string $postType = 'page'): Page
{
    global $pages;

    // Query database
    $posts = (new WP_Query([
        'posts_per_page' => 1,
        'post_type'      => $postType,
        $attrProp        => $attr,
    ]))->get_posts();

    // If found
    if (!empty($posts)) {
        if (!array_key_exists($attr, $pages)) {
            $pages[$attr] = [$attrProp => (new Page(current($posts)))];
        } else {
            $pages[$attr][$attrProp] = (new Page(current($posts)));
        }

        return $pages[$attr][$attrProp];
    } else {
        return new Page;
    }

}

/**
 * Get the name of the template file
 * @return string
 */
function getTemplateName(): string
{
    global $template;
    return basename($template, ".php");
}

/**
 * Rennder a partial page (without footer/header)
 * @param  stfring $pageName
 * @param  string  $template
 * @return void
 */
function renderPartialPage(stfring $pageName, string $template): void
{
    global $headerLoaded, $footerLoaded;
    $old_headerLoaded = $headerLoaded;
    $old_footerLoaded = $footerLoaded;

    // Set page for $curPage
    setPage($pageName);

    // If header loaded, close "<section>";
    if ($headerLoaded) {
        echo "</section>";
    }

    $headerLoaded  = true;
    $footerLoaded  = true;
    $partialRender = true;

    include dirname(__DIR__) . '/' . $template;

    $headerLoaded = false;
    $footerLoaded = false;

    // Reset the page for $curPage
    resetPage();

}

/**
 * Die and var_dump data
 * @param  mixed $data
 * @return void
 */
function dd($data): void
{
    die(var_dump($data));
    exit;
}

global $curPage;
$curPage = false;
/**
 * Get's the current page/post/post_type object
 * @return mixed
 */
function thisPage()
{
    global $curPage;

    // If $curPage not found yet, fetch it
    if ($curPage === false) {

        if (is_archive()) {
            $curPage = new Archive;
        } else {
            $curPage = new Page;
        }

    }

    // If case study, convert
    if (!is_archive()) {
        $curPage = confirmPageType($curPage);
    }

    // Return $curPage if already found, otherwise, fetch and return
    return $curPage;
}

/**
 * Confirms and reconfigures the page object type depending on post type.
 * @param  mixed $page
 * @return mixed
 */
function confirmPageType($page)
{

    // Only confirm non Archives
    if (!is_a($page, 'Archive')) {
        if (isset($page->original->post_type)) {
            $postType = $page->original->post_type;
        } elseif (isset($page->post_type)) {
            $postType = $page->post_type;
        } else {
            $postType = 'page';
        }

        if ($postType == 'page' && get_class($page) != 'Page') {
            return new Page($page->ID);
        }

    }

    // Else, return self
    return $page;
}

/**
 * Set the current page explicitly
 * @param     mixed $page
 * @param     boolean $preventFooter
 * @return  void
 */
function setPage($page, $preventFooter = false): void
{
    global $curPage, $footerLoaded;

    // Footer loaded hack
    $footerLoaded = $preventFooter;

    // IF a WP_Post object, set to new Page Class instance
    if (is_a($page, 'WP_Post')) {
        $curPage = new Page($page);
    }

    // If a Page instance, set directly
    elseif (is_a($page, 'Page') || is_a($page, 'Archive')) {
        $curPage = $page;
    }

    // If an ID, create new Page instance by retrieving post/page from DB
    elseif (is_numeric($page)) {
        $curPage = new Page(get_post($page));
    }

    // IF a string, create new instance of Page using title to get post/page from DB
    elseif (is_string($page)) {
        $curPage = new Page(current(get_posts(['posts_per_page' => 1, 'title' => $page, 'post_type' => 'page', 'orderby' => 'title', 'post_status' => 'publish'])));
    } else {
        $curPage = null;
    }

}

/**
 * Reset the explicitly set page
 * @return void
 */
function resetPage(): void
{
    global $curPage, $footerLoaded, $header;

    // Reset status of footer.
    $footerLoaded = false;

    // Resets to current page from $WP_Query result
    $curPage = new Page();
}

/**
 * Get the current URL of page
 * @param  boolean $includeQuery
 * @return string
 */
function currentUrl($includeQuery = true): string
{
    // Start URL string
    $url = 'http';

    // If secure server, add "s" to end of http to make https
    if (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") {
        $url .= "s";
    }

    $url .= "://";

    // Append the URL part
    $url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

    // Parsed URL
    $urlParsed = parse_url($url);

    // Change URL to fit the parsed_url scheme
    $url = $urlParsed['scheme'] . "://" . $urlParsed['host'] . $urlParsed['path'];

    // Include query string
    if ($includeQuery && !empty($urlParsed['query'])) {
        $url .= "?" . $_SERVER['QUERY_STRING'];
    }

    // Return
    return $url;
}

/**
 * Get the appropiate image size for device
 * @return string
 */
function deviceImageSize(): string
{
    if (function_exists('is_mobile')) {
        return (is_mobile()) ? "medium" : "large";
    }

    return "large";
}

/**
 * Return device type
 * @return string
 */
function getDevice(): string
{
    if (function_exists('is_mobile')) {
        if (is_mobile() && !is_tablet()) {
            return "mobile";
        } elseif (is_tablet()) {
            return "tablet";
        }

    }
    return "desktop";
}

/**
 * Return the URI for a file
 * @param     string $file
 * @return     string|null
 */
function URIForFile(string $file):  ? string
{
    // Search in child template for file first, then master template and if still not found, return null
    if (file_exists(get_template_directory() . '/' . $file)) {
        return get_template_directory_uri() . '/' . $file;
    } else {
        return null;
    }

}

/**
 * Return the URI for an image
 * @param     string $imageFilename
 * @return     string|null
 */
function URIForImage(string $imageFilename) :  ? string
{

    return UriForFile('images/' . $imageFilename);
}

/**
 * Return the URI for a CSS file
 * @param     string $cssFilename
 * @return     string|null
 */
function URIForCss(string $cssFilename) :  ? string
{

    return UriForFile('css/' . $cssFilename);
}

/**
 * Return the URI for a script file
 * @param     string $jsFilename
 * @return     string|null
 */
function URIForJs(string $jsFilename) :  ? string
{

    return UriForFile('js/' . $jsFilename);
}

/**
 * Return the path for a file
 * @param     string $file
 * @return     string|null
 */
function pathForFile(string $file) :  ? string
{

    // Search in child template for file first, then master template and if still not found, return null
    if (file_exists(get_template_directory() . '/' . $file)) {
        return get_template_directory() . '/' . $file;
    } else {
        return null;
    }

}

/**
 * Return the path for an image
 * @param     string $imageFilename
 * @return     string|null
 */
function pathForImage(string $imageFilename) :  ? string
{

    return pathForFile('images/' . $imageFilename);
}

/**
 * Return the path for a CSS file
 * @param     string $cssFilename
 * @return     string|null
 */
function pathForCss(string $cssFilename) :  ? string
{

    return pathForFile('css/' . $cssFilename);
}

/**
 * Return the path for a script file
 * @param     string $jsFilename
 * @return     string|null
 */
function pathForJs(string $jsFilename) :  ? string
{

    return pathForFile('js/' . $jsFilename);
}

global $flashMessage;
/**
 * Returns a link of flash messages
 * @return array
 */
function flashMessages() : array
{
    global $flashMessage;
    // New instance of FlashMessage Class
    $flashMessage = new FlashMessage();
    // Obtain all flash messages
    $flashMessage->get_all();
    // Return Class instance
    return $flashMessage;
}

/**
 * Fix the letter casing of an input
 * @param  string $input
 * @param  string $style
 * @return string
 */
function fixCase(string $input, $style = 'title'): string
{
    switch ($style) {
        case 'lower':
            $input = strtolower($input);
            break;
        case 'upper':
            $input = strtoupper($input);
            break;
        case 'sentance':
            $input = ucfirst(strtolower($input));

        // Default will be titl case
        default:
            $input = ucwords(strtolower($input));
            break;
    }

    return $input;
}

/**
 * Get the logo as an image object
 * @return array
 */
function getLogo(): array
{
    $customLogoId = get_theme_mod('custom_logo');
    $image        = wp_get_attachment_image_src($customLogoId, 'full');
    return $image[0];
}

/**
 * Make a transparent spacer file and return the URI
 * @param  integer $x
 * @param  integer $y
 * @return string|null
 */
function getSpacerImage(int $x, int $y):  ? string
{
    // If square, reduce the dimesions
    if ($x == $y) {
        $x = $y = 1;
    }

    // If 2x1 lCF
    elseif ($x / 2 == $y) {
        $x = 2;
        $y = 1;
    }
    // If 1x2 LCF
    elseif ($y / 2 == $x) {
        $x = 1;
        $y = 2;
    }

    // If image doesn't exist, create it.
    if (!file_exists("../images/{$x}x{$y}.png")) {
        // Create new image to dimensions
        $img = imagecreatetruecolor($x, $y);
        // Save alpha channels
        imagesavealpha($img, true);
        // Set transparent colour
        $color = imagecolorallocatealpha($img, 0, 0, 0, 127);
        // Fill transparent colour
        imagefill($img, 0, 0, $color);
        // Save it
        imagepng($img, dirname(__DIR__) . "/images/{$x}x{$y}.png");
    }

    // Return URI to it.
    return UriForImage("{$x}x{$y}.png");
}

/**
 * Get the image data as an array, similar to ACF does
 * @param  integer $imageId
 * @return array
 */
function getImageDataArray(int $imageId) : array
{
    $featureImagePost     = get_post($imageId);
    $featureImageMetaData = wp_get_attachment_metadata($imageId);
    $url                  = wp_get_attachment_url($imageId);
    $baseUrl              = str_replace(basename($featureImageMetaData['file']), "", $url);
    $featureImageArray    = [
        'ID'          => $imageId,
        'title'       => $featureImagePost->post_title,
        'filename'    => basename($featureImageMetaData['file']),
        'url'         => $url,
        'alt'         => get_post_meta($imageId, '_wp_attachment_image_alt', true),
        'author'      => $featureImagePost->post_author,
        'description' => $featureImagePost->post_content,
        'caption'     => $featureImagePost->post_excerpt,
        'name'        => $featureImagePost->post_name,
        'date'        => $featureImagePost->post_date,
        'modified'    => $featureImagePost->post_modified,
        'mime_type'   => $featureImagePost->post_mime_type,
        'type'        => explode("/", $featureImagePost->post_mime_type)[0],
        'icon'        => 'http://intrinsic.dev/wp-includes/images/media/default.png',
        'width'       => $featureImageMetaData['width'],
        'height'      => $featureImageMetaData['height'],
        'sizes'       => [
            'thumbnail'        => (!empty($featureImageMetaData['sizes']['thumbnail'])) ? $baseUrl . $featureImageMetaData['sizes']['thumbnail']['file'] : null,
            'thumbnail_width'  => ($featureImageMetaData['sizes']['thumbnail']['width']) ?? null,
            'thumbnail_height' => ($featureImageMetaData['sizes']['thumbnail']['height']) ?? null,
            'medium'           => (!empty($featureImageMetaData['sizes']['medium'])) ? $baseUrl . $featureImageMetaData['sizes']['medium']['file'] : null,
            'medium_width'     => ($featureImageMetaData['sizes']['medium']['width']) ?? null,
            'medium_height'    => ($featureImageMetaData['sizes']['medium']['height']) ?? null,
            'large'            => (!empty($featureImageMetaData['sizes']['large'])) ? $baseUrl . $featureImageMetaData['sizes']['large']['file'] : null,
            'large_width'      => ($featureImageMetaData['sizes']['large']['width']) ?? null,
            'large_height'     => ($featureImageMetaData['sizes']['large']['height']) ?? null,
        ],
    ];

    return $featureImageArray;
}

/**
 * Return size information from ACF returned image
 * @param  array $imageArray
 * @param  string $size
 * @return array
 */
function acfImageArraySizeInfo(array $imageArray, string $size = null): array
{
    $usableSizes = ['thumbnail', 'medium', 'large', 'largest'];
    // Set default size if not given, determined by device.
    if (is_null($size)) {
        $size = deviceImageSize();
    }

    if (!in_array($size, $usableSizes)) {
        $size = deviceImageSize();
    }

    // Calculate the largest size it can fetch
    if ($size == 'largest') {
        array_pop($usableSizes);
        $usableSizes = array_reverse($usableSizes);
        $foundSize   = null;
        foreach ($usableSizes as $testSize) {
            if (array_key_exists($testSize, $imageArray['sizes'])) {
                $foundSize = $testSize;
                break;
            }
        }
        if (!is_null($foundSize)) {
            $size = $foundSize;
        } else {
            $size = null;
        }

    }

    // Check size is usable
    if (array_key_exists($size, array_filter($imageArray['sizes']))) {
        return [
            'url'         => $imageArray['sizes'][$size],
            'width'       => $imageArray['sizes']["{$size}-width"] ?? 0,
            'height'      => $imageArray['sizes']["{$size}-height"] ?? 0,
            'title'       => $imageArray['title'],
            'caption'     => $imageArray['caption'],
            'description' => $imageArray['description'],
            'mime_type'   => $imageArray['mime_type'],
        ];
    } else {
        return [
            'url'         => $imageArray['url'],
            'width'       => $imageArray['width'],
            'height'      => $imageArray['height'],
            'title'       => $imageArray['title'],
            'caption'     => $imageArray['caption'],
            'description' => $imageArray['description'],
            'mime_type'   => $imageArray['mime_type'],
        ];
    }

}

/**
 * Get all blog info into array
 * @param     string $property
 * @param     mixed $fallback
 * @return     array
 */
function getBlogProperty(string $property, $fallback = null): array
{
    global $wp_version, $blogInfo;

    // Fetch all blog info if not already done.
    if (empty($blogInfo)) {

        // Get home URL
        $homeUrl = home_url();

        // Language
        $language = __('html_lang_attribute');
        if ('html_lang_attribute' === $language || preg_match('/[^a-zA-Z0-9-]/', $language)) {
            $language = get_locale();
            $language = str_replace('_', '-', $language);
        }

        // Text Direction
        if (function_exists('is_rtl')) {
            $textDirection = is_rtl() ? 'rtl' : 'ltr';
        } else {
            $textDirection = 'ltr';
        }

        $blogInfo = [
            'home'                 => $homeUrl,
            'siteurl'              => $homeUrl,
            'url'                  => $homeUrl,
            'wpurl'                => site_url(),
            'description'          => get_option('blogdescription'),
            'tagline'              => get_option('blogdescription'),
            'rdf_url'              => get_feed_link('rdf'),
            'rss_url'              => get_feed_link('rss'),
            'rss2_url'             => get_feed_link('rss2'),
            'atom_url'             => get_feed_link('atom'),
            'comments_atom_url'    => get_feed_link('comments_atom'),
            'comments_rss2_url'    => get_feed_link('comments_rss2'),
            'pingback_url'         => site_url('xmlrpc.php'),
            'stylesheet_url'       => get_stylesheet_uri(),
            'stylesheet_directory' => get_stylesheet_directory_uri(),
            'template_directory'   => get_template_directory_uri(),
            'admin_email'          => get_option('admin_email'),
            'charset'              => get_option('blog_charset', 'UTF-8'),
            'html_type'            => get_option('html_type'),
            'version'              => $wp_version,
            'language'             => $language,
            'text_direction'       => $textDirection,
            'name'                 => get_option('blogname'),
            'blogname'             => get_option('blogname'),
            'title'                => get_option('blogname'),
        ];
    }

    return (array_key_exists($property, $blogInfo)) ? $blogInfo[$property] : $fallback;
}

/**
 * Places array elements into piles of size X, any left over elements (modulus) will be stacked evening over the first X piles
 * If not enough piles can be formed, the minimum number is returned
 * @param  array   $arr
 * @param  int $sizeOfPile
 * @return array
 */
function chunkToPiles(array $arr, int $sizeOfPile): array
{
    $piles           = [];
    $arrSize         = count($arr);
    $elementsPerPile = floor($arrSize / $sizeOfPile);
    $remainder       = count($arrSize) % $elementsPerPile;

    for ($i = 0; $i < $sizeOfPile; $i++) {
        $piles[] = array_splice($arr, 0, $elementsPerPile);
    }

    // Add remainders
    $rc = 0;
    for ($i = 0; $i < count($arr); $i++) {
        if ($rc >= count($piles)) {
            $rc = 0;
        }

        $piles[$rc][] = array_splice($arr, 0, 1)[0];
    }

    return array_filter($piles);
}

/**
 * Removes an element from an array via it's value
 * @param  array &$arr
 * @param  mixed $element
 * @return void
 */
function removeFromArray(&$arr, $element): void
{
    if (($key = array_search($element, $arr)) !== false) {
        unset($arr[$key]);
    }
}

/**
 * Generates the image order array by device
 * @param string $order "ASC" will order smallest size first, "DESC" will order largest priority first
 * @return array
 */
function imageOrderByDevice($order = 'DESC'): array
{
    // Get device
    if ($order == 'DESC') {
        return (is_mobile() && !is_tablet()) ? ["small", "medium", "large"] : ((is_tablet()) ? ["medium", "large", "small"] : ["large", "medium", "small"]);
    } else {
        return (is_mobile() && !is_tablet()) ? ["thumbnail", "small", "medium", "large"] : ["small", "medium", "large", "full"];
    }

}

/**
 * Returns the device
 * @return string
 */
function device(): string
{
    if (is_mobile() && !is_tablet()) {
        return 'mobile';
    }

    if (is_tablet()) {
        return 'tablet';
    }

    return 'desktop';
}

/**
 * Returns a $_GET variable parameter if available, otherwise returns the $fallback value
 * @param  string $variableName
 * @param  mixed $fallback
 * @return mixed
 */
function getVar(string $variableName, $fallback = null)
{
    return (isset($_GET[$variableName])) ? $_GET[$variableName] : $fallback;
}

/**
 * Returns a $_POST variable parameter if available, otherwise returns the $fallback value
 * @param  string $variableName
 * @param  mixed $fallback
 * @return mixed
 */
function postVar(string $variableName, $fallback = null)
{
    return (isset($_POST[$variableName])) ? $_POST[$variableName] : $fallback;
}

/**
 * Returns true or false if the request made is an AJAX request
 * @return boolean
 */
function isAjaxRequest(): bool
{
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}
