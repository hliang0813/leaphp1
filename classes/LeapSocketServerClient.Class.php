<?php
visit_limit();

class LeapSocketServerClient{
	private $_host;
	private $_port;
	private $_error;
	private $_socket;
	private $_class;
	
	function __construct($host, $port) {
		$this->_host = $host;
		$this->_port = $port;
		$this->_class = '';
		$this->_connect();
	}

	private function _connect() {
		$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if($sock === false){
			$this->_error('error socket_create'.socket_strerror(socket_last_error()));
		}

		$ret = socket_connect($sock, $this->_host, $this->_port);
		if($ret === false){
			$this->_error('error socket_connect'.socket_strerror(socket_last_error()));
		}
 
		$this->_socket = $sock;
	}
 
	public function obj($class) {
		$this->_class = $class;
		return $this;
	}
 
	public function __call($func, $args) {
		$data = array('class' => $this->_class, 'func' => $func, 'args' => $args);
		$data = json_encode($data);
		$socket = $this->_socket;
		$res = socket_write($socket, $data, strlen($data));
		if($res===false){
			$this->_error('error socket_write'.socket_strerror(socket_last_error()));
		}
		$res = socket_read($socket, 1024, PHP_BINARY_READ);
		$result = substr($res, 8);
		$len = intval(substr($res, 0, 8));
		while(true){
			if($len != strlen($result)) {
				$result .= socket_read($socket, 1024, PHP_BINARY_READ);
			}else{
				break;
			}
		}
		return $result;
	}
 
	private function _error($errMsg = '') {
		$this->_error = $errMsg;
		echo $errMsg;
		exit();
	}
 
	public function __destruct() {
		$socket = $this->_socket;
		socket_write($socket, '', 0);
		socket_close($socket);
	}
}

# Example:
/*
$s = new server('127.0.0.1', 1990);
$res = $s->obj('Todo')->testFunc('what is it');
echo '<pre>' . $res . '</pre>';
*/
