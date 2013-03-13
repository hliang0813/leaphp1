<?php
visit_limit();

include_once __DIR__ . "/log4php/Logger.php";
$log_config_file = CONFIG_DIR . DS . "log" . DS . "log4php.properties";
if (file_exists($log_config_file)) {
	if (!defined('USE_LOG')) {
		define ("USE_LOG", TRUE);
		Logger::configure($log_config_file);
	}
} else {
	throw new Exception('Could not find configure file for Log4php.', 824209021);
}
