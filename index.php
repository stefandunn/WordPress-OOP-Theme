<?php
/**
 * Main template and fallback template for theme.
 * Also includes check to determine if accessed directly.
 */
if (!defined('ABSPATH')) {header('Location: http://' . $_SERVER['HTTP_HOST']) and exit;}

getTheHeader();
?>

	<!-- Blog index -->

<?php

getTheFooter();