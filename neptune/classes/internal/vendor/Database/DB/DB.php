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
}
