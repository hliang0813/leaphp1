<?php
visit_limit();

class import {
	static public function biz($biz_name) {
		$biz_name = str_replace(':', DS, $biz_name);
		$biz_file = BUSINESS_DIR . DS . $biz_name . '.Class.php';
		if (file_exists($biz_file)) {
			include_once $biz_file;
			unset($biz_file);
		} else {
			throw new Exception('Could not find the business file.', 824200007);
		}
	}

	static public function model($model_name) {
		if ($model_file = realpath(APP_PATH . DS . 'models' . DS . $model_name . '.Model.php')) {
			include_once $model_file;
			unset($model_file);
		} else {
			throw new Exception('Could not find the model file.', 824200008);
		}
	}

	static public function load($file_path) {
		if($file_real_path = realpath($file_path)) {
			include_once $file_real_path;
			unset($file_real_path);
		} else {
			throw new Exception('Could not file file.', 824200009);
		}
	}
}