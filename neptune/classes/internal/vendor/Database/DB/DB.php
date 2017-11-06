<?php

namespace Database\DB;

use PDO;

class DB
{
  protected static $instance;
  protected $error;
  protected $conn;
  protected $table;
  protected $from;
  protected $columns = ['*'];
  protected $where;
  protected $query;
  protected $results;
  protected $count;
  protected $limit;

  //
  protected static function init()
  {
    if ( ! self::$instance instanceof self ) {
      static::$instance = new self;
    }

    return self::$instance;
  }
  public static function connectDatabase(array $connect)
  {
    $init = self::init();

    if ( ! isset($connect['host']) || ! isset($connect['username']) || ! isset($connect['password']) || ! isset($connect['database']) ) {
      die('No connection to the database was established. Not all required information is entered.');
    }

    $connection = new Connection;
    $connection->setConnectionInformations($connect);
    $connection->connect();

    $init->conn = $connection->conn;
  }
  protected function get_primary_key()
  {
    $query = $this->query;
    $this->query = 'SHOW KEYS FROM '.$this->table.' WHERE Key_name = \'PRIMARY\'';
    $result = $this->conn->prepare('SHOW KEYS FROM '.$this->table.' WHERE Key_name = \'PRIMARY\'');
    $result->execute();
    $result = $result->fetchAll();

    if (empty($result)) {
      return false;
    }

    $primary_key = $result[0]->Column_name;
    $this->query = $query;

    return $primary_key;
  }
  public static function table($table)
  {
    $db = self::init();
    $db->table = $table;

    return $db;
  }
  public function where($where)
  {
    $this->where = $where;

    return $this;
  }
  public function get(...$columns)
  {
    $this->from = 'FROM `'.$this->table.'`';

    if ( ! empty($columns) ) {
      $this->columns = $columns;
    }

    return $this->execute();
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
  public function first()
  {
    // echo $this->get_primary_key();
  }
  public function last()
  {

  }
  public static function errors()
  {
    $init = self::init();

    return $init->error;
  }
  public static function count()
  {
    $init = self::init();

    return $init->count;
  }
  protected function execute()
  {
    $builder = new QueryBuilder;

    $params = Where::returnParams();
    $type = Type::getSQLType($builder->generate('SELECT', $this->columns, $this->table, [
      'WHERE' => [
        $this->where,
      ],
    ]));

    Switch($type) {
      case 'SELECT':
        $this->error = false;

        if ($this->query = $this->conn->prepare($select)) {
          $x = 1;

          if (count($params)) {
            foreach ($params as $param) {
              $this->query->bindValue($x, $param);
              $x++;
            }
          }

          if ($this->query->execute()) {
            $this->results = $this->query->fetchAll();
            $this->count = $this->query->rowCount();
          }else {
            $this->error = true;
          }
        }

        return $this->results;
      break;
    }
  }
}
