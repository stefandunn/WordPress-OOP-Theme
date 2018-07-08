<?php

include "includes/master-theme.php";

// Front-end functions only
if (!is_admin())
{
	include "includes/class-autoload.php";
	include "includes/alt-helpers.php";
	include "includes/custom-functions.php";
	include "includes/search-helpers.php";
	include "includes/script-and-styles.php";
	include "includes/before-render.php";
}