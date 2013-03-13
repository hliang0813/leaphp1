<?php
visit_limit();

function leap_function_mkdirs($dir) {
	if(!is_dir($dir))
	{
		if(!leap_function_mkdirs(dirname($dir))){
			return false;
		}
		if(!mkdir($dir,0777)){
			return false;
		} else {
			chmod($dir, 0777);
		}
	}
	return true;
}
