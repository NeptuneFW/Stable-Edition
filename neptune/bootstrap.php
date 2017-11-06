<?php

// Set Defines List (object)
$definesList = NT::getDefinesList(true);

// Require a PSR4 Autoloader Class
require_once $definesList->ROOT.'neptune/classes/internal/vendor/Autoloader/PSR4.php';

// Start a PSR4 Autoload
$loader = new PSR4;

// Larissa Error Handler Classes Autoloads
$loader->addNamespace('Larissa\\', $definesList->ROOT.'neptune/classes/internal/vendor/Larissa');
$loader->addNamespace('Larissa\\Util\\', $definesList->ROOT.'neptune/classes/internal/vendor/Larissa/Util');
$loader->addNamespace('Larissa\\Handler\\', $definesList->ROOT.'neptune/classes/internal/vendor/Larissa/Handler');
$loader->addNamespace('Larissa\\Exception\\', $definesList->ROOT.'neptune/classes/internal/vendor/Larissa/Exception');

// Support Classes Autoload
$loader->addNamespace('Sup\\', $definesList->ROOT.'neptune/classes/internal/vendor/Supports');

// Database Classes Autoloads
$loader->addNamespace('Database\\DB\\', $definesList->ROOT.'neptune/classes/internal/vendor/Database/DB');

// FileSystem Classes Autoload
$loader->addNamespace('FileSystem\\', $definesList->ROOT.'neptune/classes/internal/vendor/FileSystem');

// Third Party Classes Autoload
$loader->addNamespace('Psr\\Log\\', $definesList->ROOT.'neptune/classes/internal/thirdparty/php-fig/log');

// Register Autoload
$loader->register();

// Set Class Privileges
PSR4::classPrivilege('Larissa\Run', 'LarissaRun');
PSR4::classPrivilege('Larissa\Handler\PrettyPageHandler', 'LarissaPrettyPageHandler');
PSR4::classPrivilege('Database\DB\DB', 'DB');
PSR4::classPrivilege('FileSystem\File', 'File');
PSR4::classPrivilege('FileSystem\Folder', 'Folder');

/*
 * Larissa Error Handler için Whoops Error Handler kullanılmıştır.
 * Kodları Neptune Framework'e göre yazıldığından dolayı ismi
 * Neptune uydularından Larissa adını almıştır. Ama tüm kodlar
 * Whoops Error Handler'a aittir.
 *
 * https://github.com/filp/whoops
*/

// Run Larissa Error Handler
$larissa = new LarissaRun;
$larissa->pushHandler(new LarissaPrettyPageHandler);
$larissa->register();


DB::connectDatabase([
  'host' => '127.0.0.1',
  'username' => 'root',
  'password' => '',
  'database' => 'pixdus',
	'charset' => 'utf8',
]);
