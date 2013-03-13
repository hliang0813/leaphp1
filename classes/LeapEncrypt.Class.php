<?php
visit_limit();

class LeapEncrypt {
	static private $private_key = "cntvneusoft";
	static public function encrypt_encode($txt) {
		srand((double)microtime() * 1000000);
		$rand = rand( 1, 10000 );
		$encrypt_key = md5( $rand );
		$ctr = 0;
		$tmp = '';
		for($i = 0;$i < strlen($txt); $i++) {
			$ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
			$tmp .= $encrypt_key[$ctr].($txt[$i] ^ $encrypt_key[$ctr++]);
		}
		$tmp = LeapEncrypt::encrypt_key( $tmp, LeapEncrypt::$private_key );
		return base64_encode( $tmp );
	}
	static function encrypt_decode($txt) {
		$txt = base64_decode( $txt );
		$txt = LeapEncrypt::encrypt_key( $txt, LeapEncrypt::$private_key );
		$tmp = '';
		for ($i = 0;$i < strlen($txt); $i++) {
			$md5 = $txt[$i];
			$tmp .= $txt[++$i] ^ $md5;
		}
		return $tmp;
	}
	function encrypt_key($txt, $encrypt_key) {
		$encrypt_key = md5($encrypt_key);
		$ctr = 0;
		$tmp = '';
		for($i = 0; $i < strlen($txt); $i++)
		{
			$ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
			$tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
		}
		return $tmp;
	}
}


