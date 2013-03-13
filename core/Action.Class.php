<?php
visit_limit();

class Action extends App {
	var $template;
	var $use_template;

	public function __construct($use_template = true) {
		$this->use_template = $use_template;
		
		# 映射参数
		if ($this->use_template) {
			# 初始化template		
			$this->template = new Smarty;
			$this->template->setCompileDir(self::$template_compile_dir);
			$this->template->setCacheDir(self::$template_cache_dir);
			$this->template->setLeftDelimiter("<{");
			$this->template->setRightDelimiter("}>");
			$this->template->setTemplateDir(self::$template_dir);
		}
	}

	# 模板变量赋值
	public function assign($mark, $value) {
		if ($this->use_template) {
			$this->template->assign($mark, $value);
		} else {
			throw new Exception('Template(s) unactived.', 824200006);
		}
	}

	# 显示模板
	public function display($template_file = null) {
		if ($this->use_template) {
			if ($template_file == null) {
				$template_file = ltrim(self::$controller . '/' . self::$action . ".html", '/');
			}
			$this->template->display($template_file);
		} else {
			throw new Exception('Template(s) unactived.', 824200006);
		}
	}

	# 保存静态页
	/*public function save($save_file, $template_file = null) {
		if ($this->use_template) {
			if ($template_file == null) {
				$template_file = ltrim(TEMPLATE_DIR . DS . METHOD_NAME . ".html", '/');
			}
			$source_code = $this->template->fetch($template_file);
			if (file_put_contents($save_file, $source_code)) {
				$static_file_ends = substr(trim(file_get_contents($save_file)), -7);
				echo $static_file_ends;
			} else {
				return false;
			}
		} else {
			trigger_error('还没有开启使用模板功能。');
		}
		
	}*/

	# 页面跳转
	public function redirect($url = '') {
		$url = $url == '' ? $_SERVER['HTTP_REFERER'] : $url;
		#header('Location: ' . $url);
		echo '<script>window.location="' . $url . '";</script>';
		exit;
	}

	# $_GET
	public function _get($key) {
		return $_GET['key'];
	}

	# $_POST
	public function _post($key) {
		return $_POST['key'];
	}

	# $_REQUEST
	public function _request($key) {
		return $_REQUEST['key'];
	}

	# $_SESSION
	public function _session($key) {
		return $_SESSION['key'];
	}
}
