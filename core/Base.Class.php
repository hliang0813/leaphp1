<?php
visit_limit();

class Base {
	static $control_version = "5.3.0";		# 要求最低PHP版本号
	static $framework_version = "0.4.1";		# LeaPHP框架版本号

	/**
	 * 是否以某字符串开头
	 *
	 * @param 	字符串 $str
	 * @param 	搜索 $match
	 * @return 	真|假
	 */
	protected function startWith($str, $match) {
		if (substr($str, 0, strlen($match)) == $match) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 判断PHP版本，
	 *
	 * @param
	 * @return
	 */
	static function checkPhpVersion() {
		if (PHP_VERSION < self::$control_version) {
			throw new Exception('PHP version is to low. At least ' . self::$control_version);
		}
	}

}
