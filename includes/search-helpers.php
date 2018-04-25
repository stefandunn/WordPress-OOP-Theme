<?php

global $search;
$search = null;

/**
 * Returns a global instance of the Search class
 * @return Search
 */
function search() {
	global $search;
	// Initiate if not already
	if (is_null($search))
		$search = new Search();

	// Return global
	return $search;
}