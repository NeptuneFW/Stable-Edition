<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', realpath('.').DS);

require_once 'neptune/core/classes/Autoloader/ClassMapGenerator.php';
require_once 'neptune/core/classes/Autoloader/ClassMap.php';
require_once 'neptune/core/classes/Autoloader/AutoloadRegister.php';

$classmap = new ClassMapGenerator;

$classmap->generate(
  'neptune',
  'neptune/autoload/classmap.php'
);

$classmap = require ROOT.'neptune/autoload/classmap.php';

$autoload = new AutoloadRegister;
$autoload->setClassMap($classmap);
$autoload->registerClassMap($classmap);
print_r(get_declared_classes());
new PSR4();
