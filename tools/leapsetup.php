#!/usr/bin/env php
<?php
define('ROOT', __DIR__);
define('APP_NAME', 'eztools');
define('DEBUG', true);
define('URLS', __DIR__ . '/EzTools.Urls.php');
include __DIR__ . '/../LeaPHP.php';
App::run();
