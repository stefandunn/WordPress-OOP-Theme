<?php

global $messages;
$messages = [];

/**
 * Functions to perform before rendering the page
 */
function beforeRender()
{

    // Execute AJAX calls
    if (isAjaxRequest()) {
        ajaxCalls();
    }

}

/**
 * Perform these AJAX Calls in sequential order
 * @return void
 */
function ajaxCalls()
{

    // Set the JSON response header
    header('Content-Type: application/json');
}

add_action('wp', 'beforeRender');
