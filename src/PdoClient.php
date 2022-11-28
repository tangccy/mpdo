<?php

namespace tjn\pdo;

use PDO;
use PDOStatement;

/**
 * pdo封装客户端
 */
class PdoClient
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * 表名
     * @var string
     */
    protected $table = '';

    /**
     * 查询字段
     * @var string
     */
    protected $field = "*";

    /**
     * 预处理条件
     * @var string
     */
    protected $where = '';

    /**
     * 预处理条件值
     * @var array
     */
    protected $wherePrepares = [];

    /**
     * 所有预处理值
     * @var array
     */
    protected $prepares = [];

    /**
     * 最终生成预处理sql
     * @var string
     */
    protected $sql = '';

    /**
     * limit查询
     * @var string
     */
    protected $limit = "";

    /**
     * 分组
     * @var string
     */
    protected $groupBy = "";

    /**
     * 排序
     * @var string
     */
    protected $orderBy = "";

    /**
     * join
     * @var array
     */
    protected $join = [];

    /**
     * 别名
     * @var string
     */
    private $alias = '';

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
     * 设置表名
     * @param string $table 表名
     * @param string $alias 别名
     * @return $this
     */
    public function table(string $table, string $alias = ""): PdoClient
    {
        $this->table = $table;
        $this->alias = $alias;
        return $this;
    }

    /**
     * 查询字段
     * @param string $field
     * @return $this
     */
    public function select(string $field = "*"): PdoClient
    {
        $this->field = $field;
        return $this;
    }

    /**
     * 查询条件
     * @param array $where
     * @param string $splicer
     * @return $this
     */
    public function where(array $where, string $splicer = "AND"): PdoClient
    {
        $whereStr = '';
        foreach ($where as $item) {
            list($filed, $operator, $value, $splicerTemp) = $item;
            $operator = strtoupper($operator);
            $splicerTemp = $splicerTemp ? strtoupper($splicerTemp) : 'AND';
            switch ($operator) {
                case 'IN':
                    $strV = '';
                    foreach ($value as $v) {
                        $strV .= '?,';
                        $this->wherePrepares[] = $v;
                    }
                    $strV = rtrim($strV, ',');
                    $str = " (`$filed` $operator ($strV)) ";
                    break;
                default:
                    $str = " (`$filed` $operator ?) ";
                    $this->wherePrepares[] = $value;
                    break;
            }
            $whereStr .= $whereStr ? " $splicerTemp $str" : $str;
        }
        $this->where .= $this->where ? " $splicer ($whereStr)" : $whereStr;
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
     * @return \tjn\pdo\PdoClient
     */
    public function whereRaw(string $where, array $value): PdoClient
    {
        $this->where = $this->where ? " AND $where " : $where;
        $this->wherePrepares[] = array_merge($this->wherePrepares, $value);
        return $this;
    }


    /**
     * limit
     * @param $start
     * @param $offset
     * @return PdoClient
     */
    public function limit($start, $offset): PdoClient
    {
        $this->limit = "LIMIT $start, $offset";
        return $this;
    }

    /**
     * 分组查询
     * @param $groupBy
     * @return \tjn\pdo\PdoClient
     */
    public function groupBy($groupBy): PdoClient
    {
        if ($groupBy) {
            $this->groupBy = ' GROUP BY ' . $groupBy;
        }
        return $this;
    }

    /**
     * 排序
     * @param string $orderBy
     * @return \tjn\pdo\PdoClient
     */
    public function orderBy(string $orderBy): PdoClient
    {
        if ($orderBy) {
            $this->orderBy = ' ORDER BY ' . $orderBy;
        }
        return $this;
    }

    public function join($table, $on, $type = "INNER"): PdoClient
    {
        $this->join[] = $type . ' JOIN ' . $table . ' ON ' . $on;
        return $this;
    }

    /**
     * 执行查询
     * @throws PdoClientException
     */
    protected function getStatement(): PDOStatement
    {
        $where = $this->where ? " WHERE {$this->where}" : '';
        $joinStr = '';
        foreach ($this->join as $join) {
            $joinStr .= ' ' . $join;
        }
        var_dump($join);
        $this->sql = "SELECT $this->field FROM `$this->table` $this->alias $joinStr $where $this->groupBy $this->orderBy $this->limit";
        $this->prepares = array_merge($this->prepares, $this->wherePrepares);
        return $this->query($this->sql, $this->prepares);
    }

    /**
     * count统计
     * @throws \tjn\pdo\PdoClientException
     */
    public function count(): int
    {
        $where = $this->where ? "WHERE $this->where" : $this->where;
        $this->sql = "SELECT COUNT(`id`) as total FROM $this->table $where";
        $this->prepares = $this->wherePrepares;
        $result = $this->query($this->sql, $this->prepares);
        $data = $result->fetch();
        return (int)$data['total'];
    }

    /**
     * 获取单条
     * @throws PdoClientException
     */
    public function first(): array
    {
        $this->limit(0, 1);
        $result = $this->getStatement();
        return $result ? $result->fetch(PDO::FETCH_ASSOC) : [];
    }

    /**
     * 获取多条
     * @throws PdoClientException
     */
    public function all(): array
    {
        $result = $this->getStatement();
        return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * 插入数据
     * @param array $data
     * @return int
     * @throws \tjn\pdo\PdoClientException
     */
    public function insert(array $data): int
    {
        $fieldStr = '';
        $valueStr = '';
        $values = [];
        foreach ($data as $field => $datum) {
            $fieldStr .= "`$field`,";
            $valueStr .= "?,";
            $values[] = $datum;
        }
        $this->prepares = $values;
        $fieldStr = rtrim($fieldStr, ',');
        $valueStr = rtrim($valueStr, ',');
        $this->sql = "INSERT INTO `$this->table` ($fieldStr) VALUES ($valueStr)";
        $this->query($this->sql, $this->prepares);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * 批量插入/替换
     * @param array $data 插入数据
     * @param bool $replace 是否使用替换
     * @return int
     * @throws \tjn\pdo\PdoClientException
     */
    public function insertAll(array $data, bool $replace): int
    {
        $sql = ($replace ? "REPLACE" : "INSERT") . " INTO `%s` %s VALUES %s";
        $fieldArr = array_keys(reset($data));
        $fields = '(' . join(',', array_map(function ($item) {
                return "`$item`";
            }, array_keys(reset($data)))) . ')';
        $valueStr = [];
        $valueArr = [];
        foreach ($data as $row) {
            $str = '';
            foreach ($fieldArr as $field) {
                $str .= "?,";
                $valueArr[] = $row[$field];
            }
            $valueStr[] = '(' . rtrim($str, ',') . ')';
        }
        $this->prepares = $valueArr;
        $value = join(',', $valueStr);
        $this->sql = sprintf($sql, $this->table, $fields, $value);
        $res = $this->query($this->sql, $this->prepares);
        return $res->rowCount();
    }

    /**
     * 新增或更新
     * @param $data
     * @param array $unique 唯一索引值
     * @return int
     * @throws \tjn\pdo\PdoClientException
     */
    public function save($data, array $unique = []): int
    {
        $fieldStr = '';
        $valueStr = '';
        $upStr = '';
        $values = [];
        $uniqueValues = [];
        foreach ($data as $field => $datum) {
            $fieldStr .= "`$field`,";
            if ($datum instanceof Query) {
                $valueStr .= $datum->raw . ",";
            } else {
                $valueStr .= "?,";
                $values[] = $datum;
            }
            if (empty($unique)) {
                if ($datum instanceof Query) {
                    $upStr .= "`$field`=$datum->raw,";
                } else {
                    $upStr .= "`$field`=?,";
                    $uniqueValues[] = $datum;
                }

            }
        }
        if (!empty($unique)) {
            foreach ($unique as $field => $datum) {
                if ($datum instanceof Query) {
                    $upStr .= "`$field`=$datum->raw,";
                } else {
                    $upStr .= "`$field`=?,";
                    $uniqueValues[] = $datum;
                }
            }
        }
        $fieldStr = rtrim($fieldStr, ',');
        $valueStr = rtrim($valueStr, ',');
        $upStr = rtrim($upStr, ',');
        $this->sql = "INSERT INTO `$this->table` ($fieldStr) VALUES ($valueStr) ON DUPLICATE KEY UPDATE $upStr";
        $this->prepares = array_merge($values, $uniqueValues);
        $res = $this->query($this->sql, $this->prepares);
        return $res->rowCount();
    }

    /**
     * 更新
     * @param $data
     * @return \PDOStatement
     * @throws \tjn\pdo\PdoClientException
     */
    public function update($data): PDOStatement
    {
        $updStr = '';
        $updVal = [];
        foreach ($data as $key => $datum) {
            if ($datum instanceof Query) {
                $updStr .= "`$key`={$datum->raw}";
            } else {
                $updStr .= "`$key`=?,";
                $updVal[] = $datum;
            }
        }
        $updStr = rtrim($updStr, ',');
        $this->prepares = array_merge($updVal, $this->wherePrepares);
        $this->sql = "UPDATE $this->table SET $updStr $this->where";
        return $this->query($this->sql, $this->prepares);
    }

    /**
     * 删除
     * @throws \tjn\pdo\PdoClientException
     */
    public function delete(): PDOStatement
    {
        $this->sql = "DELETE FROM $this->table $this->where";
        $this->prepares = $this->wherePrepares;
        return $this->query($this->sql, $this->prepares);
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
     * 原生语句存储
     * @param $querySql
     * @return \tjn\pdo\Query
     */
    public static function raw($querySql): Query
    {
        $query = new Query();
        $query->raw = $querySql;
        return $query;
    }

    /**
     * 执行
     * @param string $sql
     * @param array $prepares
     * @return PDOStatement
     * @throws \tjn\pdo\PdoClientException
     */
    public function query(string $sql, array $prepares): PDOStatement
    {
        $sth = $this->pdo->prepare($sql);
        if (!$sth) {
            $errorInfo = $this->pdo->errorInfo();
            throw new PdoClientException($errorInfo[2], $this->pdo->errorCode());
        }
        $sth->execute($prepares);
        return $sth;
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
     * 获取最后执行的sql
     * @return string
     */
    public function getLastSql(): string
    {
        return sprintf(str_replace('?', "'%s'", $this->sql), ...$this->prepares);
    }

    //禁止克隆
    private function __clone()
    {
    }
}
