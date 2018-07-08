<?php

if (!is_admin()) {
    spl_autoload_register(function ($class) {
        if (file_exists(__DIR__ . "/classes/{$class}.class.php")) {
            @include __DIR__ . "/classes/{$class}.class.php";
        }

    }, true, true);
}
