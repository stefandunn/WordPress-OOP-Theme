<?php


Class Archive {

	public $postType, $name, $permalink, $hasPosts, $hasLeftOvers;

	private $internalOffset = 0;
	private $posts, $internalOrder, $internalOrderType, $internalPostCount, $internalTotalCount;


	/**
	 * Class constructor
	 */
	public function __construct(string $postType = null) {

		global $wp_query;

		if (is_null($postType))
			$this->postType = (!empty($wp_query->get('post_type')))? $wp_query->get('post_type') : $this->determinePostType();
		else
			$this->postType = $postType;

		$this->name = "{$this->postType}-archive";
		$this->permalink = get_post_type_archive_link($postType);

		$this->internalTotalCount = count(get_posts([
			'post_type' => $this->postType,
			'posts_per_page' => 100000,
			'post_status' => 'publish',
		]));

		$this->hasLeftOvers = $this->hasPosts = ($this->internalTotalCount > 0);

		return $this;

	}

	private function determinePostType () {
		$availablePostTypes = get_post_types();
		foreach ($availablePostTypes as $postType) {
			$archiveLink = trim(get_post_type_archive_link($postType), '/') . '/';
			if ($archiveLink == trim(currentUrl(false), '/') . '/') {
				return $postType;
			}
		}

		return null;
	}


	/**
	 * Get's posts related to this archive
	 * @param  array $arguments
	 * @return array
	 */
	public function getPosts (array $arguments = [], bool $resetInternalOffset = true) : array {

		// Reset internal offset if instreucted to.
		if ($resetInternalOffset) $internalOffset = 0;

		$args = array_merge([
			'post_type' => $this->postType,
			'posts_per_page' => 20,
			'offset' => $internalOffset,
			'post_status' => 'publish',
		], $arguments);

		// Change the internal offset
		$this->internalOffset = $args['offset'];

		// Get the posts
		$this->posts = get_posts($args);

		// Increment the post count
		$this->internalPostCount += count($this->posts);
		$this->hasLeftOvers	= ($this->internalPostCount < $this->internalTotalCount);


		// Reformat the posts
		$this->reformatPosts();

		// Return posts
		return $this->posts;
	}

	/**
	 * Get's the next page of posts
	 * @param  int|integer $limit
	 * @return array
	 */
	public function nextPosts (int $limit = 20) : array {

		if ($this->hasLeftOvers) {
			$args = [
				'post_type' => $this->postType,
				'posts_per_page' => $limit,
				'offset' => $this->internalOffset,
				'post_status' => 'publish',
			];

			// Get posts
			$this->posts = get_posts($args);

			// Increment the post count
			$this->internalPostCount += count($this->posts);
			$this->hasLeftOvers	= ($this->internalPostCount < $this->internalTotalCount);

			// Increment internal offset
			$this->internalOffset += $limit;

			// Reformat the posts
			$this->reformatPosts();

			// Return posts
			return $this->posts;
		}

		return [];
	}

	/**
	 * Loop through and reformat posts
	 * @return void
	 */
	private function reformatPosts() {

		if (!empty($this->posts)) {

			foreach ($this->posts as $key => $post) {
				
				// Post type
				$postType = $post->post_type;

				// Class name
				$className = ucfirst(studly_case($postType));

				if (class_exists($className))
					$this->posts[$key] = new $className($post);
				else
					$this->posts[$key] = new Page($post);

			}
		}

	}

}