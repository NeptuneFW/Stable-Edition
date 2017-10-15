<?php

class ClassMap
{
  private $regex = [
    '/\\./',
  ];
  private $config = 'astronaut.config.autoload';
  private $classes = [];
  private $classmap = '';

  public function up()
  {
    $config = $this->config;
    $replace = preg_replace($this->regex[0], '/', $config);
    $replace .= '.php';
    $directories = $this->scandir();
    $classes = [];

    foreach($directories as $dirs) {
      if (is_dir($dirs)) {
        $classes = array_merge($classes, static::createMap($dirs));
      }
    }

    $generate = preg_replace(['/array \(/', '/\)/'], ['[', '];'], var_export($classes, true));

    return $this->classmap = $generate;
  }
  public function down()
  {
    return $this->classmap;
  }
  public static function createMap($dir)
  {
    if (is_string($dir)) {
      $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    }

    $map = [];

    foreach ($dir as $file) {
      if ( ! $file->isFile() ) {
        continue;
      }

      $path = $file->getRealPath() ?: $file->getPathname();

      if ('php' !== pathinfo($path, PATHINFO_EXTENSION)) {
        continue;
      }

      $classes = self::findClasses($path);

      if (PHP_VERSION_ID >= 70000) {
        gc_mem_caches();
      }
      foreach ($classes as $class) {
        $map[$class] = $path;
      }
    }

    return $map;
  }
  private function scandir()
  {
    function listdirs($dir) {
      static $alldirs = [];

      $directories = glob($dir.'/*');

      if (count($directories) > 0) {
        foreach ($directories as $d) {
          $alldirs[] = ROOT.$d;
        }
      }
      foreach ($directories as $dir){
        listdirs($dir);
      }

      return $alldirs;
    }

    $directory_list = listdirs('neptune');

    return $directory_list;
  }
  private static function findClasses($path)
  {
    $contents = file_get_contents($path);
    $tokens = token_get_all($contents);
    $classes = [];
    $namespace = '';

    for ($i = 0; isset($tokens[$i]); ++$i) {
      $token = $tokens[$i];

      if ( ! isset($token[1]) ) {
        continue;
      }

      $class = '';

      switch ($token[0]) {
        case T_NAMESPACE:
          $namespace = '';

          while (isset($tokens[++$i][1])) {
            if (in_array($tokens[$i][0], [T_STRING, T_NS_SEPARATOR])) {
              $namespace .= $tokens[$i][1];
            }
          }

          $namespace .= '\\';
        break;
        case T_CLASS:
        case T_INTERFACE:
        case T_TRAIT:
          $isClassConstant = false;

          for ($j = $i - 1; $j > 0; --$j) {
            if ( ! isset($tokens[$j][1]) ) {
              break;
            }
            if (T_DOUBLE_COLON === $tokens[$j][0]) {
              $isClassConstant = true;

              break;
            }else if ( ! in_array($tokens[$j][0], [T_WHITESPACE, T_DOC_COMMENT, T_COMMENT]) ) {
              break;
            }
          }
          if ($isClassConstant) {
            break;
          }

          while (isset($tokens[++$i][1])) {
            $t = $tokens[$i];
            if (T_STRING === $t[0]) {
              $class .= $t[1];
            }else if ('' !== $class && T_WHITESPACE === $t[0]) {
              break;
            }
          }

          $classes[] = ltrim($namespace.$class, '\\');
        break;
        default:
        break;
      }
    }

    return $classes;
  }
}
