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
     * @var \PDO
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
        $port = $config['post'];
        $dbName = $config['dbname'];
        $user = $config['user'];
        $pass = $config['password'];
        $dsn = "$dbms:host=$host;port=$port;dbname=$dbName";
        return new PdoClient($user, $pass, $dsn);
    }

    /**
     * 设置表名
     * @param string $table
     * @return $this
     */
    public function table(string $table): PdoClient
    {
        $this->table = $table;
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
     * @return $this
     */
    public function where(array $where): PdoClient
    {
        foreach ($where as $item) {
            list($filed, $operator, $value, $splicer) = $item;
            $operator = strtoupper($operator);
            $splicer = $splicer ? strtoupper($splicer) : 'AND';
            switch ($operator) {
                case 'IN':
                    $strV = '';
                    foreach ($value as $v) {
                        $strV .= '?,';
                        $this->wherePrepares[] = $v;
                    }
                    $strV = rtrim($strV, ',');
                    $str = " `$filed` $operator ($strV) ";
                    break;
                default:
                    $str = " `$filed` $operator ? ";
                    $this->wherePrepares[] = $value;
                    break;
            }
            $this->where .= $this->where ? " $splicer $str" : $str;
        }
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
     * 执行查询
     * @throws PdoClientException
     */
    protected function getStatement(): PDOStatement
    {
        $where = $this->where ? " WHERE {$this->where}" : '';
        $this->sql = "SELECT {$this->field} FROM `{$this->table}` {$where} {$this->limit}";
        $this->prepares = array_merge($this->prepares, $this->wherePrepares);
        return $this->query($this->sql, $this->prepares);
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