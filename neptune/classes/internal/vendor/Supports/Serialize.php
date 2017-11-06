<?php

namespace Sup;

class Serialize
{
  public static function encode($data) : String
  {
    return serialize($data);
  }
  public static function decode(string $data, bool $array = false)
  {
    if($array === false) {
      return (object) unserialize($data);
    }else {
      return (array) unserialize($data);
    }
  }
  public static function decodeObject(String $data) : \stdClass
  {
    return self::$decode($data, false);
  }
  public static function decodeArray(String $data) : Array
  {
    return self::$decode($data, true);
  }
}
