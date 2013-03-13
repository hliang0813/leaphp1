<?php
visit_limit();

class Model extends Base {
	protected $table;
	protected $pk = 'id';
	# array('字段名称', '表单tag类型')
	protected $statuements = array();
	protected $statuement_key = array();
	public $manage = array();

	# SQL 语句片断
	protected $where = '';
	protected $limit = '';
	protected $order = array();

	# 构造函数
	public function __construct($db_object = null) {
		$this->clear();
		if (is_object($db_object)) {
			$this->db = $db_object;
		}
		$this->statuements_key = $this->addTable2Statuement(array_keys($this->statuements), $this->table);
		if ($this->pk) {
			$this->pk = $this->addTable2Statuement($this->pk, $this->table);
		}
	}

	# 重置变量
	protected function clear() {
		$this->where = 'WHERE 1 = 1 ';
		$this->limit = '';
		$this->order = array();
	}

	# SQL WHERE 语句
	# 大于(>); 大于等于(>=); 小于(<); 小于等于(<=); 等于(=); 不等于(<>)；IN(@)；模糊匹配(&)
	public function filter($condition, $type = 'AND') {
		$pattern = '/^(\w+)(=|(<>)|(>=)|(<=)|>|<|@|&)(.+)?$/';
		preg_match($pattern, $condition, $matches);
		list($condition, $statuement, $sign, , , , $value) = $matches;
		$statuement = $this->addTable2Statuement($statuement, $this->table);
		if (!in_array($statuement, $this->statuements_key) && $statuement != $this->pk) {
			#trigger_error('条件字段错误：' . $statuement, E_USER_ERROR);
		}

		$where_condition = '%s %s %s %s';

		switch ($sign) {
			case '@':
				$sign = 'IN';
				$value = sprintf("('%s')", str_replace(',', "','", $value));
				break;
			case '&':
				$sign = 'LIKE';
				$value = '\'%' . $value . '%\'';
				break;
			default:
				$value = sprintf("'%s'", $value);
		}

		$this->where .= sprintf($where_condition, $type, $statuement, $sign, $value);

		return $this;
	}

	# SQL LIMIT 语句
	public function limit($limit, $start = 0) {
		$limit = intval($limit);
		$start = intval($start);
		$this->limit = sprintf('LIMIT %d, %d', $start, $limit);
		return $this;
	}

	# SQL ORDER 语句
	# 减号(-)开头，表示倒序排列
	public function order($condition) {
		$pattern = '/^(-?)(\w+)$/';
		preg_match($pattern, $condition, $matches);
		list($condition, $type, $statuement) = $matches;
		$statuement = $this->addTable2Statuement($statuement, $this->table);
		if (!in_array($statuement, $this->statuements_key) && $statuement != $this->pk) {
			trigger_error('排序字段错误：' . $statuement, E_USER_ERROR);
		}
		$type = $type == '-' ? 'DESC' : 'ASC';
		$this->order[] = "{$statuement} {$type}";
		return $this;
	}
	
	# 新建记录
	public function add($data = array()) {
		if (!$data) {
			trigger_error('新增字段不能为空', E_USER_ERROR);
		}
		$this->checkStatuement($data, '新增');
		foreach ( (array)$data as $key => $value) {
			$keys[] = $key;
			$datas[':' . $key . '_str'] = $value;
		}
		$add_sql = "INSERT INTO {$this->table} (" . implode(',', $keys) . ") VALUES (:" . implode(',:', $keys) . ")";
		$this->db->prepare($add_sql);
		$_result = $this->db->execBind($datas);
		$this->clear();
		return $_result;
	}

	# 修改记录
	public function edit($data = array()) {
		if (!$data) {
			trigger_error('修改字段不能为空', E_USER_ERROR);
		}
		$this->checkStatuement($data, '修改');
		foreach ( (array)$data as $key => $value) {
			$contents[] = $key . ' = :' . $key;
			$datas[':' . $key . '_str'] = $value;
		}
		$edit_sql = "UPDATE {$this->table} SET " . implode(',', $contents) . " {$this->where}";
		$this->db->prepare($edit_sql);
		$_result = $this->db->execBind($datas);
		$this->clear();
		return $_result;
	}

	# 删除记录
	public function delete() {
		$delete_sql = "DELETE FROM {$this->table} {$this->where}";
		$_result = $this->db->exec($delete_sql);
		$this->clear();
		return $_result;
	}

	# 查询记录（不分页）
	public function get($data = array(), $join_statuement = array()) {
		if (empty($data)) {
			if ($this->pk) {
				$datas = "{$this->pk}," . implode(',', $this->statuements_key);
			} else {
				$datas = implode(',', $this->statuements_key);
			}
		} else {
			$this->checkStatuement($data, '查询');
			$datas = implode(',', $this->addTable2Statuement($data, $this->table));
		}

		if (isset($this->join) || isset($this->leftJoin) || isset($this->rightJoin)) {
			# 联合查询
			if (isset($this->leftJoin)) {
				$join_type = 'LEFT';
				$this->join = $this->leftJoin;
			} else if (isset($this->rightJoin)) {
				$join_type = 'RIGHT';
				$this->join = $this->rightJoin;
			} else {
				$join_type = 'INNER';
			}
			#import::model($this->join);
			$join_model = new $this->join($this->db);
			if (empty($join_statuement)) {
				$join_datas = implode(',', $join_model->statuements_key);
			} else {
				$join_datas = implode(',', $this->addTable2Statuement($join_statuement, $join_model->table));
			}
			$datas = $datas . ',' . $join_datas;
			$on_part = array();
			foreach ((array)$this->join_keys as $left_key => $right_key) {
				$left_key = $this->addTable2Statuement($left_key, $this->table);
				$right_key = $this->addTable2Statuement($right_key, $join_model->table);
				$on_part[] = "{$left_key} = {$right_key}";
			}
			$on_part = implode(' and ', (array)$on_part);
			$table = "{$this->table} {$join_type} JOIN {$join_model->table} ON {$on_part}";
		} else {
			# 普通查询
			$table = $this->table;
		}
		if (count($this->order) > 0) {
			$order = 'ORDER BY ' . implode(',', $this->order);
		} else {
			$order = '';
		}
		$get_sql = "SELECT {$datas} FROM {$table} {$this->where} {$order} {$this->limit}";
		$_result = $this->db->exec($get_sql);
		$this->clear();
		return $_result;
	}

	# 查询结果数量
	public function count() {
		$count_sql = "SELECT COUNT(1) as counter FROM {$this->table} {$this->where}";
		$_result = $this->db->exec($count_sql);
		$this->clear();
		return $_result;
	}
	
	# 验证字段合法性
	protected function checkStatuement($data, $type) {
		foreach ( (array)$data as $key => $value) {
			$statuement = is_numeric($key) ? $value : $key;
			$statuement = $this->addTable2Statuement($statuement, $this->table);
			if (!in_array($statuement, $this->statuements_key) && $statuement != $this->pk) {
				trigger_error("{$type} 字段错误：{$statuement}", E_USER_ERROR);
				return false;
			}
		}
		return true;
	}

	# 给字段加上表名前缀
	protected function addTable2Statuement($statuement, $table) {
		if (is_array($statuement)) {
			foreach ((array)$statuement as $key => $value) {
				$statuement[$key] = $table . '.' . $value;
			}
		} else {
			$statuement = $table . '.' . $statuement;
		}
		return $statuement;
	}
}
