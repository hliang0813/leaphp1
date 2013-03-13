<?php
class ToolsAction extends Action {
	private $cli_params;

	public function index() {
		if (PHP_SAPI != 'cli') {
			die('此脚本只能在cli模式下运行');
		}
		$this->cli_params = $GLOBALS['argv'];
		switch ($this->cli_params[1]) {
			case '':
			case '-h':
				$this->help();
				break;
			case 'startapp':
				$this->startapp();
				break;
			case 'startaction':
				$this->startaction();
				break;
			case 'startmodel':
				$this->startmodel();
				break;
			default:
				echo "Command '" . $this->cli_params[1] . "' not found !\r\n";
				break;
		}
	}

	# 安装应用
	private function startapp() {
		if (count($this->cli_params) != 3) {
			die("Please enter your application name.\r\n");
		} else {
			list(,,$app_name) = $this->cli_params;
			$entry_name = $app_name;
			$urls_name = $app_name . '.urls';
		}

		echo "Installing, please wait ...\r\n";

		$app_path = $_SERVER['PWD'] . DS . $app_name;
		
		# 创建主要目录
		$actions = LeapFunction('mkdirs', $app_path . DS . 'actions');
		$models = LeapFunction('mkdirs', $app_path . DS . 'models');
		$templates = LeapFunction('mkdirs', $app_path . DS . 'templates');

		# 创建控制器文件
		$controller_file = $app_path . DS . 'actions' . DS . 'IndexAction.ctrl.php';
		if ($actions && !file_exists($controller_file)) {
			$controller_file_content = '<?php
class IndexAction extends Action {
	public function __construct() {
		parent::__construct();
	}
	public function index() {
		Copyright::frameworkDefaultPage();
	}
}';
			file_put_contents($controller_file, $controller_file_content);
		}

		# 创建入口文件
		$entry_file = $_SERVER['PWD'] . DS . $entry_name . '.php';
		if (!file_exists($entry_file)) {
			$entry_file_content = "<?PHP
define('ROOT', __DIR__);				# 必须
define('APP_NAME', '" . $app_name . "');		# 应用名称
define('DEBUG', true);					# 是否开启debug模式，true|false
define('URLS', __DIR__ . '/" . $urls_name . ".php');	# 指定路由文件
include '" . LEAP_DIR . DS . "LeaPHP.php';		# 引入框架主文件
App::run();						# 开始进入程序";
			file_put_contents($entry_file, $entry_file_content);
		}

		# 创建路由器文件
		$urls_file = $_SERVER['PWD'] . DS . $urls_name . '.php';
		if (!file_exists($urls_file)) {
			$urls_file_content = "<?php
return array(
	# 在这里添加你自己的路由规则...
	'/^$/' => 'IndexAction.index',
	# ...
);";
			file_put_contents($urls_file, $urls_file_content);
		}
		echo "Your application '" . $app_name . "' has been installed successfully!\r\n";
	}

	# 新建控制器
	private function startaction() {

	}

	# 新建ORM对象
	private function startmodel() {
		if (count($this->cli_params) < 4) {
			die("parameters error\r\n");
		} else {
			list(,,$app_name, $model_name) = $this->cli_params;
		}
		
		if (!isset($_SERVER['PWD'])) {
			$_SERVER['PWD'] = '.';
		}

		if (!file_exists($_SERVER['PWD'] . DS . $app_name)) {
			die("application not found !\r\n");
		}

		$model_file = $_SERVER['PWD'] . DS . $app_name . DS . 'models' . DS . $app_name . '.Model.php';

		if (!file_exists($model_file)) {
			$fp = fopen($model_file, 'w');
			fwrite($fp, "<?php\r\n");
			fclose($fp);
		}

		include_once $model_file;
		if (!class_exists($model_name)) {
			$pk = isset($this->cli_params[4]) ? $this->cli_params[4] : '';

			$statuements = array();
			while(true) {
				echo 'Enter your statuements name: ';
				$s = fgets(STDIN);
				if (trim($s) == '') {
					break;
				}
				array_push($statuements, '\'' . trim($s) . '\' => \'input\',');
			}
			$statuements_string = implode("\r\n\t\t", $statuements);

			$model_content = 'class ' . $model_name . ' extends Model {
	public $table = \'' . $model_name . '\';
	public $pk = \'' . $pk . '\';
	public $statuements = array(
		' . $statuements_string . '
	);
}' . "\r\n";
			if (count($statuements) > 0) {
				file_put_contents($model_file, $model_content, FILE_APPEND);
			}
		} else {
			die("Model '" . $model_name . "' has already been created !\r\n");
		}
	}

	# 帮助文档
	private function help() {
		$output = "
usage:	ezsetup.php startapp [appname]
	ezsetup.php startmodel [appname] [modelname] [primarykey]\r\n";
		echo $output;
	}
}
