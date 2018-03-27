<?php

global $messages;
$messages = [];

/**
* Functions to perform before rendering the page
*/
function beforeRender () {

}

add_action('wp', 'beforeRender');