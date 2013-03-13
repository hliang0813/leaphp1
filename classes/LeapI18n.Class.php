<?php
visit_limit();

class LeapI18n {
	static $client_lang;
	static $language;
	
	static public function init() {
		self::$client_lang = self::checkLang();
		$lang_file =  LANG_DIR . DS . self::$client_lang . '.lang.php';
		if (file_exists($lang_file)) {
			self::$language = include $lang_file;
		}
	}

	static public function gettext($msgid, $params = array()) {
		if (array_key_exists($msgid, (array)self::$language)) {
			$i18n_string = self::$language[$msgid];
			foreach ($params as $key => $value) {
				$i18n_string = str_replace('$' . $key, $params[$key], $i18n_string);
			}
			return $i18n_string;
		} else {
			return $msgid;
		}
	}

	static private function checkLang() {
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 4);
		if (preg_match("/zh-c/i", $lang)) {
			$lang = 'chinese_s';
		} else if (preg_match("/zh/i", $lang)) {
			$lang = 'chinese_t';
		} else if (preg_match("/en/i", $lang)) {
			$lang = 'english';
		} else if (preg_match("/fr/i", $lang)) {
			$lang = 'french';
		} else if (preg_match("/de/i", $lang)) {
			$lang = 'german';
		} else if (preg_match("/jp/i", $lang)) {
			$lang = 'japanese';
		} else if (preg_match("/ko/i", $lang)) {
			$lang = 'korean';
		} else if (preg_match("/es/i", $lang)) {
			$lang = 'spanish';
		} else if (preg_match("/sv/i", $lang)) {
			$lang = 'swedish';
		} else {
			$lang = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
		}
		return $lang;
	}
}

function __($msgid) {
	$params = func_get_args();
	unset($params[0]);
	return LeapI18n::gettext($msgid, $params);
}

function _L($msgid) {
	return __($msgid);
}

