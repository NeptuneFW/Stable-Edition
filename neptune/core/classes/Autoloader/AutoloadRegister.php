<?php

class AutoloadRegister
{
  private $map = [];

  public function setClassMap(array $map)
  {
    $this->map = $map;
  }
  public function registerClassMap($prepend = false)
  {
    spl_autoload_register([$this, 'loadClass'], true, $prepend);
  }
  public function loadClass($class)
  {
    if (isset($this->map[$class])) {
      require $this->map[$class];
    }
  }
}
