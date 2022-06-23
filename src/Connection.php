<?php
/**
 * Taken from https://codeshack.io/super-fast-php-mysql-database-class/
 * Released under the MIT License.
 * (c) David Adams
 */

namespace KinoriTech\FastMysql;

use mysqli;

class Connection {

	public $query_count = 0;

	/**
	 * @param string $dbhost	host name/ip (default localhost)
	 * @param string $dbuser	database user (default root)
	 * @param string $dbpass	database user's password (default <empty>)
	 * @param string $dbname	database to connecto to  (default <empty>)
	 * @param int $port			port to use (default 3306)
	 * @param string $charset	charset to use (default utf8)
	 */
	public function __construct(
		$dbhost = 'localhost',
		$dbuser = 'root',
		$dbpass = '',
		$dbname = '',
		$port = 3306,
		$charset = 'utf8') {
		$this->connection = new mysqli($dbhost, $dbuser, $dbpass, $dbname, $port);
		if ($this->connection->connect_error) {
			$this->error('Failed to connect to MySQL - ' . $this->connection->connect_error);
		}
		$this->connection->set_charset($charset);
	}

	/**
	 * @throws DbException
	 */
	public function query($query) {
        if (!$this->query_closed) {
            $this->query->close();
        }
		if ($this->query = $this->connection->prepare($query)) {
            if (func_num_args() > 1) {
                $x = func_get_args();
                $args = array_slice($x, 1);
				$types = '';
                $args_ref = array();
                foreach ($args as $k => &$arg) {
					if (is_array($args[$k])) {
						foreach ($args[$k] as $j => &$a) {
							$types .= $this->_gettype($args[$k][$j]);
							$args_ref[] = &$a;
						}
					} else {
	                	$types .= $this->_gettype($args[$k]);
	                    $args_ref[] = &$arg;
					}
                }
				array_unshift($args_ref, $types);
                call_user_func_array(array($this->query, 'bind_param'), $args_ref);
            }
            $this->query->execute();
           	if ($this->query->errno) {
				//$this->error('Unable to process MySQL query (check your params) - ' . $this->query->error);
                if ($this->fail_on_error && !$this->on_transaction) {
                    throw new DbException($this->query->errno);
                }
           	}
            $this->query_closed = FALSE;
			$this->query_count++;
        } else {
            $this->error('Unable to prepare MySQL statement (check your syntax) - ' . $this->connection->error);
        }
		return $this;
    }

    public function startTransaction() {
	    if ($this->on_transaction) {
	        $this->error('Transaction already started.');
        }
	    mysqli_autocommit($this->connection, false);
	    $this->on_transaction = TRUE;
    }

    public function commitTransaction() {
	    if (!$this->on_transaction) {
	        $this->error('Transaction not started. Call startTransaction to start a transaction');
        }
        mysqli_commit($this->connection);
        mysqli_autocommit($this->connection, true);
	    $this->on_transaction = FALSE;

    }

    public function rollBackTransaction() {
	     if (!$this->on_transaction) {
	        $this->error('Transaction not started. Call startTransaction to start a transaction');
        }
	    mysqli_rollback($this->connection);
	    mysqli_autocommit($this->connection, true);
	    $this->on_transaction = FALSE;
    }


	public function fetchAll($callback = null) {
	    $params = array();
        $row = array();
	    $meta = $this->query->result_metadata();
	    while ($field = $meta->fetch_field()) {
	        $params[] = &$row[$field->name];
	    }
	    call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();
        while ($this->query->fetch()) {
            $r = array();
            foreach ($row as $key => $val) {
                $r[$key] = $val;
            }
            if ($callback != null && is_callable($callback)) {
                $value = call_user_func($callback, $r);
                if ($value == 'break') break;
            } else {
                $result[] = $r;
            }
        }
        $this->query->close();
        $this->query_closed = TRUE;
		return $result;
	}

	public function fetchArray() {
	    $params = array();
        $row = array();
	    $meta = $this->query->result_metadata();
	    while ($field = $meta->fetch_field()) {
	        $params[] = &$row[$field->name];
	    }
	    call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();
		while ($this->query->fetch()) {
			foreach ($row as $key => $val) {
				$result[$key] = $val;
			}
		}
        $this->query->close();
        $this->query_closed = TRUE;
		return $result;
	}

	public function close() {
		return $this->connection->close();
	}

    public function numRows() {
		$this->query->store_result();
		return $this->query->num_rows;
	}

	public function affectedRows() {
		return $this->query->affected_rows;
	}

    public function lastInsertID() {
    	return $this->connection->insert_id;
    }

    public function error($error) {
        if ($this->show_errors) {
            exit($error);
        }
    }

	public function mysqli_real_escape_string($value) {
		return mysqli_real_escape_string($this->connection, $value);
	}

	protected $connection;
	protected $query;
	protected $show_errors = TRUE;
	protected $query_closed = TRUE;
	protected $on_transaction = FALSE;

	private function _gettype($var) {
	    if (is_string($var)) return 's';
	    if (is_float($var)) return 'd';
	    if (is_int($var)) return 'i';
	    return 'b';
	}

}
