<?php
/**
 * 读取 ini 配置文件
 *
 * 整个配置文件为一个对象
 * 将配置文件中每个配置项以对象形成员方式返回
 */

visit_limit();

class Settings {
	private static $instance;
	private $settings;

	/**
	 * 构造函数
	 *
	 * @param 	String $ini_file
	 */
	private function __construct($ini_file) {
		$this->settings = parse_ini_file($ini_file, true);
		return;
	}
	/**
	 * 静态方法
	 *
	 * @param 	String $ini_file
	 * @return 	ini 对象 
	 */
	public static function getInstance($ini_file) {
		if(! isset(self::$instance)) {
			self::$instance = new Settings($ini_file);		
		}
		return self::$instance;
	}
	/**
	 * 魔术方法
	 *
	 * @param 	String $setting
	 * @return unknown
	 */
	public function __get($setting) {
		if(array_key_exists($setting, $this->settings)) { 
			return $this->settings[$setting]; 
		} else {
			foreach($this->settings as $section) {
				if(array_key_exists($setting, $section)) {
					return $section[$setting];
				}
			}
		}
	}
}