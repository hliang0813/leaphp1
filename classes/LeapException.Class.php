<?php
visit_limit();

# 系统级别异常错误代码
define ("SYS_ERR_CODE", 99);

/**
 * 函数名：exception_handler
 * 描述：自动捕获未被捕获的异常
 * @param Exception $exception
 */
/*function exception_handler($exception) {
	echo "Uncaught LeapException: [{$exception->getCode()}] {$exception->getMsg()}";
}
set_exception_handler("exception_handler");*/

/**
 * 类名：LeapException
 * 描述：异常捕获类
 * @author hliang
 * @copyright Copyright (c) 2011- neusoft
 * @version 0.1
 */
class LeapException extends Exception {
	/**
	 * 方法名：getMsg
	 * 描述：获取异常信息
	 */
	public function getMsg() {
		return $this->getMessage();
	}
}