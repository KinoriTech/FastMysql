# FastMysql

This lightweight database class is written with PHP and uses the MySQLi extension,
it uses prepared statements to properly secure your queries, no need to worry about SQL injection attacks.

Based on the code at by David Adams at [super-fast-php-mysql-database-class](https://codeshack.io/super-fast-php-mysql-database-class/).

The MySQLi extension has built-in prepared statements that you can work with, 
this will prevent SQL injection and prevent your database from being exposed, 
some developers are confused on how to use these methods correctly so David
created (and we refined) this easy to use database class that'll do the work 
for you. 

This database class is beginner-friendly and easy to implement, with the native 
MySQLi methods you need to write 3-7 lines of code to retrieve data from a 
database, with this class you can do it with just 1-2 lines of code, and is 
much easier to understand.

## Install

The recommended way is via composer:

```bash
composer require KinoriTech/FastMysql
```

## How To Use

### Connect to MySQL database:

```php
use KinoriTech\FastMysql\Connection;

$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'example';

$db = new Connection($dbhost, $dbuser, $dbpass, $dbname);
```

### Fetch a record from a database:
```php
$account = $db->query('SELECT * FROM accounts WHERE username = ? AND password = ?', 'test', 'test')->fetchArray();
echo $account['name'];
```

Or you could do:

```php
$account = $db->query('SELECT * FROM accounts WHERE username = ? AND password = ?', array('test', 'test'))->fetchArray();
echo $account['name'];
```

### Fetch multiple records from a database:

```php
$accounts = $db->query('SELECT * FROM accounts')->fetchAll();

foreach ($accounts as $account) {
    echo $account['name'] . '<br>';
}
```

You can specify a callback if you do not want the results being stored in an array (useful for large amounts of data):

```php
$db->query('SELECT * FROM accounts')->fetchAll(function($account) {
echo $account['name'];
});
```

If you need to break the loop you can add:

`return 'break';`

### Get the number of rows:
```php
$accounts = $db->query('SELECT * FROM accounts');
echo $accounts->numRows();
```

### Get the affected number of rows:

```php
$insert = $db->query('INSERT INTO accounts (username,password,email,name) VALUES (?,?,?,?)', 'test', 'test', 'test@gmail.com', 'Test');
echo $insert->affectedRows();
```

### Get the total number of queries:
```php
echo $db->query_count;

```

### Get the last insert ID:

```php
echo $db->lastInsertID();
```

### Close the database:

```php
$db->close();
```



