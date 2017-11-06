<?php

namespace Sup;

class Json
{
  public static function encode($data, string $type = 'unescaped_unicode')
  {
    return json_encode($data, Converter::toConstant($type, 'JSON_'));
  }
  public static function decode(string $data, bool $array = false, int $length = 512)
  {
    $return = json_decode($data, $array, $length);

    return $return;
  }
  public static function decodeObject(string $data, int $length = 512)
  {
    return self::decode($data, false, $length);
  }
  public static function decodeArray(string $data, int $length = 512)
  {
    return (array) self::decode($data, true, $length);
  }
  public static function error()
  {
    return json_last_error_msg();
  }
  public static function errVal()
  {
    return json_last_error_msg();
  }
  public static function errNo()
  {
    return json_last_error();
  }
  public static function check(string $data)
  {
    return (is_array(json_decode($data, true)) && self::errNo() === 0);
  }
}
