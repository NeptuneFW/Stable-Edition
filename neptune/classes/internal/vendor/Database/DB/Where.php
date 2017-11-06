<?php

namespace Database\DB;

class Where
{
  protected static $instance;
  protected $where;
  protected $params;

  public static function init()
  {
    if ( ! self::$instance instanceof self ) {
      static::$instance = new self;
    }

    return self::$instance;
  }
  public static function where($column, $operator = null, $value = null, $connector = 'AND')
  {
    $init = self::init();

    $init->params[] = $value;

    $value = is_integer($value) ? $value : '\''.$value.'\'';
    $where = $column.' '.$operator.' ?';

    if (empty($init->where)) {
      $init->where = $where;
    }else {
      $init->where .= ' '.$connector.' '.$where;
    }

    return $init;
  }
  public function and($column, $operator = null, $value = null)
  {
    $init = self::init();

    $init->where($column, $operator, $value, 'AND');

    return $init;
  }
  public function or($column, $operator = null, $value = null)
  {
    $init = self::init();

    $init->where($column, $operator, $value, 'OR');

    return $init;
  }
  public function in()
  {

  }
  public function return()
  {
    return $this->where;
  }
  public static function returnParams()
  {
    $init = self::init();

    return $init->params;
  }
}
