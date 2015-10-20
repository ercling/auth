<?php
defined('APPMODE_DEBUG') or define('APPMODE_DEBUG', true);
$loader = require(__DIR__ . '/../vendor/autoload.php');
$config = require(__DIR__ . '/../config/web.php');
(new base\Application($config))->run();