<?php

namespace Database;

use PDO;
use PSR4;

class DB
{
  private static $instance;
  private static $debug = false;
  private $table;
  private $conn;
  private $columns = ['*'];
  private $message = [];
  private $query;
  private $where;
  private $join;
  private $query_data = [];
  private $limit;
  private $order;
  private $get_id = false;
  private $from;

  public static function connect(array $connect)
  {
    if ( ! isset($connect['host']) || ! isset($connect['username']) || ! isset($connect['password']) || ! isset($connect['database']) ) {
      die('No connection to the database was established. Not all required information is entered.');
    }

    self::$host = $connect['host'];
    self::$username = $connect['username'];
    self::$password = $connect['password'];
    self::$database = $connect['database'];
    self::$charset = isset($connect['charset']) ? $connect['charset'] : 'utf8';
  }
  protected function __construct()
  {
    try {
      $this->conn = new PDO('mysql:host='.self::$host.';dbname='.self::$database.';charset='.self::$charset, self::$username, self::$password, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
      ]);
    }catch (PDOException $e) {
      throw new PDOException('Database Connection Error: '.$e->getMessage());
    }
  }
  protected static function init()
  {
    if ( ! self::$instance instanceof self ) {
      static::$instance = new self;
    }

    return self::$instance;
  }
  public static function debug()
  {
    self::$debug = true;
  }
  protected function get_primary_key()
  {
    $query = $this->query;
    $this->query = 'SHOW KEYS FROM '.$this->table.' WHERE Key_name = \'PRIMARY\'';
    $result = $this->execute();

    if (empty($result)) {
      return false;
    }

    $primary_key = $result[0]->Column_name;
    $this->query = $query;

    return $primary_key;
  }
  public function insert_get_id(array $data)
  {
    $this->get_id = true;

    return $this->insert($data);
  }
  public function insert(array $data)
  {
    $this->query = 'INSERT INTO ' . $this->table . ' (';
    $tmp = '';
    foreach ($data as $key => $value) {
      $this->query .= $tmp . $key;
      $tmp = ', ';
    }

    $this->query .= ') VALUES (';
    $tmp = '';

    foreach ($data as $key => $value) {
      $this->query .= $tmp . '?';
      $tmp = ', ';
      $this->query_data[] = $value;
    }

    $this->query .= ');';

    return $this->execute('INSERT');
  }
  public static function table($table)
  {
    $db = self::init();
    $db->clean();
    $db->table = $table;

    return $db;
  }
  public function update(array $data)
  {
    $this->query = 'UPDATE ' . $this->table . ' SET ';
    $tmp = '';
    $this->query_data = [];

    foreach ($data as $key => $value) {
      $this->query .= $tmp . '`'.$key.'`' . ' = ?';
      $tmp = ', ';
      $this->query_data[] = $value;
    }

    $this->from = null;

    return $this->execute('UPDATE');
  }
  public function delete($all = false)
  {
    if (empty($this->where) && $all === false) {
      die('Warning: You are trying to delete all the records.');
    }

    $this->query = 'DELETE FROM ' . $this->table;

    return $this->execute('DELETE');
  }
  public function where_in($column, array $data)
  {
    $newData = [];

    foreach ($data as $value) {
      $newData[] = (is_integer($value)) ? $value : '\''.$value.'\'';
    }

    $this->where = '`'.$column . '`'.' IN ('.implode(', ', $newData).')';

    return $this;
  }
  public function like($column, $value)
  {
    $this->where('`'.$column.'`', 'LIKE', "'%$value%'");

    return $this;
  }
  public function where($column, $operator = null, $value = null, $connector = 'AND')
  {
    $where = $column.' '.$operator.' '.$value;

    if (empty($this->where)) {
      $this->where = $where;
    }else {
      $this->where .= ' '.$connector.' '.$where;
    }

    return $this;
  }
  public function and_where($column, $operator = null, $value = null)
  {
    return $this->where($column, $operator, $value, 'AND');
  }
  public function or_where($column, $operator = null, $value = null)
  {
    return $this->where($column, $operator, $value, 'OR');
  }
  public function join($table, $col1, $operator = null, $col2 = null, $string = false, $type = 'INNER')
  {
    $col2 = ($string === true) ? '\''.$col2.'\'' : $col2;
    $join = $type.' JOIN '.'`'.$table.'`'.' ON '.' '.$col1.' '.$operator.' '.$col2;
    $this->join .= ' '.$join;

    return $this;
  }
  public function left_join($table, $col1, $operator = null, $col2 = null)
  {
    $this->join($table, $col1, $operator, $col2, 'LEFT');

    return $this;
  }
  public function right_join($table, $col1, $operator = null, $col2 = null)
  {
    $this->join($table, $col1, $operator, $col2, 'RIGHT');

    return $this;
  }
  public function min($column = null)
  {
    return $this->minOrMax($column, 'MIN');
  }
  public function max($column = null)
  {
    return $this->minOrMax($column, 'MAX');
  }
  private function minOrMax($column = null, $minOrMax = 'MAX')
  {
    if (is_null($column)) {
      $column = $this->get_primary_key();
    }

    $minOrMax = ($minOrMax==='MAX' ? 'MAX' : 'MIN');
    $this->limit = 1;
    $this->query = 'SELECT '.strtoupper($minOrMax).'('.$column.') as '.strtolower($minOrMax).' FROM '.$this->table;

    return $this->execute();
  }
  public function avg($column)
  {
    $this->limit = 1;
    $this->query = 'SELECT AVG('.$column.') as average FROM '.$this->table;

    return $this->execute();
  }
  public function sum($column)
  {
    $this->limit = 1;
    $this->query = 'SELECT SUM('.$column.') AS sum FROM '.$this->table;

    return $this->execute();
  }
  public function count($column = '*')
  {
    $this->limit = 1;
    $this->query = 'SELECT COUNT('.$column.') AS count FROM '.$this->table;

    return $this->execute();
  }
  public static function raw($query, $command = false)
  {
    $conn = self::init()->conn;

    if ($command) {
      return $conn->exec($query);
    }

    $sth = $conn->query($query);

    return $sth->fetchAll();
  }
  public function limit($amount = 1)
  {
    $this->take($amount);

    return $this;
  }
  public function take($amount = 1)
  {
    $this->limit = (int)$amount;

    return $this;
  }
  public function offset($amount)
  {
    if (isset($this->limit)) {
      $this->limit .= ', '.(int)$amount;
    }

    return $this;
  }
  public function last()
  {
    return $this->first(true);
  }
  public function first($last = false)
  {
    $primary_key = $this->get_primary_key();
    $this->from = ' FROM ' . '`'.$this->table.'`';
    $this->order = ' ORDER BY '.$primary_key;
    $this->order .= ($last === true) ? ' DESC' : '';
    $this->limit = 1;

    return $this->execute();
  }
  public function order_by($column, $keyword)
  {
    if (empty($this->order)) {
      $this->order = ' ORDER BY '.'`'.$column.'`' . ' '.strtoupper($keyword);
    }else {
      $this->order .= ', '.'`'.$column.'`'.' '.strtoupper($keyword);
    }

    return $this;
  }
  public function only(...$args)
  {
    return $this->get(empty($args) ? ['*'] : $args);
  }
  public function find($data, $column = null)
  {
    $this->from = ' FROM '.'`'.$this->table.'`';
    if (is_array($data) && $column === null) {
      foreach ($data as $key => $value) {
        $this->where('`'.$key . '`', '=', '\''.$value.'\'');
      }
    }else {
      $this->limit = 1;
      $this->where( ! is_null($column) ? $column : '`id`', '=', (int)$data );
    }

    return $this->execute();
  }
  public function get(array $columns = [])
  {
    $this->from = ' FROM '.'`'.$this->table.'`';

    if ( ! empty($columns) ) {
      $this->columns = $columns;
    }

    return $this->execute();
  }
  protected function clean()
  {
    $this->where = null;
    $this->where_data = null;
    $this->join  = null;
    $this->limit = null;
    $this->order = null;
    $this->from = null;
    $this->query = null;
    $this->query_data = null;
    $this->columns = ['*'];
  }
  public function __call($method, $params)
  {
    if ( ! preg_match('/^(find)By(\w+)$/i', $method, $matches) ) {
      die('Called to undefined method ['.$method.']');
    }

    $criterialKeys = explode('And', preg_replace('/([a-z0-9])([A-Z])/', '$1$2', $matches[2]));
    $criterialKeys = array_map('strtolower', $criterialKeys);
    $criterialValues = array_slice($params, 0, count($criterialKeys));
    $criteria = array_combine($criterialKeys, $criterialValues);
    $method = $matches[1];

    return $this->$method($criteria);
  }
  private function __clone() {}
  public static function __callStatic($name, $args)
  {
    $tableGet = preg_match('/(\w+)Result/', $name, $get);
    $tableGet = preg_match('/(\w+)Get/', $name, $get);

    if ( ! empty($tableGet) ) {
      echo 'ok';
    }
  }
  public function execute($type = 'SELECT')
  {
    if (strlen($this->query) === 0) {
      $this->query = 'SELECT ';
      $tmp = '';

      foreach($this->columns as $column) {
        $this->query .= $tmp.$column;
        $tmp = ', ';
      }
    }
    if (strlen($this->query) === 0) {
      $this->query .= ' FROM ' . $this->table;
    }
    if ( ! is_null($this->from) ) {
      $this->query .= $this->from;
    }
    if ( ! empty($this->join) ) {
      $this->query .= $this->join;
    }
    if ( ! empty($this->where) ) {
      $this->query .= ' WHERE '.$this->where;
    }
    if ( ! empty($this->order) ) {
      $this->query .= $this->order;
    }
    if ( ! empty($this->limit) && $type != 'UPDATE' ) {
      $this->query .= ' LIMIT '.$this->limit;
    }

    $sth = $this->conn->prepare($this->query);
    $sth->execute($this->query_data);

    if (self::$debug) {
      echo 'Database Errors';
      echo '<br>';
      var_dump($this->conn->errorInfo());
      echo '<br>';
      echo 'QUERY:';
      var_dump($this->query);
      echo '<br>';
      echo 'Query Data';
      var_dump($this->query_data);
      echo '<br>';
      echo 'Where Clause';
      var_dump($this->where);
      echo '<br>';
    }
    switch (strtoupper($type)) {
      case 'SELECT':
        $data = (isset($this->limit) && $this->limit === 1) ? $sth->fetch() : $sth->fetchAll();
      break;
      case 'INSERT':
        if ($sth->rowCount() > 0) {
          return ($this->get_id) ? $this->conn->lastInsertId() : true;
        }else {
          return false;
        }
      break;
      case 'UPDATE':
      case 'DELETE':
        return ($sth->rowCount() > 0) ? true : false;
      break;
      default:
        die('Called to undefined action ['.$type.']');
      break;
    }
    $this->clean();

    return (count($data) > 0) ? $data : null;
  }
}
