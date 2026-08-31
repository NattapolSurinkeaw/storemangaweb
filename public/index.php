<?php
/**
 * Front Controller
 * Entry point ของทุก Request
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/Model.php';
require_once APP_PATH . '/core/App.php';

$app = new App();
