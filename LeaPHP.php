<?php
/*
说明：框架主文件。
功能：框架内部方法库自动加载、安全性过滤。
作者：huang.liang@neusoft.com
最后更新：2012-08-09
*/
#error_reporting(0);

session_start();
header("Content-type: text/html; charset=utf-8");

if (!defined('DEBUG')) {
	define('DEBUG', false);
}

if (!defined('PATHINFO')) {
	define('PATHINFO', true);
}

date_default_timezone_set('Asia/Shanghai');

define('LEAP_START', true);

if (!defined('DS')) {
	define ('DS', DIRECTORY_SEPARATOR);
}

if (!defined('LEAP_DIR')) {
	define ('LEAP_DIR', __DIR__);
}
if (!defined('APP_PATH')) {
	define('APP_PATH', ROOT . DS . APP_NAME);
}
if (!defined('CONFIG_DIR')) {
	define('CONFIG_DIR', ROOT . DS . 'configs');
}
if (!defined('BUSINESS_DIR')) {
	define('BUSINESS_DIR', ROOT . DS . 'business');
}
if (!defined('UPLOAD_DIR')) {
	define('UPLOAD_DIR', 'uploads');
}
if (!defined('LANG_DIR')) {
	define('LANG_DIR', ROOT . DS . 'lang');
}

# LeaPHP 统一异常处理
function LeaphpException($e) {
	$error = sprintf('[ERROR #%d] %s', $e->getCode(), $e->getMessage());
	echo $error;
	if (DEBUG) {
		echo '<div style="font-size:13px;"><pre>', $e->getTraceAsString(), '</div>';
	}
	exit();
}
set_exception_handler('LeaphpException');


# 加载全局配置文件
$global_config_file = LEAP_DIR . DS . 'configs' . DS . 'global.settings.ini';
if (file_exists($global_config_file)) {
	$global_config = parse_ini_file($global_config_file, true);
	foreach((array)$global_config['General'] as $key => $value) {
		if (!defined(strtoupper($key))) {
			define (strtoupper($key), $value);
		}
	}
} else {
	throw new Exception('Could not find global configure file.', 824200001);
}

# 自动装载类库
function LeapClassAutoload($class_name) {
	$loaded = false;
	# 根据leaphp的目录结构设置自动装载的位置
	$cmap = array(
		'Action' => '',
		'App' => '',
		'Base' =>'',
		'Copyright' => '',
		'Model' => '',
		
		'DataBase' => 'db' . DS,
		'Db' => 'db' . DS,
		'MasterSlave' => 'db' . DS,
		
		'PageNav' => 'library' . DS,
	);
	if (array_key_exists($class_name, $cmap)) {
		$class_file = realpath(LEAP_DIR . DS . 'core' . DS . $cmap[$class_name] . $class_name . '.Class.php');
	} else {
		$class_file = realpath(LEAP_DIR . DS . str_replace('/', DS, CLASSES_DIR) . DS . $class_name . '.Class.php');
	}
	if (file_exists($class_file)) {
		include $class_file;
		$loaded = true;
	}
}
spl_autoload_register('LeapClassAutoload');

function visit_limit() {
	if (!defined('LEAP_START')) {
		LeapFunction('sendheader', 404);
	}
}

# 自动装载函数库
function LeapFunction() {
	$params = func_get_args();
	switch (func_num_args()) {
		case 0:
		throw new Exception('Parameter(s) error while using autoload function(s).', 824209015);
			break;
		default:
			$function_name = 'leap_function_' . $params[0];
			if (!function_exists($function_name)) {
				$function_file = LEAP_DIR . DS . FUNCTIONS_DIR . DS . 'function.' . $params[0] . '.php';
				if (file_exists($function_file)) {
					include_once $function_file;
				} else {
					throw new Exception('Autoload function(s) not found.', 824209016);
				}
			}
			unset($params[0]);
			return call_user_func_array($function_name, $params);
			break;
	}
}

# 注册HOOK函数
function LeapHook_register($hookname, $callback) {
	$GLOBALS['_LEAP_HOOK'][$hookname][] = $callback;
}

# 调用HOOK函数
function LeapHook_add($hookname, $data) {
	foreach ((array)$GLOBALS['_LEAP_HOOK'][$hookname] as $hook) {
		return $hook($data);
	}
	return $data;
}

# 加载model文件
$model_file = ROOT . DS . APP_NAME . DS . 'models' . DS . APP_NAME . '.Model.php';
if (file_exists($model_file)) {
	include_once $model_file;
}

# 开启i18n多语言支持
LeapI18n::init();

# 引入Application类，可以通过自动加载，直接include可以提高加载速度
$app_entry = LEAP_DIR . DS . 'core' . DS . 'App.Class.php';
if (file_exists($app_entry)) {
	include_once $app_entry;
}

