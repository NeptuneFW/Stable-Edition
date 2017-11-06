<?php

class NT
{
  private static $defines = [];

  public static function kernel()
  {
    self::checkPHPVersion();

    self::loadDefines();
    $defines = (object) self::$defines;

    require_once $defines->ROOT.'neptune/bootstrap.php';
  }
  public static function getDefinesList($object = false)
  {
    $defines = self::$defines;

    if ($object === true) {
      $defines = (object) self::$defines;
    }

    return $defines;
  }
  public static function setDefines(array $defines)
  {
    if ( ! is_array($defines) ) {
      die('First parameter must be an array.');
    }

    foreach($defines as $key => $value) {
      define($key, $value);
    }

    return new self;
  }
  private static function loadDefines()
  {
    self::setDefines([
      'DS' => DIRECTORY_SEPARATOR,
      'ROOT' => realpath('.').DIRECTORY_SEPARATOR,
    ]);

    $defines = get_defined_constants(true)['user'];

    return self::$defines = $defines;
  }
  private static function checkPHPVersion()
  {
    if (version_compare(phpversion(), '7.0', '<')) {
      die('Neptune Framework is written in PHP 7.0. Your PHP version is "'.phpversion().'". Please upgrade your PHP version.');
    }
  }
}
