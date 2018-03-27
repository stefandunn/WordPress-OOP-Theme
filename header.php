<!doctype html>
<html lang="en" xml:lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
        <?php wp_head(); ?>
    </head>
    <body data-base-uri="<?= home_url(); ?>" <?= body_class("normal"); ?> data-device="<?= getDevice(); ?>">