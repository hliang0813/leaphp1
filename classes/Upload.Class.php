<?php
visit_limit();

/**
 * 类名：Upload
 * @author hliang
 * @copyright Copyright (c) 2011- neusoft
 * @version 0.1
 */
class Upload {
	private $server_folder;
	private $visit_folder;
	private $file_ary;

	private $server_file_prefix;
	private $date_folder;
	private $local_filename;
	private $server_filename;
	
	private $auto_dump = true;

	/**
	 * 构造函数，初始化
	 *
	 * @param	上传到服务器上，新文件名所带的前缀
	 * @return	无
	 */
	public function __construct($prefix = null, $date_folder = false) {
		$this->server_folder = ROOT . DS . UPLOAD_DIR . DS;
		$this->visit_folder = UPLOAD_DIR . "/";
		$this->file_folder = UPLOAD_DIR . DS;
		if (!empty($prefix)) {
			$this->server_file_prefix = $prefix . "-";
		}

		switch ($date_folder) {
			case 'day':
				$this->date_folder = date("Ymd/");
				break;
			case 'month':
				$this->date_folder = date("Ym/");
				break;
			case 'year':
				$this->date_folder = date("Y/");
				break;
			default:
				$this->date_folder = '';
				break;
		}
		return;
	}

	public function __destruct() {
		return;
	}
	
	/**
	 * 设置自动保存到磁盘
	 * 
	 * @param boolean $audodump
	 */
	public function setAutodump($audodump) {
		$this->auto_dump = $autodump;
	}
	
	/**
	 * 设置上传限制，扩展名，尺寸
	 * @param array $extension
	 * @param int $maxsize
	 */
	public function setLimit($extension = null, $maxsize = null) {
		$this->extension_limit = $extension;
		$this->max_size = $maxsize;
	}

	/**
	 * 发送文件到服务器上
	 *
	 * @param	文件域的 name 属性
	 * @param	上传目录下的子目录，默认以当前年月日创建目录，如： 2011-03-04
	 * @return	上传成功，则返回一个由“访问路径”、“原文件名”两个元素组成的数组。失败则返回 false
	 */
	public function send($field_name, $sub_folder = "", $filename = "") {
		switch ($_FILES[$field_name]['error']) {
			case "1":
				throw new Exception("文件大小超过服务器限制", 1501);
				break;
			case "2":
				throw new Exception("文件大小超过限制", 1502);
				break;
			case "3":
				throw new Exception("文件上传不完整", 1503);
				break;
			case "4":
				throw new Exception("请选择上传文件", 1504);
				break;
		}
		if ($this->max_size) {
			$maxsize = abs(intval($this->max_size)) * 1000;
			if ($_FILES[$field_name]['size'] > $maxsize) {
				
				throw new Exception("文件大小超过限制", 1505);
			}
		}
		$this->make_dir($this->server_folder);
		$this->file_ary = $_FILES[$field_name];
		
		# modified by hliang @ 2012.11.12
		# check file mime-type by a new method
		# from line 113 to 117
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			list($this->file_ary['type'], $charset) = explode(';', finfo_file($finfo, $_FILES[$field_name]['tmp_name']));
			finfo_close($finfo);
		}
		
		
		$this->local_filename = pathinfo($this->file_ary['name']);
		$this->sub_folder = $sub_folder;
		if ($this->checkFiletype()) {
			$date_folder = $this->date_folder;
			if (empty($this->sub_folder)) {
				$this->server_sub_folder = $this->server_folder . $date_folder;
				$this->visit_folder = $this->visit_folder . $date_folder;
			} else {
				$sub_folder_tmp = $this->server_folder . $this->sub_folder;
				if (!file_exists($sub_folder_tmp)) {
					$this->make_dir($sub_folder_tmp);
				}
				$this->server_sub_folder = $sub_folder_tmp . DS . $date_folder;
				$this->visit_folder = $this->visit_folder . $this->sub_folder . "/" . $date_folder;
			}
			$this->make_dir($this->server_sub_folder);

			if (empty($filename)) {
				$this->server_filename = $this->server_file_prefix . mktime() . "." . $this->local_filename['extension'];
			} else {
				$this->server_filename = $filename . "." . $this->local_filename['extension'];
			}
			
			if ($this->auto_dump) {
				return $this->createServerFile();
			} else {
				$file_data = addslashes(@file_get_contents($this->file_ary['tmp_name']));
				return $file_data;
			}
		} else {
			throw new Exception("文件类型错误", 1506);
		}
	}

	/**
	 * 将上传到临时目录中的文件拷贝到目标目录
	 *
	 * @param	无
	 * @return	上传成功，则返回一个由“访问路径”、“原文件名”两个元素组成的数组。失败则返回 false
	 */
	private function createServerFile() {
		if ($this->file_ary['error'] == 0) {
			if (is_uploaded_file($this->file_ary['tmp_name'])) {
				if (!file_exists($this->server_sub_folder)) {
					$this->make_dir($this->server_sub_folder);
				}
				if (move_uploaded_file($this->file_ary['tmp_name'], $this->server_sub_folder . $this->server_filename)) {
					$server_path = $this->file_folder . $this->sub_folder . DS . $this->date_folder . $this->server_filename;
					$uploaded = array(
						"path" => $this->visit_folder . $this->server_filename,
						"filename" => $this->local_filename['filename'],
						"localname" => $this->file_ary['name'],
						"extension" => $this->local_filename['extension'],
						"size" => $this->file_ary['size'],
					);
					list($uploaded['width'], $uploaded['height']) = getimagesize(ROOT . DS . $uploaded['path']);
					return $uploaded;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * 判断上传文件的类型，根据所上传文件的 minetype 来判断
	 *
	 * @param
	 * @return	通过，返回 true，否则返回 false
	 */
	private function checkFiletype() {
		if (is_array($this->extension_limit)) {
			$mimetype = $this->checkMimeType($this->extension_limit);
			if (in_array(strtolower(trim($this->file_ary['type'])), $mimetype)) {
				return true;
			} else {
				return false;
			}
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 服务器上创建目录，并设置权限为 0777
	 *
	 * @param	要创建目录名
	 * @return	创建成功，返回 true。目录已经存在或创建失败，返回 false。
	 */
	private function make_dir($dirname) {
		LeapFunction('mkdirs', $dirname);
	}

	private function checkMimeType($extensions) {
		$mimetype = array(
			'323'   => array('text/h323'),
			'7z'    => array('application/x-7z-compressed'),
			'abw'   => array('application/x-abiword'),
			'acx'   => array('application/internet-property-stream'),
			'ai'    => array('application/postscript'),
			'aif'   => array('audio/x-aiff'),
			'aifc'  => array('audio/x-aiff'),
			'aiff'  => array('audio/x-aiff'),
			'amf'   => array('application/x-amf'),
			'asf'   => array('video/x-ms-asf'),
			'asr'   => array('video/x-ms-asf'),
			'asx'   => array('video/x-ms-asf'),
			'atom'  => array('application/atom+xml'),
			'avi'   => array('video/avi', 'video/msvideo', 'video/x-msvideo'),
			'bin'   => array('application/octet-stream','application/macbinary'),
			'bmp'   => array('image/bmp'),
			'c'     => array('text/x-csrc'),
			'c++'   => array('text/x-c++src'),
			'cab'   => array('application/x-cab'),
			'cc'    => array('text/x-c++src'),
			'cda'   => array('application/x-cdf'),
			'class' => array('application/octet-stream'),
			'cpp'   => array('text/x-c++src'),
			'cpt'   => array('application/mac-compactpro'),
			'csh'   => array('text/x-csh'),
			'css'   => array('text/css'),
			'csv'   => array('text/x-comma-separated-values', 'application/vnd.ms-excel', 'text/comma-separated-values', 'text/csv'),
			'dbk'   => array('application/docbook+xml'),
			'dcr'   => array('application/x-director'),
			'deb'   => array('application/x-debian-package'),
			'diff'  => array('text/x-diff'),
			'dir'   => array('application/x-director'),
			'divx'  => array('video/divx'),
			'dll'   => array('application/octet-stream', 'application/x-msdos-program'),
			'dmg'   => array('application/x-apple-diskimage'),
			'dms'   => array('application/octet-stream'),
			'doc'   => array('application/msword'),
			'docx'  => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
			'dvi'   => array('application/x-dvi'),
			'dxr'   => array('application/x-director'),
			'eml'   => array('message/rfc822'),
			'eps'   => array('application/postscript'),
			'evy'   => array('application/envoy'),
			'exe'   => array('application/x-msdos-program', 'application/octet-stream'),
			'fla'   => array('application/octet-stream'),
			'flac'  => array('application/x-flac'),
			'flc'   => array('video/flc'),
			'fli'   => array('video/fli'),
			'flv'   => array('video/x-flv'),
			'gif'   => array('image/gif'),
			'gtar'  => array('application/x-gtar'),
			'gz'    => array('application/x-gzip'),
			'h'     => array('text/x-chdr'),
			'h++'   => array('text/x-c++hdr'),
			'hh'    => array('text/x-c++hdr'),
			'hpp'   => array('text/x-c++hdr'),
			'hqx'   => array('application/mac-binhex40'),
			'hs'    => array('text/x-haskell'),
			'htm'   => array('text/html'),
			'html'  => array('text/html'),
			'ico'   => array('image/x-icon'),
			'ics'   => array('text/calendar'),
			'iii'   => array('application/x-iphone'),
			'ins'   => array('application/x-internet-signup'),
			'iso'   => array('application/x-iso9660-image'),
			'isp'   => array('application/x-internet-signup'),
			'jar'   => array('application/java-archive'),
			'java'  => array('application/x-java-applet'),
			'jpe'   => array('image/jpeg', 'image/pjpeg'),
			'jpeg'  => array('image/jpeg', 'image/pjpeg'),
			'jpg'   => array('image/jpeg', 'image/pjpeg'),
			'js'    => array('application/x-javascript'),
			'json'  => array('application/json'),
			'latex' => array('application/x-latex'),
			'lha'   => array('application/octet-stream'),
			'log'   => array('text/plain', 'text/x-log'),
			'lzh'   => array('application/octet-stream'),
			'm4a'   => array('audio/mpeg'),
			'm4p'   => array('video/mp4v-es'),
			'm4v'   => array('video/mp4'),
			'man'   => array('application/x-troff-man'),
			'mdb'   => array('application/x-msaccess'),
			'midi'  => array('audio/midi'),
			'mid'   => array('audio/midi'),
			'mif'   => array('application/vnd.mif'),
			'mka'   => array('audio/x-matroska'),
			'mkv'   => array('video/x-matroska'),
			'mov'   => array('video/quicktime'),
			'movie' => array('video/x-sgi-movie'),
			'mp2'   => array('audio/mpeg'),
			'mp3'   => array('audio/mpeg'),
			'mp4'   => array('application/mp4','audio/mp4','video/mp4'),
			'mpa'   => array('video/mpeg'),
			'mpe'   => array('video/mpeg'),
			'mpeg'  => array('video/mpeg'),
			'mpg'   => array('video/mpeg'),
			'mpg4'  => array('video/mp4'),
			'mpga'  => array('audio/mpeg'),
			'mpp'   => array('application/vnd.ms-project'),
			'mpv'   => array('video/x-matroska'),
			'mpv2'  => array('video/mpeg'),
			'ms'    => array('application/x-troff-ms'),
			'msg'   => array('application/msoutlook','application/x-msg'),
			'msi'   => array('application/x-msi'),
			'nws'   => array('message/rfc822'),
			'oda'   => array('application/oda'),
			'odb'   => array('application/vnd.oasis.opendocument.database'),
			'odc'   => array('application/vnd.oasis.opendocument.chart'),
			'odf'   => array('application/vnd.oasis.opendocument.forumla'),
			'odg'   => array('application/vnd.oasis.opendocument.graphics'),
			'odi'   => array('application/vnd.oasis.opendocument.image'),
			'odm'   => array('application/vnd.oasis.opendocument.text-master'),
			'odp'   => array('application/vnd.oasis.opendocument.presentation'),
			'ods'   => array('application/vnd.oasis.opendocument.spreadsheet'),
			'odt'   => array('application/vnd.oasis.opendocument.text'),
			'oga'   => array('audio/ogg'),
			'ogg'   => array('application/ogg'),
			'ogv'   => array('video/ogg'),
			'otg'   => array('application/vnd.oasis.opendocument.graphics-template'),
			'oth'   => array('application/vnd.oasis.opendocument.web'),
			'otp'   => array('application/vnd.oasis.opendocument.presentation-template'),
			'ots'   => array('application/vnd.oasis.opendocument.spreadsheet-template'),
			'ott'   => array('application/vnd.oasis.opendocument.template'),
			'p'     => array('text/x-pascal'),
			'pas'   => array('text/x-pascal'),
			'patch' => array('text/x-diff'),
			'pbm'   => array('image/x-portable-bitmap'),
			'pdf'   => array('application/pdf', 'application/x-download'),
			'php'   => array('application/x-httpd-php'),
			'php3'  => array('application/x-httpd-php'),
			'php4'  => array('application/x-httpd-php'),
			'php5'  => array('application/x-httpd-php'),
			'phps'  => array('application/x-httpd-php-source'),
			'phtml' => array('application/x-httpd-php'),
			'pl'    => array('text/x-perl'),
			'pm'    => array('text/x-perl'),
			'png'   => array('image/png', 'image/x-png'),
			'po'    => array('text/x-gettext-translation'),
			'pot'   => array('application/vnd.ms-powerpoint'),
			'pps'   => array('application/vnd.ms-powerpoint'),
			'ppt'   => array('application/powerpoint'),
			'pptx'  => array('application/vnd.openxmlformats-officedocument.presentationml.presentation'),
			'ps'    => array('application/postscript'),
			'psd'   => array('application/x-photoshop', 'image/x-photoshop'),
			'pub'   => array('application/x-mspublisher'),
			'py'    => array('text/x-python'),
			'qt'    => array('video/quicktime'),
			'ra'    => array('audio/x-realaudio'),
			'ram'   => array('audio/x-realaudio', 'audio/x-pn-realaudio'),
			'rar'   => array('application/rar'),
			'rgb'   => array('image/x-rgb'),
			'rm'    => array('audio/x-pn-realaudio'),
			'rpm'   => array('audio/x-pn-realaudio-plugin', 'application/x-redhat-package-manager'),
			'rss'   => array('application/rss+xml'),
			'rtf'   => array('text/rtf'),
			'rtx'   => array('text/richtext'),
			'rv'    => array('video/vnd.rn-realvideo'),
			'sea'   => array('application/octet-stream'),
			'sh'    => array('text/x-sh'),
			'shtml' => array('text/html'),
			'sit'   => array('application/x-stuffit'),
			'smi'   => array('application/smil'),
			'smil'  => array('application/smil'),
			'so'    => array('application/octet-stream'),
			'src'   => array('application/x-wais-source'),
			'svg'   => array('image/svg+xml'),
			'swf'   => array('application/x-shockwave-flash'),
			't'     => array('application/x-troff'),
			'tar'   => array('application/x-tar'),
			'tcl'   => array('text/x-tcl'),
			'tex'   => array('application/x-tex'),
			'text'  => array('text/plain'),
			'texti' => array('application/x-texinfo'),
			'textinfo' => array('application/x-texinfo'),
			'tgz'   => array('application/x-tar'),
			'tif'   => array('image/tiff'),
			'tiff'  => array('image/tiff'),
			'torrent' => array('application/x-bittorrent'),
			'tr'    => array('application/x-troff'),
			'tsv'   => array('text/tab-separated-values'),
			'txt'   => array('text/plain'),
			'wav'   => array('audio/x-wav'),
			'wax'   => array('audio/x-ms-wax'),
			'wbxml' => array('application/wbxml'),
			'wm'    => array('video/x-ms-wm'),
			'wma'   => array('audio/x-ms-wma'),
			'wmd'   => array('application/x-ms-wmd'),
			'wmlc'  => array('application/wmlc'),
			'wmv'   => array('video/x-ms-wmv', 'application/octet-stream'),
			'wmx'   => array('video/x-ms-wmx'),
			'wmz'   => array('application/x-ms-wmz'),
			'word'  => array('application/msword', 'application/octet-stream'),
			'wp5'   => array('application/wordperfect5.1'),
			'wpd'   => array('application/vnd.wordperfect'),
			'wvx'   => array('video/x-ms-wvx'),
			'xbm'   => array('image/x-xbitmap'),
			'xcf'   => array('image/xcf'),
			'xhtml' => array('application/xhtml+xml'),
			'xht'   => array('application/xhtml+xml'),
			'xl'    => array('application/excel', 'application/vnd.ms-excel'),
			'xla'   => array('application/excel', 'application/vnd.ms-excel'),
			'xlc'   => array('application/excel', 'application/vnd.ms-excel'),
			'xlm'   => array('application/excel', 'application/vnd.ms-excel'),
			'xls'   => array('application/excel', 'application/vnd.ms-excel'),
			'xlsx'  => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
			'xlt'   => array('application/excel', 'application/vnd.ms-excel'),
			'xml'   => array('text/xml', 'application/xml'),
			'xof'   => array('x-world/x-vrml'),
			'xpm'   => array('image/x-xpixmap'),
			'xsl'   => array('text/xml'),
			'xvid'  => array('video/x-xvid'),
			'xwd'   => array('image/x-xwindowdump'),
			'z'     => array('application/x-compress'),
			'zip'   => array('application/x-zip', 'application/zip', 'application/x-zip-compressed')
		);
		foreach ($extensions as $value) {
			if (is_array($mimetype[$value])) {
				foreach ($mimetype[$value] as $mimekey => $mimevalue) {
					$return[] = $mimevalue;
				}
			}
		}
		return $return;
	}
}


/*
使用方法：

$ul = new Upload;
if ($file = $ul->send("upload_file_field_name")) {
	echo $file['vist'];
	echo $file['filename'];
}
*/