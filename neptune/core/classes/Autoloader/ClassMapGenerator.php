<?php

class ClassMapGenerator
{
  public function generate($directory_name, $classmap_save_file)
  {
    $classmap = new ClassMap();
    $up = $classmap->up();
    $down = $classmap->down();

    file_put_contents($classmap_save_file, sprintf('<?php '."\n\n".'return %s', $down));
  }
}
