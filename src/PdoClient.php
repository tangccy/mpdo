<?php

namespace tjn\pdo;

use PDO;
use PDOStatement;

/**
* pdo封装
* Class Mpdo
 */
class PdoClient
{
	/**
	 * pdo示例
	 * @var \PDO
	 */
	private $pdo;

	/**
	 * 表名
	 * @var string
	 */
	private $table = '';

	/**
	 * 字段名
	 * @var string
	 */
	private $field = '*';

	/**
	 * and条件存储数组
	 */
	private $where;

	/**
	 * or条件存储数组
	 */
	private $whereOr;

	/**
	 * 直接语句条件存储数组
	 */
	private $whereRaw;


	/**
	 * in and
	 */
	private $whereIn;

	/**
	 * not in and
	 */
	private $whereNotIn;

	/**
	 * in or
	 * @var
	 */
	private $whereInOr;

	/**
	 *  not in or
	 * @var
	 */
	private $whereNotInOr;

	/**
	 * group by
	 * @var string
	 */
	private $groupBy = '';

	/**
	 * order by
	 * @var string
	 */
	private $orderBy = '';

	/**
	 * limit
	 * @var string
	 */
	private $limit = '';

	/**
	 * 别名
	 * @var string
	 */
	private $alias = '';

	/**
	 * 预处理的位置对应值
	 * @var array[]
	 */
	private $prepareValue = [
		'where' => [],
		'whereOr' => [],
		'whereRaw' => [],
		'whereIn' => [],
		'whereInOr' => [],
		'whereNotIn' => [],
		'whereNotInOr' => [],
	];

	/**
	 * @var array
	 */
	private $join = [];

	private $sql;
	private $errMsg;
	private $prepares;

	/**
	 * 数据库连接池子
	 * @var array
	 */
	private static $dbPond = [];

	//禁止实例化
	private function __construct(string $user, string $pass, string $dsn)
	{
		//初始化一个PDO对象
		$this->pdo = new PDO($dsn, $user, $pass, [
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
		]);
		$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	}

	/**
	 * 连接数据库
	 * @param array $config
	 * @return PdoClient
	 */
	public static function connect(array $config): PdoClient
	{
		$dbms = $config['dbms'] ?? 'mysql';
		$host = $config['host'];
		$port = $config['port'];
		$dbName = $config['dbname'];
		$user = $config['user'];
		$pass = $config['password'];
		$dsn = "$dbms:host=$host;port=$port;dbname=$dbName";
		return new PdoClient($user, $pass, $dsn);
	}

	/**
	 * 表名
	 * @param $table
	 * @param string $alias
	 * @return PdoClient
	 */
	public function table($table, $alias = '')
	{
		$this->table = $table;
		if ($alias) {
			$this->alias = $alias;
		}
		return $this;
	}


	/**
	 * 字段
	 * @param string $field
	 * @return PdoClient
	 */
	public function field($field = "*")
	{
		$this->field = $field;
		return $this;
	}

	/**
	 * 条数
	 * @param $start
	 * @param $offset
	 * @return PdoClient
	 */
	public function limit($start, $offset)
	{
		$this->limit = " LIMIT {$start}, {$offset}";
		return $this;
	}

	/**
	 * AND查询
	 *  ============
	 * +
	 * + where([['status', '=', 1], ['type', '>', 1]])
	 * + 相当于 (status=1 and type>1)
	 * +
	 * ============
	 * @param array|string $where
	 * @param string $opt
	 * @param string $value
	 * @return $this
	 */
	public function where($where, $opt = '', $value = '')
	{
		if (!empty($where)) {
			if (is_array($where) && $opt == '' && $value == '') {
				$first = reset($where);
				if (is_array($first)) {
					$sqlTemp = '';
					$valueArr = [];
					foreach ($where as $value) {
						$fieldTemp = explode('.', $value[0]);
						$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$value[0]`";
						if ('between' == strtolower($value[1])) {
							$sqlTemp .= "$field  BETWEEN ? AND ? AND ";
							$valueArr[] = $value[2];
							$valueArr[] = $value[3];
						} elseif ('in' == strtolower($value[1])) {
							$inStr = '';
							foreach ($value[2] as $v) {
								$valueArr[] = $v;
								$inStr .= '?,';
							}
							$inStr = rtrim($inStr, ',');
							$sqlTemp .= "$field IN ($inStr)  AND ";
						} else {
							$sqlTemp .= $field . ' ' . $value[1] . ' ?' . ' AND ';
							$valueArr[] = $value[2];
						}
					}
					$sqlTemp = rtrim($sqlTemp, ' AND ');  // 去除最后一个'AND'
					$this->where = ' (' . $sqlTemp . ') ';
					$this->prepareValue['where'] = $valueArr;
				} else {
					$fieldTemp = explode('.', $where[0]);
					$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$where[0]`";
					if ($where[1] == strtolower('between')) {
						$this->where = "$field BETWEEN ? AND ?";
						$this->prepareValue['where'] = [$where[2], $where[3]];
					} elseif ('in' == strtolower($where[1])) {
						$inStr = '';
						foreach ($where[2] as $v) {
							$this->prepareValue['where'][] = $v;
							$inStr .= '?,';
						}
						$inStr = rtrim($inStr, ',');
						$this->where .= "$field IN ($inStr)";
					} else {
						$this->where = $field . ' ' . $where[1] . ' ?';
						$this->prepareValue['where'] = [$where[2]];
					}
				}
			} else {
				$fieldTemp = explode('.', $where);
				$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$where`";
				$this->where = $field . " " . $opt . ' ?';
				$this->prepareValue['where'] = [$value];
			}
		}

		return $this;
	}

	/**
	 * OR查询
	 * ============
	 * +
	 * + whereOr([['status', '=', 1], ['type', '>', 1]])
	 * + 相当于 (status=1 or type>1)
	 * +
	 * ============
	 * @param $where
	 * @return PdoClient
	 * @throws PdoClientException
	 */
	public function whereOr($where)
	{
		if (!empty($where)) {
			if (!is_array($where)) {
				throw new PdoClientException("参数类型是array", ErrorCode::PARAM_ERR);
			}
			$first = reset($where);
			if (is_array($first)) {
				$sqlTemp = '';
				$valueArr = [];
				foreach ($where as $value) {
					$fieldTemp = explode('.', $value[0]);
					$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$value[0]`";
					if ('between' == strtolower($value[1])) {
						$sqlTemp .= "$field  BETWEEN ? AND ? OR ";
						$valueArr[] = $value[2];
						$valueArr[] = $value[3];
					} elseif ('in' == strtolower($value[1])) {
						$inStr = '';
						foreach ($value[2] as $v) {
							$valueArr[] = $v;
							$inStr .= '?,';
						}
						$inStr = rtrim($inStr, ',');
						$sqlTemp .= "$field in ($inStr) OR ";
					} else {
						$sqlTemp .= $field . ' ' . $value[1] . ' ' . '?' . ' OR ';
						$valueArr[] = $value[2];
					}
				}
				$sqlTemp = rtrim($sqlTemp, ' OR ');  // 去除最后一个'OR'
				$this->whereOr[] = ' (' . $sqlTemp . ') ';
				$this->prepareValue['whereOr'] = array_merge($this->prepareValue['whereOr'], $valueArr);
			} else {
				$fieldTemp = explode('.', $where[0]);
				$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$where[0]`";
				if ($where[1] == strtolower('between')) {
					$this->whereOr = "$field BETWEEN ? AND ?";
					$this->prepareValue['whereOr'][] = [$where[2], $where[3]];
				} elseif ('in' == strtolower($where[1])) {
					$inStr = '';
					foreach ($where[2] as $v) {
						$this->prepareValue['whereOr'][] = $v;
						$inStr .= '?,';
					}
					$inStr = rtrim($inStr, ',');
					$this->whereOr[] = "$field in ($inStr)";
				} else {
					$this->whereOr[] = $field . " " . $where[1] . ' ?';
					$this->prepareValue['whereOr'][] = $where[2];
				}

			}
		}
		return $this;
	}

	/**
	 * In and 查询
	 * ============
	 * +
	 * + whereIn([['status', [1,2,3,4]], ['type', [1,2,3,4]]])
	 * + 相当于 (status in (1,2,3,4) and  type in (1,2,3,4))
	 * + whereIn(['status', [1,2,3,4]])
	 * + 相当于 (status in (1,2,3,4))
	 * + whereIn('status', [1,2,3,4])
	 * + 相当于 (status in (1,2,3,4))
	 * +
	 * ============
	 * @param $where
	 * @param array $value
	 * @return PdoClient
	 */
	public function whereIn($where, $value = [])
	{
		if (!empty($where)) {
			if (is_array($where) && empty($value)) {
				$first = reset($where);
				if (is_array($first)) {
					$sqlTemp = '';
					$valueArr = [];
					foreach ($where as $value) {
						$fieldTemp = explode('.', $value[0]);
						$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$value[0]`";
						$str = '';
						foreach ($value[1] as $v) {
							$str .= '?,';
							$valueArr[] = $v;
						}
						$str = rtrim($str, ',');
						$sqlTemp .= $field . ' IN (' . $str . ') ' . ' AND ';
					}
					$sqlTemp = rtrim($sqlTemp, ' AND ');  // 去除最后一个'AND'
					$this->whereIn = ' (' . $sqlTemp . ') ';
				} else {
					$fieldTemp = explode('.', $where[0]);
					$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$where[0]`";
					$str = '';
					$valueArr = [];
					foreach ($where[1] as $v) {
						$str .= '?,';
						$valueArr[] = $v;
					}
					$str = rtrim($str, ',');
					$this->whereIn = ' (' . $field . ' IN (' . $str . ') ' . ') ';
				}
				$this->prepareValue['whereIn'] = $valueArr;
			} else {
				$fieldTemp = explode('.', $where);
				$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$where`";
				$str = '';
				foreach ($value as $v) {
					$str .= '?,';
				}
				$str = rtrim($str, ',');
				$this->whereIn = ' (' . $field . ' IN (' . $str . ') ' . ') ';
				$this->prepareValue['whereIn'] = $value;
			}
		}
		return $this;
	}

	/**
	 * In Or 查询
	 * ============
	 * +
	 * + whereInOr([['status', [1,2,3,4]], ['type', [1,2,3,4]]])
	 * + 相当于 (status in (1,2,3,4) OR  type in (1,2,3,4))
	 * +
	 * ============
	 * @param $where
	 * @return PdoClient
	 * @throws PdoClientException
	 */
	public function whereInOr($where)
	{
		if (!empty($where)) {
			if (!is_array($where)) {
				throw new PdoClientException("参数非array", ErrorCode::PARAM_ERR);
			}
			$sqlTemp = '';
			$valueArr = [];
			foreach ($where as $value) {
				$fieldTemp = explode('.', $value[0]);
				$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$value[0]`";
				$str = '';
				foreach ($value[1] as $v) {
					$str .= '?,';
					$valueArr[] = $v;
				}
				$str = rtrim($str, ',');
				$sqlTemp .= $field . ' IN (' . $str . ') ' . ' OR ';
			}
			$sqlTemp = rtrim($sqlTemp, ' OR ');  // 去除最后一个'OR'
			$this->whereInOr = ' (' . $sqlTemp . ') ';
			$this->prepareValue['whereInOr'] = $valueArr;
		}
		return $this;
	}


	/**
	 * NOT IN AND 查询
	 * ============
	 * +
	 * + whereNotIn([['status', [1,2,3,4]], ['type', [1,2,3,4]]])
	 * + 相当于 (status not in (1,2,3,4) and  type not in (1,2,3,4))
	 * + whereNotIn('status', [1,2,3,4])
	 * + 相当于 (status not in (1,2,3,4))
	 * + whereNotIn(['status', [1,2,3,4]])
	 * + 相当于 (status not in (1,2,3,4))
	 * +
	 * ============
	 * @param $where
	 * @param array $value
	 * @return PdoClient
	 */
	public function whereNotIn($where, $value = [])
	{
		if (!empty($where)) {
			if (is_array($where) && empty($value)) {
				$first = reset($where);
				if (is_array($first)) {
					$sqlTemp = '';
					$valueArr = [];
					foreach ($where as $value) {
						$fieldTemp = explode('.', $value[0]);
						$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$value[0]`";
						$str = '';
						foreach ($value[1] as $v) {
							$str .= '?,';
							$valueArr[] = $v;
						}
						$str = rtrim($str, ',');
						$sqlTemp .= $field . ' NOT IN (' . $str . ') ' . ' AND ';
					}
					$sqlTemp = rtrim($sqlTemp, ' AND ');  // 去除最后一个'AND'
					$this->whereNotIn = ' (' . $sqlTemp . ') ';
				} else {
					$fieldTemp = explode('.', $where[0]);
					$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$where[0]`";
					$str = '';
					$valueArr = [];
					foreach ($where[1] as $v) {
						$str .= '?,';
						$valueArr[] = $v;
					}
					$str = rtrim($str, ',');
					$this->whereNotIn = ' (' . $field . ' NOT IN (' . $str . ') ' . ') ';
				}
				$this->prepareValue['whereNotIn'] = $valueArr;
			} else {
				$fieldTemp = explode('.', $where);
				$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$where`";
				$str = '';
				foreach ($value as $v) {
					$str .= '?,';
				}
				$str = rtrim($str, ',');
				$this->whereNotIn = ' (' . $field . ' NOT IN (' . $str . ') ' . ') ';
				$this->prepareValue['whereNotIn'] = $value;
			}
		}
		return $this;
	}

	/**
	 * NOT IN OR 查询
	 * ============
	 * +
	 * + whereNotInOr([['status', [1,2,3,4]], ['type', [1,2,3,4]]])
	 * + 相当于 (status not in (1,2,3,4) or type not in (1,2,3,4))
	 * +
	 * ============
	 * @param $where
	 * @return PdoClient
	 * @throws PdoClientException
	 */
	public function whereNotInOr($where)
	{
		if (!empty($where)) {
			if (!is_array($where)) {
				throw new PdoClientException("参数非array", ErrorCode::PARAM_ERR);
			}
			$sqlTemp = '';
			$valueArr = [];
			foreach ($where as $value) {
				$fieldTemp = explode('.', $value[0]);
				$field = count($fieldTemp) > 1 ? "`$fieldTemp[0]`.`$fieldTemp[1]`" : "`$value[0]`";
				$str = '';
				foreach ($value[1] as $v) {
					$str .= '?,';
					$valueArr[] = $v;
				}
				$str = rtrim($str, ',');
				$sqlTemp .= $field . ' IN (' . $str . ') ' . ' OR ';
			}
			$sqlTemp = rtrim($sqlTemp, ' OR ');  // 去除最后一个'OR'
			$this->whereNotInOr = ' (' . $sqlTemp . ') ';
			$this->prepareValue['whereNotInOr'] = $valueArr;
		}
		return $this;
	}


	/**
	 * 直接写语法
	 * ============
	 * +
	 * + whereRaw('status=? and type>?', 1, 2)
	 * + 相当于 (status=1 and type>2)
	 * +
	 * ============
	 * @param string $where
	 * @param array $value
	 * @return PdoClient
	 * @throws PdoClientException
	 */
	public function whereRaw($where, $value)
	{
		$count = substr_count($where, '?');
		if ($count != count($value)) {
			throw new PdoClientException('where预处理占位符与传参值不一致', ErrorCode::PARAM_ERR);
		}
		$this->whereRaw = $where;
		$this->prepareValue['whereRaw'] = $value;
		return $this;
	}

	/**
	 * 获取数据
	 * @return false|\PDOStatement
	 * @throws PdoClientException
	 */
	public function get()
	{
		if (!$this->table) {
			throw new PdoClientException("table name not exist", ErrorCode::TABLE_NOT_EXIST);
		}
		$where = $this->makeWhere();
		$whereVal = $this->makeWhereVal();
		$join = $this->makeJoin();
		$sql = <<<SQL
SELECT %s FROM `%s` %s %s %s %s %s %s
SQL;
		$this->sql = sprintf($sql, $this->field, $this->table, $this->alias, $join, $where, $this->groupBy, $this->orderBy, $this->limit);
		$this->prepares = $whereVal;
		return $this->execute($this->sql, $whereVal);
	}

	/**
	 * 查询一条
	 * @return false|array
	 * @throws PdoClientException
	 */
	public function first()
	{
		$this->limit(0, 1);
		$result = $this->get();
		return $result ? $result->fetch(\PDO::FETCH_ASSOC) : [];
	}

	/**
	 * @return array
	 * @throws PdoClientException
	 */
	public function all()
	{
		$result = $this->get();
		return $result ? $result->fetchAll(\PDO::FETCH_ASSOC) : [];
	}

	/**
	 * count统计
	 * @return false|int
	 * @throws PdoClientException
	 */
	public function count()
	{
		if (!$this->table) {
			throw new PdoClientException("table name not exist", ErrorCode::TABLE_NOT_EXIST);
		}
		$where = $this->makeWhere();
		$whereVal = $this->makeWhereVal();
		$sql = 'SELECT COUNT(`id`) as total FROM `%s` %s';
		$this->sql = sprintf($sql, $this->table, $where);
		$this->prepares = $whereVal;
		$result = $this->execute($this->sql, $this->prepares);
		if (!$result) {
			return false;
		}
		$data = $result->fetch();
		return $data['total'];
	}

	/**
	 * 分组查询
	 * @param $groupBy
	 * @return PdoClient
	 */
	public function groupBy($groupBy)
	{
		if ($groupBy) {
			$this->groupBy = ' GROUP BY ' . $groupBy;
		}
		return $this;
	}

	/**
	 * 排序
	 * @param $orderBy
	 * @return PdoClient
	 */
	public function orderBy($orderBy)
	{
		if ($orderBy) {
			$this->orderBy = ' ORDER BY ' . $orderBy;
		}
		return $this;
	}

	public function join($table, $on, $type = "INNER")
	{
		$this->join[] = $type . ' JOIN ' . $table . ' ON ' . $on;
		return $this;
	}

	public function makeJoin()
	{
		$sql = '';
		foreach ($this->join as $join) {
			$sql .= ' ' . $join;
		}
		return $sql;
	}


	/**
	 * 拼接条件
	 * @return string
	 */
	private function makeWhere()
	{
		/**
		 *  'where'        => [],
		 * 'whereOr'      => [],
		 * 'whereRaw'     => [],
		 * 'whereIn'      => [],
		 * 'whereInOr'    => [],
		 * 'whereNotIn'   => [],
		 * 'whereNotInOr' => [],
		 */
		$where = '';
		if ($this->where) {
			$where .= $this->where . ' AND ';
		}
		if ($this->whereOr) {
			foreach ($this->whereOr as $item) {
				$where .= $item . ' AND ';
			}
		}
		if ($this->whereRaw) {
			$where .= $this->whereRaw . ' AND ';
		}
		if ($this->whereIn) {
			$where .= $this->whereIn . ' AND ';
		}
		if ($this->whereInOr) {
			$where .= $this->whereInOr . ' AND ';
		}

		if ($this->whereNotIn) {
			$where .= $this->whereNotIn . ' AND ';
		}
		if ($this->whereNotInOr) {
			$where .= $this->whereNotInOr . ' AND ';
		}
		if ($where) {
			$where = ' WHERE ' . rtrim($where, ' AND ');
		}
		return $where;
	}

	/**
	 * 条件值
	 * @return array
	 */
	private function makeWhereVal()
	{
		$whereVal = [];
		foreach ($this->prepareValue as $values) {
			$whereVal = array_merge($whereVal, $values);
		}
		return $whereVal;
	}

	/**
	 * 插入数据
	 * @param array $data
	 * @return int
	 * @throws PdoClientException
	 */
	public function insert($data)
	{
		if (!is_array($data)) {
			throw new PdoClientException("参数非数组", ErrorCode::PARAM_ERR);
		}
		if (!$this->table) {
			throw new PdoClientException("table name not exist", ErrorCode::TABLE_NOT_EXIST);
		}
		$fieldStr = '';
		$valueStr = '';
		$values = [];
		foreach ($data as $field => $datum) {
			$fieldStr .= "`{$field}`,";
			$valueStr .= "?,";
			$values[] = $datum;
		}
		$fieldStr = rtrim($fieldStr, ',');
		$valueStr = rtrim($valueStr, ',');
		$sql = <<<SQL
INSERT INTO %s (%s) VALUES (%s)
SQL;
		$this->sql = sprintf($sql, $this->table, $fieldStr, $valueStr);
		$this->prepares = $values;
		$this->execute($this->sql, $this->prepares);
		return $this->pdo->lastInsertId();
	}

	/**
	 * 批量插入/替换
	 * @param array $data 插入数据
	 * @param boolean $replace 是否使用替换
	 * @return bool|int
	 * @throws PdoClientException
	 */
	public function insertAll($data, $replace = false)
	{
		if (!$this->table) {
			throw new PdoClientException("table name not exist", ErrorCode::TABLE_NOT_EXIST);
		}
		$sql = ($replace ? "REPLACE" : "INSERT") . " INTO `%s` %s VALUES %s";
		$fieldArr = array_keys(reset($data));
		$fields = '(' . join(',', array_map(function ($item) {
				return "`{$item}`";
			}, array_keys(reset($data)))) . ')';
		$valueStr = [];
		$valueArr = [];
		foreach ($data as $key => $row) {
			$str = '';
			foreach ($fieldArr as $field) {
				$str .= "?,";
				$valueArr[] = $row[$field];
			}
			$valueStr[] = '(' . rtrim($str, ',') . ')';
		}
		$value = join(',', $valueStr);
		$this->sql = sprintf($sql, $this->table, $fields, $value);
		$this->prepares = $valueArr;
		$res = $this->execute($this->sql, $this->prepares);
		return $res ? $res->rowCount() : false;
	}

	/**
	 * @param $data
	 * @param array $unique 唯一索引值
	 * @return false|int
	 * @throws \tjn\pdo\PdoClientException
	 */
	public function save($data, $unique = [])
	{
		if (!$this->table) {
			throw new PdoClientException("table name not exist", ErrorCode::TABLE_NOT_EXIST);
		}
		$fieldStr = '';
		$valueStr = '';
		$upStr = '';
		$values = [];
		$uniqueValues = [];
		foreach ($data as $field => $datum) {
			$fieldStr .= "`{$field}`,";
			if ($datum instanceof Query) {
				$valueStr .= $datum->raw . ",";
			} else {
				$valueStr .= "?,";
				$values[] = $datum;
			}
			if (empty($unique)) {
				if ($datum instanceof Query) {
					$upStr .= "`{$field}`={$datum->raw},";
				} else {
					$upStr .= "`{$field}`=?,";
					$uniqueValues[] = $datum;
				}

			}
		}
		if (!empty($unique)) {
			foreach ($unique as $field => $datum) {
				if ($datum instanceof Query) {
					$upStr .= "`{$field}`={$datum->raw},";
				} else {
					$upStr .= "`{$field}`=?,";
					$uniqueValues[] = $datum;
				}
			}
		}
		$fieldStr = rtrim($fieldStr, ',');
		$valueStr = rtrim($valueStr, ',');
		$upStr = rtrim($upStr, ',');
		$this->sql = "INSERT INTO `$this->table` ($fieldStr) VALUES ($valueStr) ON DUPLICATE KEY UPDATE $upStr";
		$this->prepares = array_merge($values, $uniqueValues);
		$res = $this->execute($this->sql, $this->prepares);
		return $res ? $res->rowCount() : false;
	}

	/**
	 * 更新
	 * @param $data
	 * @return bool
	 * @throws PdoClientException
	 */
	public function update($data)
	{
		if (!is_array($data)) {
			throw new PdoClientException("参数非数组", ErrorCode::PARAM_ERR);
		}
		$updStr = '';
		$updVal = [];
		foreach ($data as $key => $datum) {
			if ($datum instanceof Query) {
				$updStr .= "`{$key}`={$datum->raw}";
			} else {
				$updStr .= "`{$key}`=?,";
				$updVal[] = $datum;
			}
		}
		$updStr = rtrim($updStr, ',');
		$whereVal = [];
		foreach ($this->prepareValue as $values) {
			$whereVal = array_merge($whereVal, $values);
		}
		$where = $this->makeWhere();
		$prepare = array_merge($updVal, $whereVal);
		if (!$where || !$prepare) {
			throw new PdoClientException("参数非数组", ErrorCode::DANGER_ERR);
		}
		$sql = <<<SQL
UPDATE %s SET %s %s;
SQL;
		$this->sql = sprintf($sql, $this->table, $updStr, $where);
		$this->prepares = $prepare;
		$res = $this->execute($this->sql, $this->prepares);
		return !($res === false);
	}

	/**
	 * 删除
	 * @throws PdoClientException
	 */
	public function delete()
	{
		$sql = <<<SQL
DELETE FROM %s %s
SQL;
		$whereVal = $this->makeWhereVal();

		$where = $this->makeWhere();
		if (!$where || empty($whereVal)) {
			throw new PdoClientException('禁止删除全表', ErrorCode::DANGER_ERR);
		}
		$this->sql = sprintf($sql, $this->table, $where);
		$this->prepares = $whereVal;
		$res = $this->execute($this->sql, $this->prepares);
		return !($res === false);
	}

	/**
	 *  执行 SQL 语句，以 PDOStatement 对象形式返回结果集
	 * @param $sql
	 * @return false|\PDOStatement
	 */
	public function query($sql)
	{
		$result = $this->pdo->query($sql);
		if (!$result) {
			$this->errorLogs();
		}
		return $result;
	}

	/**
	 * 执行一条 SQL 语句，并返回受影响的行数
	 * @param $sql
	 * @return false|int
	 */
	public function exec($sql)
	{
		return $this->pdo->exec($sql);
	}

	/**
	 * @param $sql
	 * @param $values
	 * @return false|\PDOStatement
	 */
	public function execute($sql, $values)
	{
		$sth = $this->pdo->prepare($sql);
		if (!$sth) {
			$this->errorLogs($sth);
			return false;
		}
		$sth->execute($values);
		if ($sth->errorCode() != '00000') {
			$this->errorLogs($sth);
			return false;
		}
		return $sth;
	}

	/**
	 * 开启事务
	 */
	public function beginTransaction()
	{
		$this->pdo->beginTransaction();
	}

	/**
	 * 提交事务
	 */
	public function commit()
	{
		$this->pdo->commit();
	}

	/**
	 * 回滚事务
	 */
	public function rollBack()
	{
		$this->pdo->rollBack();
	}

	/**
	 * @param $querySql
	 * @return \tjn\pdo\Query
	 */
	public static function raw($querySql)
	{
		$query = new Query();
		$query->raw = $querySql;
		return $query;
	}

	/**
	 * 错误日志记录
	 * @param \PDOStatement $sth
	 */
	private function errorLogs($sth = '')
	{
		if (!$sth) {
			$code = $this->pdo->errorCode();
			$info = $this->pdo->errorInfo();
		} else {
			$code = $sth->errorCode();
			$info = $sth->errorInfo();
			$sql = $sth->queryString;
		}
		$err = "ERROR_CODE:{$code}\nMESSAGE:{$info[2]}\n";
		if (isset($sql)) {
			$err .= "SQL:".$this->getLastSql();
		}
		$err .= "===================================\n";
		$this->errMsg = $err;
		return $this->errMsg;
	}

	public function getError(){
		return $this->errMsg;
	}

	/**
	 * 获取最后执行的sql
	 * @return string
	 */
	public function getLastSql(): string
	{
		return sprintf(str_replace('?', "'%s'", $this->sql), ...$this->prepares);
	}

}
