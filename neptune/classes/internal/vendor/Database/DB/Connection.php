<?php

namespace Database\DB;

use PDO;
use PDOException;

class Connection
{
  protected $connectionInformations = [];
  public $conn;

  public function setConnectionInformations(array $connect)
  {
    $this->connectionInformations = [
      'host' => $connect['host'],
      'username' => $connect['username'],
      'password' => $connect['password'],
      'database' => $connect['database'],
      'charset' => isset($connect['charset']) ? $connect['charset'] : 'utf8',
    ];
  }
  public function connect()
  {
    try {
      $host = $this->connectionInformations['host'];
      $database = $this->connectionInformations['database'];
      $charset = $this->connectionInformations['charset'];
      $username = $this->connectionInformations['username'];
      $password = $this->connectionInformations['password'];

      $this->conn = new PDO('mysql:host='.$host.';dbname='.$database.';charset='.$charset, $username, $password, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
      ]);
    }catch (PDOException $e) {
      throw new PDOException('Database Connection Error: '.$e->getMessage());
    }
  }
}
