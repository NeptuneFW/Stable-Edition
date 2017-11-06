<?php

namespace Sup;

class Vars
{
  public static function bool($var)
  {
    return boolval($var);
  }
  public static function boolean($var)
  {
    return boolval($var);
  }
  public static function float($var)
  {
    return floatval($var);
  }
  public static function double($var)
  {
    return floatval($var);
  }
  public static function int($var)
  {
    return intval($var);
  }
  public static function integer($var)
  {
    return intval($var);
  }
  public static function string($var)
  {
    return strval($var);
  }
  public static function type($var)
  {
    return gettype($var);
  }
  public static function resourceType($resource)
  {
    if( ! is_resource($resource) ) {
      die('First parameter must be an resource.');
    }

    return get_resource_type($resource);
  }
  public static function serial($var)
  {
    return serialize($var);
  }
  public static function unserial(string $var)
  {
    return unserialize($var);
  }
  public static function remove($var)
  {
    unset($var);
  }
  public static function delete($var)
  {
    self::$remove($var);
  }
  public static function toType($var, string $type = 'integer')
  {
    settype($var, $type);

    return $var;
  }
}
