<?php

namespace Sup;

use NT;

class Config
{
  public static function __callStatic($name, $args)
  {
    $path = $args[0];

    if ($path) {
      $config = require NT::getDefinesList(true)->ROOT.'neptune/core/config/'.$name.'.php';
      $path = explode('.', $path);

      foreach ($path as $bit) {
        if (isset($config[$bit])) {
          $config = $config[$bit];
        }
      }

      return $config;
    }
  }
  public static function generate($name, array $content)
  {
    $configDir = NT::getDefinesList(true)->ROOT.'neptune/core/config/';
    $content = preg_replace(['/array \(/', '/\)/'], ['[', ']'], var_export($content, true));

    if ( ! file_exists($configDir.$name.'.php') ) {
      file_put_contents($configDir.$name.'.php', sprintf('<?php "'"\n\n"'" return %s;', $content));
    }
  }
  public static function delete($name)
  {
    if (file_exists(NT::getDefinesList(true)->ROOT.'neptune/core/config/'.$name.'.php')) {
      return unlink(NT::getDefinesList(true)->ROOT.'neptune/core/config/'.$name.'.php');
    }
  }
}
