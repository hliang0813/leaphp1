<?php
/*
说明：分页类。
功能：对列表数据分页操作。
作者：huang.liang@neusoft.com
最后更新：2012-08-09
*/

visit_limit();

class PageNav extends Base {
	private $object;
	private $db;

	public $page_size;
	public $current_page;
	public $total_page;
	public $total_record;
	public $prev_page;
	public $next_page;

	private $first_word = '首页';
	private $prev_word = '上一页';
	private $next_word = '下一页';
	private $last_word = '尾页';
	private $total_pre_word = '共有';
	private $total_end_word = '页';
	private $current_pre_word = '当前第';
	private $current_end_word = '页';
	private $no_page_word = '';

	private $record_start;

	private $param = 'page';
	private $query_string;
	private $output_str;

	private $num_show = 3;
	private $space = '&nbsp;&nbsp;';
	private $pageRender;

	private $listStatuement;
	private $countStatuement;
	private $conditionSql;

	private $conditionLimit;

	/**
	 * 初始化
	 *
	 * @param	数据库操作对象
	 * @param	每页显示记录数
	 * @param	用来分页的变量参数
	 * @return
	 */
	public function __construct($object, $current=1, $size=20) {
		$this->db = $object;
		$current = $current == 0 ? 1 : $current;
		$this->current_page = @abs(intval($current));
		if (!$this->current_page) {
			$this->current_page = 1;
		}
		$this->page_size = intval($size) == 0 ? 20 : intval($size);
		return;
	}

	/**
	 * 设置当前页码左右侧页码的数量
	 *
	 * @param	数字
	 * @return
	 */
	public function setCounter($counter) {
		$this->num_show = $counter;
		return;
	}

	public function setQuery($listStatuement, $conditionSql) {
		$this->listStatuement = $listStatuement;
		$this->conditionSql = $conditionSql;

		$this->setTotal();
		$this->getQueryString();
	}

	/**
	 * 设置“首页”“上一页”“下一页”“尾页”的显示文字
	 *
	 * @param	首页
	 * @param	上一页
	 * @param	下一页
	 * @param	尾页
	 * @return
	 */
	public function setWord($first = '', $prev = '', $next = '', $last = '') {
		if (!empty($first)) {
			$this->first_word = $first;
		}
		if (!empty($prev)) {
			$this->prev_word = $prev;
		}
		if (!empty($next)) {
			$this->next_word = $next;
		}
		if (!empty($last)) {
			$this->last_word = $last;
		}
		return;
	}

	/**
	 * 设置显示总页数的显示文字
	 *
	 * @param	页码前的文字
	 * @param	页码后的文字
	 * @return
	 */
	public function setTotalWord($pre = '', $end = '') {
		if (!empty($pre)) {
			$this->total_pre_word = $pre;
		}
		if (!empty($end)) {
			$this->total_end_word = $end;
		}
		return;
	}

	/**
	 * 设置显示当前页码的显示文字
	 *
	 * @param	当前页码前的文字
	 * @param	当前页码后的文字
	 * @return
	 */
	public function setCurrentWord($pre = '', $end = '') {
		if (!empty($pre)) {
			$this->current_pre_word = $pre;
		}
		if (!empty($end)) {
			$this->current_end_word = $end;
		}
		return;
	}

	/**
	 * 设置没有分页时显示的文字
	 *
	 * @param	文字内容
	 * @return
	 */
	public function setNoPageWord($word = '') {
		if (!empty($word)) {
			$this->no_page_word = $word;
		}
		return;
	}

	/**
	 * 获取内容列表
	 *
	 * @param
	 * @return	结果集，二维数组
	 */
	public function getPageList() {
		$this->setLimit();
		$listSql = 'select ' . $this->listStatuement . ' ' . $this->conditionSql . ' ' . $this->conditionLimit;
		$list = $this->db->exec($listSql);
		$this->page_size = count($list);
		return $list;
	}

	/**
	 * 获取分页显示字符串
	 *
	 * @param
	 * @return	显示的文字内容
	 */
	public function getPageRender() {
		$param = func_get_args();

		$render = '';
		if ($this->total_page > 1) {
			if (in_array('TOTAL', $param)) {
				$render .= "<span>{$this->total_pre_word}{$this->total_page}{$this->total_end_word}</span>&nbsp; ";
			}
			if (in_array('CURRENT', $param)) {
				$render .= "<span>{$this->current_pre_word}{$this->current_page}{$this->current_end_word}</span>&nbsp; ";
			}
			if ($this->current_page != 1) {
				$render .= "<span><a href='" . $this->switchUriType(1) . "' rel='1' target='_self'>{$this->first_word}</a></span>&nbsp; ";
				$render .= "<span><a href='" . $this->switchUriType($this->prev_page) . "' rel='" . $this->prev_page . "' target='_self'>{$this->prev_word}</a></span>&nbsp; ";
			}
			$render .= $this->showPageNumbers();
			if ($this->current_page != $this->total_page) {
				$render .= "<span><a href='" . $this->switchUriType($this->next_page) . "' rel='" . $this->next_page . "' target='_self'>{$this->next_word}</a></span>&nbsp; ";
				$render .= "<span><a href='" . $this->switchUriType($this->total_page) . "' rel='" . $this->total_page . "' target='_self'>{$this->last_word}</a></span>&nbsp; ";
			}
		} else {
			$render .= "<span class='none'>{$this->no_page_word}</span>&nbsp; ";
		}

		return $render;
	}

	/**
	 * 计算重组后的query string
	 *
	 * @param
	 * @return
	 */
	public function  getQueryString() {
		$this->prev_page = ((int)$this->current_page - 1) <= 0 ? 1 : ((int)$this->current_page - 1);
		$this->next_page = ((int)$this->current_page + 1) > $this->total_page ? $this->total_page : ((int)$this->current_page + 1);
		$this->new_query_string = $_SERVER['PHP_SELF'] . '?' . $this->query_string . $this->param;
		return;
	}

	/**
	 * 计算每页取值的范围
	 *
	 * @param
	 * @return
	 */
	private function setLimit() {
		$this->record_start = ($this->current_page - 1) * $this->page_size;
		$this->conditionLimit = ' limit ' . $this->record_start . ', ' . $this->page_size;
		return;
	}

	/**
	 * 计算总记录数量
	 *
	 * @param
	 * @return
	 */
	public function setTotal($total = 1) {
		if ($this->db !== null) {
			$count_total_sql = 'select count(1) as total ' . $this->conditionSql;
			$result = $this->db->exec($count_total_sql);
			$this->total_record = $result[0]['total'];
			$this->total_page = ceil($this->total_record / $this->page_size);
			return;
		} else {
			$this->total_page = ceil($total / $this->page_size);
			$this->current_page = $this->current_page;
			$this->prev_page = abs(intval($this->current_page - 1));
			$this->next_page = abs(intval($this->current_page + 1));
			return;
		}
	}

	/**
	 * 计算页数列表，显示 ... 1 2 3 ...
	 *
	 * @param      none
	 * @return     string
	 */
	private function showPageNumbers() {
		# 向左计算显示页码
		if ($this->current_page > 1) {
			$left_start = $this->current_page - $this->num_show;
			if ($left_start < 1) {
				$left_start = 1;
			}
			if ($left_start > 1) {
				$this->output_str .= '<span>...<span>&nbsp; ';
			}
			for ($i = $left_start; $i < $this->current_page; $i ++) {
				$this->output_str .= "<span><a href='" . $this->switchUriType($i) . "' rel='" . $i . "' target='_self'>{$i}</a></span>&nbsp; ";
			}
		}
		# 当前页码
		$this->output_str .= "<span class='current'><a class='current_page' href='" . $this->switchUriType($this->current_page) . "' rel='" . $this->current_page . "' target='_self'>{$this->current_page}</a></span>&nbsp; ";
		# 向右计算显示页码
		if ($this->current_page < $this->total_page) {
			$right_end = $this->current_page + $this->num_show;
			if ($right_end > $this->total_page) {
				$right_end = $this->total_page;
			}
			for ($i = $this->current_page; $i < $right_end; $i ++) {
				$mark = $i + 1;
				$this->output_str .= "<span><a href='" . $this->switchUriType($mark) . "' rel='" . $mark . "' target='_self'>{$mark}</a></span>&nbsp; ";
			}
			if ($right_end < $this->total_page) {
				$this->output_str .= '<span>...</span>&nbsp; ';
			}
		}
		return $this->output_str;
	}

	public function setUrlTemplate($url_template) {
		$this->url_template = $url_template;
	}

	/**
	 * 改变URL显示类型
	 *
	 * @param
	 * @return
	 */
	private function switchUriType($pagenum) {
		if (isset($this->url_template)) {
			$url = str_replace('{{PAGE}}', $pagenum, $this->url_template);
			return $url;
		} else {
			return 'javascript:void(0);';
		}
		/*$query = LeapURI::getParams($_SERVER['REQUEST_URI']);
		parse_str($query, $query_array);
		$query_array[$this->param] = $pagenum;

		$uri = $this->new_query_string . '=' . $pagenum;
		$url = parse_url($uri);
		$action = @explode('.php', $url['path']);
		$action = @explode('/' . App::$pathinfo_separator . '/', $action[1]);
		$action = str_replace('/', '.', trim($action[0], '/'));

		$query = http_build_query($query_array);

		$return_val = LeapURI::make($action, $query);
		return $return_val;*/
	}
}
