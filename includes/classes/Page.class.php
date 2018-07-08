<?php

Class Page {

	// Original data
	public $original, $customFields, $blogInfo;

	// ID of the object
	public $id, $publishDate, $featureImages, $permalink, $children, $searchSnippet;
	public $slides = null;

	/**
	 * Construct the Page class instance
	 * @param mixed $data Data from wordpress Class, ID, Object or null
	 * @return self
	 */
	public function __construct ($data = null) {
		
		global $wp_query;

		// If on a 404 page, set original content to nothing
		if (is_404())
			$this->original = new StdClass;

		// If data is null, use the global query object
		if (is_null($data)) $data = $wp_query->get_queried_object();

		// If $data is WP_Post object
		if (is_a($data, 'WP_Post')) $this->original = $data;

		// If $data is numeric, load using it as a Page ID
		if (is_numeric($data)) $this->original = get_post($data);

		// Set ID
		if (is_a($this->original, 'WP_Post')) $this->id = $this->original->ID;

		// If ID is not null, get custom fields
		if (!empty($this->id) && function_exists('get_fields')) $this->customFields = get_fields($this->id);

		// Santise the content
		$this->sanitiseContent();

		// Get feature image
		if (!empty($this->id)) {
			$this->featureImages = [
				'thumbnail' => preg_replace( "/(.+src\=)[\'\"]([^\'\"]+)[\"\'](.*)/", '$2', get_the_post_thumbnail($this->id, 'thumbnail')),
				'medium' => preg_replace("/(.+src\=)[\'\"]([^\'\"]+)[\"\'](.*)/", '$2',  get_the_post_thumbnail($this->id, 'medium')),
				'large' => preg_replace("/(.+src\=)[\'\"]([^\'\"]+)[\"\'](.*)/", '$2',  get_the_post_thumbnail($this->id, 'large')),
				'full' => preg_replace("/(.+src\=)[\'\"]([^\'\"]+)[\"\'](.*)/", '$2',  get_the_post_thumbnail($this->id, 'full')),
			];
			
			// If the image is a GIF, set all featureImages to the FULL image version
			if (!empty(array_filter($this->featureImages))){
				if (stristr('gif', pathinfo($this->featureImages['full'])['extension'])) {
					$this->featureImages = [
						'thumbnail' => $this->featureImages['full'],
						'medium' => $this->featureImages['full'],
						'large' => $this->featureImages['full'],
						'full' => $this->featureImages['full'],
					];
				}
			}
		}

		// Get permalink
		if (!empty($this->id)) $this->permalink = get_permalink($this->id);

		// Get publish date
		$this->getPublishDate();

		// Find children
		$this->findChildren();

		// Generate excerpt if doesn't exist
		$this->searchSnippet = $this->makeSnippet();

		// If found, return this instance, otherwise null
		return (!empty($this->id))? $this : null;
	}

	/**
	 * Magic method to shorthand the method of getting a property from the original data
	 * @param  	string $property
	 * @return 	mixed
	 */
	public function __get ($property) {

		// Change property name if page_ prefix instead of post_
		$property = preg_replace("/^page\_(.*)/", 'post_$1', $property);

		// Replace certain property names
		$property = preg_replace([ "/^title$/", "/^name$/", "/^content$/", "/^published$/", "/^excerpt$/" ], [ 'post_title', 'post_name', 'post_content', 'post_date', 'post_excerpt'], $property);

		return (isset($this->original->{$property}))? $this->original->{$property} : ((isset($this->customFields[$property])) ? $this->customFields[$property] : null);
	}

	/**
	 * Sanitises the content, applies filters and such..
	 * @param  string $content
	 * @return string
	 */
	public function sanitiseContent (string $content = '') {

		// If not called statically
		if (isset($this) && strlen($content) == 0 && isset($this->original->post_content))
			$content = $this->original->post_content;

		// Apply filters
		$content = apply_filters('the_content', $content);

		// Find media objects
		$mediaContentArray = get_media_embedded_in_content($content, ['video', 'object', 'embed', 'iframe']);


		// If media objects found, wrap them
		if (!empty($mediaContentArray)) {

			// Loop through
			foreach ($mediaContentArray as $mediaHTML) {
				
				// String replace the html
				$content = str_replace($mediaHTML, "<div class='video-wrapper'>{$mediaHTML}</div>", $content);
			}
		}

		// Fix URL links (some anchor href attribute values may begin with www as opposed to http://www.)
		$content = str_replace(["href=\"www.", "href='www."], ["href=\"//www.", "href='//www."], $content);

		// If not called statically
		if (isset($this)){
			if (isset($this->original->post_content))
				$this->original->post_content = $content;
			$this->content = $content;
		}

		// Return
		return $content;
	}

	/**
	* Get's custom field by key with fallback
	 * @param 	string $key
	 * @param 	string|null $fallback
	 * @return 	mixed
	 */
	public function customField ($key, $fallback = null) {

		// Check if custom fields are found
		if (!empty($this->customFields) && array_key_exists($key, $this->customFields)) $value = $this->customFields[$key];
		else {

			// Else, find field
			if (function_exists('get_field'))
				$value = get_field($key, $this->id);
			else $value = null;

			// Store in $customFields property
			if (!is_null($value))
				$this->customFields[$key] = $value;
		}

		return (is_null($value) || (is_string($value) && empty(strip_tags($value))))? $fallback : $value;
	}

	/**
	 * Alias of $this->customField()
	 * @param 	string $key
	 * @param 	string|null $fallback
	 * @return 	mixed
	 */
	public function gcf ($key, $fallback = null) {
		return $this->customField($key, $fallback);
	}
	/**
	 * Formats the publish time to a date $format
	 * @param 	string $format The date format to output
	 * @return 	string
	 */
	public function getPublishDate ($format = 'Y-m-d H:i:s') {
		// If no original data, return current date
		if (!is_object($this->original))
			return date($format);
			
		$this->publishDate = @mysql2date($format, $this->original->post_date);
		return $this->publishDate;
	}

	/**
	 * Check if feature image exists
	 * @param 	string|null $size
	 * @return 	boolean
	 */
	public function hasFeatureImage ($size = null) {

		// If correct size provided, see if that exists
		if (!is_null($size) && in_array($size, [ "thumbnail", "medium", "large", "full" ]))
			return (!empty($this->featureImages[$size]));

		// Otherwise check if any feature image available
		return (count(array_filter($this->featureImages)) > 0);
	}

	/**
	 * Get's feature image by size
	 * @param 	string $size
	 * @return 	string
	 */
	public function getFeatureImage ($size = 'full') {
		return $this->featureImages[ $size ];
	}

	/**
	 * Get's the largest available size feature image
	 * @return string
	 */
	public function getLargestFeatureImage () {

		// Loop through sizes (largest to smallest) and return if found
		foreach (imageOrderByDevice('DESC') as $size)
			if (!empty($this->featureImages[$size])) return $this->featureImages[$size];
		
		// Else, nothing found? Return full image
		return $this->featureImages['full'];
	}

	/**
	 * Get's the smallest available size feature image
	 * @return mixed
	 */
	public function getSmallestFeatureImage () {

		// Loop through sizes (smallest to smallest) and return if found
		foreach (array_reverse(imageOrderByDevice('ASC'))  as $size)
			if (!empty($this->featureImages[$size])) return $this->featureImages[$size];
		
		// Else, nothing found? Return null
		return null;
	}

	/**
	 * Returns the path for a feature image path
	 * @param  string $size
	 * @return string
	 */
	public function getFeatureImagePath ($size = 'full') : string {
		$url = $this->getFeatureImage($size);
		$path = WP_CONTENT_DIR . str_replace(content_url(), '', $url);
		return $path;
	}

	/**
	 * Determins if there are any children of this page
	 * @return void
	 */
	private function findChildren () {

		// Find children
		$this->children = get_posts([
			'posts_per_page' => 100,
			'post_type' => 'page',
			'post_parent' => $this->id, 
			'post_status' => 'publish',
			'order' => 'ASC',
			'orderby' => 'menu_order',
		]);

		if (!empty($this->children))
			$this->children = array_map( function ($item) {
				return new Page($item);
			}, $this->children);

	}

	/** 
	 * Makes a snippet if need be
	 * @param  integer $numWords
	 * @return string
	 */
	public function makeSnippet ($numWords = 120, $elipsis = "...") {
		return wp_trim_words($this->content, $numWords, "") . $elipsis;
	}

	/**
	 * Returns this object as a JSON object
	 * @return string
	 */
	public function returnAsJson () {
		return json_encode((array)$this);
	}

}