<?php
visit_limit();

class App extends Base {
	static public $pathinfo_separater = '/';

	static public $controller;
	static public $action;
	static public $params = array();
	static public $action_param_string = '';
	
	static public $app_base = '';
	static public $controller_file = '';
	
	static public $template_dir = '';
	static public $template_compile_dir = '';
	static public $template_cache_dir = '';
	
	static public $pathinfo = array();
	static public $script_filename = array();
	static public $script_name = array();
	
	# 解析pathinfo
	static private function parsePathinfo() {
		# 解析pathinfo
		if (defined('URLS')) {	# pathinfo模式
			if (!file_exists(URLS)) {
				throw new Exception('Could not find router file.', 824200003);
			} else {
				$urls = include URLS;
				$server_pathinfo = isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : $_SERVER['PATH_INFO'];
				foreach ( (array)$urls as $pattern => $router) {
					preg_match($pattern, $server_pathinfo, self::$params);
					if (!empty(self::$params)) {
						list(self::$controller, self::$action) = explode('.', $router);
						unset(self::$params[0]);
						self::$action_param_string = "'" . implode("', '", self::$params) . "'";
						break;
					}
				}
			}
		} else {	# 普通模式
			self::$controller = isset($_REQUEST['ctl']) ? $_REQUEST['ctl'] : 'index';
			self::$action = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'index';
		}

		if (!isset(self::$controller) || !isset(self::$action)) {
			throw new Exception('Could not find router rule.', 824200010);
		}

		# 设置模板变量
		if (defined('LEAP_GLOBAL_ACTION') && LEAP_GLOBAL_ACTION == 'MANAGER') {
			self::$controller_file = LEAP_DIR . DS . 'core' . DS . 'manager' . DS . 'actions' . DS . 'ManagerAction.ctrl.php';
			self::$template_dir = LEAP_DIR . DS . 'core' . DS . 'manager' . DS . 'templates' . DS;
		} else {
			self::$controller_file = self::$app_base . DS . 'actions' . DS . self::$controller . '.ctrl.php';
			self::$template_dir = self::$app_base .  DS . 'templates' . DS;
		}
		self::$template_compile_dir = self::$app_base . DS . 'templates_c' . DS;
		self::$template_cache_dir = self::$app_base . DS . 'caches' . DS;

	}
	
	
	# 初始化常量
	static public function initConst() {
		# 程序入口访问路径
		define('ENTRY', $_SERVER['SCRIPT_NAME']);
		# 程序入口文件名
		define('ENTRY_FILENAME', self::$script_name['basename']);
		# 程序的访问路径
		$path = (self::$script_name['dirname'] == '\\' || self::$script_name['dirname'] == '/') ? '' : self::$script_name['dirname'];
		define('PATH', $path);
		# 程序的服务器路径
		if (!defined('APP_PATH')) {
			define('APP_PATH', self::$script_filename['dirname']);
		}
	}

	# CLI模式
	static public function cliModeParams() {
		$cli_argvs = $GLOBALS['argv'];
		unset($cli_argvs[0]);
		$params_str = @implode('&', $cli_argvs);
		parse_str($params_str, $params);
		foreach ( (array)$params as $key => $value) {
			$_GET[ltrim($key, '--')] = trim($value);
		}
	}

	# 应用开始
	static public function run() {
		if (!defined('LEAP_START')) {
			define('LEAP_START', true);
		}

		# 以CLI模式访问
		if (PHP_SAPI == 'cli') {
			self::cliModeParams();
		}

		parent::checkPhpVersion();
		# 应用路径
		self::$script_name = pathinfo($_SERVER['SCRIPT_NAME']);
		self::$script_filename = pathinfo($_SERVER['SCRIPT_FILENAME']);
		self::$app_base = ROOT . DS . APP_NAME;
		# 解析pathinfo
		self::parsePathinfo();
		# 初始化常量
		self::initConst();
		# 找controller文件
		if (file_exists(self::$controller_file)) {
			include_once self::$controller_file;
			$app = new self::$controller;
			# 找action方法
			if (method_exists($app, self::$action)) {
				$method = self::$action;
				call_user_func_array(array($app, $method), self::$params);
			} else {
				throw new Exception('Unsigned action.', 824200005);
			}
		} else {
			throw new Exception('Unsigned controller.', 824200004);
		}
	}
}
