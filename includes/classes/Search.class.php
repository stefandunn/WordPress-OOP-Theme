<?php

class Search
{

    public $keywords       = '';
    public $results        = [];
    public $internalOffset = 0;
    public $hasResults, $hasLeftOvers, $totalCount, $pages, $currentPage, $resultCount;

    private $args = [];

    /**
     * Create new instance of Search with optional keywords
     * @param     array $args The arguments to setup search class
     * @param     bool $performSearch
     * @return  void
     */
    public function __construct(array $arguments = [], bool $performSearch = true): void
    {

        $this->args = array_merge([
            'post_type'        => ['post', 'page', 'service', 'staff'],
            'max_results'      => 10,
            'results_per_page' => 10,
            'order_by'         => 'post_date',
            'order'            => 'ASC',
            'post_status'      => 'publish',
        ], $arguments);

        // Set keywords
        $this->setKeyWords((isset($_GET['kw'])) ? $_GET['kw'] : '');

        // Set offset
        $this->internalOffset = (isset($_GET['offset']) && is_numeric($_GET['offset'])) ? $_GET['offset'] : 0;
        $this->currentPage    = ($this->internalOffset != 0) ? ceil($this->internalOffset / $this->args['results_per_page']) + 1 : 1;

        // Perform search if keywords exsist
        if ($performSearch && !empty($this->keywords)) {
            $this->search();
        }

    }

    /**
     * Set's keywords for the search query
     * @param     string $keywords
     * @return     void
     */
    public function setKeywords(string $keywords): void
    {
        $this->keywords = "{$keywords}";
    }

    /**
     * Get's the meta fields from Advanced Custom Fields excluding any keywords that appear in $excludedKeywords that appear in the meta_key
     * @param  array  $excludedKeywords
     * @return array
     */
    private function getACFFields(array $excludedKeywords): array
    {
        global $wpdb;
        $ACFs = $wpdb->get_col('SELECT `meta_key` FROM `wp_postmeta` WHERE `meta_value` LIKE "field_%" GROUP BY `meta_value`');
        $ACFs = array_map(function ($field) {return substr($field, 1);}, $ACFs);

        // Remove certain keys
        foreach ($ACFs as $key => $field) {

            // Remove excluded keywords meta fields
            foreach ($excludedKeywords as $keyword) {
                if (strstr($field, "{$keyword}") !== false) {
                    unset($ACFs[$key]);
                }

            }
        }

        return $ACFs;
    }

    /**
     * Do the search
     * @return array
     */
    public function search(): array
    {
        global $wpdb;

        // Get post types
        $postTypes = (is_string($this->args['post_type'])) ? explode("|", $this->args['post_type']) : $this->args['post_type'];

        $sql = "SELECT `posts`.id FROM `{$wpdb->posts}` as `posts` INNER JOIN `{$wpdb->postmeta}` as `postmeta` ON `posts`.`id` = `postmeta`.`post_id` WHERE ";

        // Add contraints to post types
        if (!empty($postTypes)) {
            $sql .= "`posts`.`post_type` IN ('" . implode("', '", $postTypes) . "')";
        }

        $sql .= " AND ((";

        // Where conditions for post data
        foreach (['post_title', 'post_excerpt', 'post_content'] as $columnName) {
            $sql .= "posts.{$columnName} LIKE '%%%s%%' OR ";
            $args[] = $this->keywords;
        }
        $sql = rtrim($sql, " OR ") . ")";

        // Where conditions for meta fields
        $metaFields = $this->getACFFields(['logo', 'image', 'photo', 'portrait']);

        // If meta fields available
        if (!empty($metaFields)) {
            $sql .= " OR (";
            foreach ($metaFields as $field) {
                $sql .= "(`postmeta`.`meta_key` = '{$field}' AND `postmeta`.`meta_value` LIKE '%%%s%%') OR ";
                $args[] = $this->keywords;
            }
            $sql = rtrim($sql, " OR ") . ")";
        }

        $sql .= ")";

        // Add constraints
        $sql .= " GROUP BY `posts`.`id` ORDER BY `posts`.`" . $this->args['order_by'] . "` " . $this->args['order'];

        // Get total count
        $this->totalCount = count($wpdb->get_col($wpdb->prepare($sql, $args)));

        // Add limits
        $sql .= " LIMIT %d OFFSET %d";
        $args[] = $this->args['results_per_page'];
        $args[] = $this->internalOffset;

        $postIds = $wpdb->get_col($wpdb->prepare($sql, $args));

        if (!empty($postIds)) {
            foreach ($postIds as $postId) {
                $post = get_post($postId);
                switch ($post->post_type) {
                    case "service":
                        $this->results[] = new Service($post);
                        break;
                    case "staff":
                        $this->results[] = new Staff($post);
                        break;
                    default:
                        $this->results[] = new Page($post);
                        break;
                }
            }
        }

        // Limit results
        if (count($this->results) > $this->args['max_results']) {
            $this->results    = array_slice($this->results, 0, $this->args['max_results']);
            $this->totalCount = $this->args['max_results'];
        }

        $this->resultCount = count($this->results);

        $this->hasLeftOvers = ($this->totalCount > ($this->resultCount - ($this->internalOffset * $this->args['results_per_page'])));
        $this->hasResults   = (!empty($this->results));
        $this->pages        = ($this->hasResults) ? ceil($this->totalCount / $this->args['results_per_page']) : 1;

        return $this->results;
    }

    /**
     * Highlights the words of interest
     * @param  string $content
     * @param  string $class
     * @return string
     */
    public function highlightKeywords(string $content, string $class = 'bg-yellow'): string
    {
        return str_ireplace($this->keywords, "<span class='{$class}'>{$this->keywords}</span>", $content);
    }

    /**
     * Generates a URL to navigate to the page specified by $pageNo
     * @param  string $pageNo
     * @return string
     */
    public function pageLink(string $pageNo): string
    {
        $newOffset = ($this->args['results_per_page'] * ($pageNo - 1));
        return currentURL(false) . "?kw={$this->keywords}&offset={$newOffset}";
    }
}
