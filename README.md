# pdo简易封装


## 实例化
```php
$config = [
    'dbms' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => 'learn01',
    'user' => 'root',
    'password' => '123456',
];
$pdo = PdoClient::connect($config);
```

## 表

### table()

```php
//plat_user是表名
$pdo->table('plat_user');
```

## 条件

#### where()

参数会形成AND组合

```php
//1.二维数组 sql:select * from `plat_user` where (actor=1 and status=0)
$pdo->table('plat_user')
    ->where([
        ['actor', '=', 1], 
        ['status', '=', 0]
    ]);
    
//2.一维数组 sql:select * from `plat_user` where (actor=1)
$pdo->table('plat_user')
    ->where(['actor', '=', 1]);
    
//3.分开传参 sql:select * from `plat_user` where (actor=1)
$pdo->table('plat_user')
    ->where('actor', '=', 1);   
```



#### whereOr()

参数会形成OR组合

```php
//1. sql:select * from `plat_user` where (actor=1 or status=0)
$pdo->table('plat_user')
    ->whereOr([
        ['actor', '=', 1], 
        ['status', '=', 0]
    ]);
```



#### whereIn()

```php
//1.三维数组传参 sql:select * from `plat_user` where (actor in (1,2,3) and status in(0,1))
$pdo->table('plat_user')
    ->whereIn([
        ['actor', [1,2,3]], 
        ['status', [0,1]]
    ]);
    
//2.二维数组传参 sql:select * from `plat_user` where (actor in (1,2,3))
$pdo->table('plat_user')
    ->whereIn(['actor', [1,2,3]]);
    
//2分开参数传参 sql:select * from `plat_user` where (actor in (1,2,3))
$pdo->table('plat_user')
    ->whereIn('actor', [1,2,3]);  
```



#### whereInOr()

```php
//1.三维数组传参 sql:select * from `plat_user` where (actor in (1,2,3) OR status in (0,1))
$pdo->table('plat_user')
    ->whereIn([
        ['actor', [1,2,3]], 
        ['status', [0,1]]
    ]);
```



#### whereNotIn()

```php
//1.三维数组传参 sql:select * from `plat_user` where (actor not in (1,2,3) AND status not in (0,1))
$pdo->table('plat_user')
    ->whereNotIn([
        ['actor', [1,2,3]], 
        ['status', [0,1]]
    ]);
    
//2.二维数组传参 sql:select * from `plat_user` where (actor not in (1,2,3))
$pdo->table('plat_user')
    ->whereNotIn(['actor', [1,2,3]]);
        
//3.分开参数传参 sql:select * from `plat_user` where (actor not in (1,2,3))
$pdo->table('plat_user')
    ->whereNotIn('actor', [1,2,3]);
```



#### whereNotInOr()

```php
//1.三维数组传参 sql:select * from `plat_user` where (actor not in (1,2,3) OR status not in (0,1))
$pdo->table('plat_user')
    ->whereNotInOr([
        ['actor', [1,2,3]], 
        ['status', [0,1]]
    ]);
```



#### whereRaw()

```php
//1.三维数组传参 sql:select * from `plat_user` where (actor=1 and status=0)
$pdo->table('plat_user')
    ->whereRaw('actor=? and status=?', [1, 0]);
```



## 获取数据

### get()

```php
//1.sql:select * from `plat_user` where (actor=1 and status=0)  这里只是将组装的预处理sql用execute执行，并且返回 PDOStatement结构的数据
$pdo->table('plat_user')
    ->whereRaw('actor=? and status=?', [1, 0])
    ->get();
```

###  first()

```php
//1.在get()的基础上fetch取出一条数据
$pdo->table('plat_user')
    ->whereRaw('actor=? and status=?', [1, 0])
    ->first();
```

### all()

```php
//1.在get()的基础上fetchAll取出全部数据
$pdo->table('plat_user')
    ->whereRaw('actor=? and status=?', [1, 0])
    ->all();
```

### count()

```php
//1.sql:select count(`id`) total from `plat_user` where (actor=1 and status=0) 但是count方法会直接返回int统计数
$pdo->table('plat_user')
    ->whereRaw('actor=? and status=?', [1, 0])
    ->count();
```

###  groupBy
```php
//1.sql:select * from `plat_user` where (status=0) group by actor 
$pdo->table('plat_user')
    ->where('status', 0)
    ->groupBy('actor')
    ->all();
```

### orderBy

```php
//1.sql:select * from `plat_user` where (status=0) order by id desc 
$pdo->table('plat_user')
    ->where('status', 0)
    ->orderBy('id desc')
    ->all();
```
### join
```php
$pdo->table('plat_user', 'u')
    ->join('plat_config_detail as p', 'u.id=p.upUser')
    ->join('plat_actor as m', 'm.id=u.actor', 'LEFT')
    ->limit(0, 20)
    ->field('u.id, u.account, p.upUser, p.configId, u.actor, m.name as actor_name')
    ->all();
```
## 插入数据

### insert()

```php
//数据
$data = [
    'id'=>1,
    'account'    => 'xiaoming',
    'name'   => 'ming',
    'real_name'  => '小名',
    'password'   => '8ef7d5456ebff0b52316376c7649670d',
    'phone'   => '13800138000',
    'area' => 0,
    'actor' => 1,
    'permission' => '{"10000":["exchangerefund","phprun","buserinfo","operalog","catVerCode","channelconf","approveOper","approveLogs","approveApply","approveConfig"],"9999":["buserinfo","exchangerefund","phprun","exchangeship","applySend","catVerCode","actorAdmin","editAdmin","menuAdmin","area","confManager","robot"],"1":[]}',
    'status' => 2,
    'gameids' => '',
];
//返回的是ID
$id = $pdo->table('plat_user')
    ->insert($data);
```
### insertAll()
```php
$data = [
    ['name' => 'test1', 'permission' => 'xxx', 'ctime' => time(), 'gameids' => ''],
    ['name' => 'test2', 'permission' => 'xxx', 'ctime' => time(), 'gameids' => ''],
    ['name' => 'test3', 'permission' => 'xxx', 'ctime' => time(), 'gameids' => ''],
    ['name' => 'test4', 'permission' => 'xxx', 'ctime' => time(), 'gameids' => ''],
    ['name' => 'test5', 'permission' => 'xxx', 'ctime' => time(), 'gameids' => ''],
    ['name' => 'test6', 'permission' => 'xxx', 'ctime' => time(), 'gameids' => ''],
    ['name' => 'test7', 'permission' => 'xxx', 'ctime' => time(), 'gameids' => ''],
];

$rowCount = $pdo->table('plat_actor')->insertAll($data);
```
## 更新数据

### update()

```php
$data = ['actor' => 1];
//返回的是bool
$status = $pdo->table('plat_user')
    ->where(['id', '=', 1])
    ->update($data);
```

## 删除数据

### delete()

```php
//删除
$status = $pdo->table('plat_user')
            ->where([['id', '=', '56']])
            ->delete();
```

## 事务

### beginTransaction()

```php
$pdo->beginTransaction();
```

### commit()

```php
$pdo->commit();
```

### rollBack()

```php
$pdo->rollBack();
```
