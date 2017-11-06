<?php

namespace FileSystem;

use Exception;
use DirectoryIterator;

class Folder
{
  const MERGE = 'merge';
  const OVERWRITE = 'overwrite';
  const SKIP = 'skip';
  const SORT_NAME = 'name';
  const SORT_TIME = 'time';

  public $path;
  public $sort = false;
  public $mode = 0755;
  protected $_fsorts = [
    self::SORT_NAME => 'getPathname',
    self::SORT_TIME => 'getCTime',
  ];
  protected $_messages = [];
  protected $_errors = [];
  protected $_directories;
  protected $_files;

  public function __construct($path = null, $create = false, $mode = false)
  {
    if (empty($path)) {
      $path = TMP;
    }
    if ($mode) {
      $this->mode = $mode;
    }
    if ( ! file_exists($path) && $create === true ) {
      $this->create($path, $this->mode);
    }
    if ( ! Folder::isAbsolute($path) ) {
        $path = realpath($path);
    }
    if ( ! empty($path) ) {
      $this->cd($path);
    }
  }
  public function pwd()
  {
    return $this->path;
  }
  public function cd($path)
  {
    $path = $this->realpath($path);

    if (is_dir($path)) {
      return $this->path = $path;
    }

    return false;
  }
  public function read($sort = self::SORT_NAME, $exceptions = false, $fullPath = false)
  {
    $dirs = $files = [];

    if ( ! $this->pwd() ) {
      return [$dirs, $files];
    }
    if (is_array($exceptions)) {
      $exceptions = array_flip($exceptions);
    }

    $skipHidden = isset($exceptions['.']) || $exceptions === true;

    try {
      $iterator = new DirectoryIterator($this->path);
    }catch (Exception $e) {
      return [$dirs, $files];
    }

    if ( ! is_bool($sort) && isset($this->_fsorts[$sort]) ) {
      $methodName = $this->_fsorts[$sort];
    }else {
      $methodName = $this->_fsorts[self::SORT_NAME];
    }
    foreach ($iterator as $item) {
      if ($item->isDot()) {
        continue;
      }

      $name = $item->getFilename();

      if ($skipHidden && $name[0] === '.' || isset($exceptions[$name])) {
        continue;
      }
      if ($fullPath) {
        $name = $item->getPathname();
      }
      if ($item->isDir()) {
        $dirs[$item->{$methodName}()][] = $name;
      }else {
        $files[$item->{$methodName}()][] = $name;
      }
    }
    if ($sort || $this->sort) {
      ksort($dirs);
      ksort($files);
    }
    if ($dirs) {
      $dirs = array_merge(...array_values($dirs));
    }
    if ($files) {
      $files = array_merge(...array_values($files));
    }

    return [$dirs, $files];
  }
  public function find($regexpPattern = '.*', $sort = false)
  {
    list(, $files) = $this->read($sort);

    return array_values(preg_grep('/^'.$regexpPattern.'$/i', $files));
  }
  public function findRecursive($pattern = '.*', $sort = false)
  {
    if ( ! $this->pwd() ) {
      return [];
    }

    $startsOn = $this->path;
    $out = $this->_findRecursive($pattern, $sort);
    $this->cd($startsOn);

    return $out;
  }
  protected function _findRecursive($pattern, $sort = false)
  {
    list($dirs, $files) = $this->read($sort);
    $found = [];

    foreach ($files as $file) {
      if (preg_match('/^'.$pattern.'$/i', $file)) {
        $found[] = Folder::addPathElement($this->path, $file);
      }
    }

    $start = $this->path;

    foreach ($dirs as $dir) {
      $this->cd(Folder::addPathElement($start, $dir));
      $found = array_merge($found, $this->findRecursive($pattern, $sort));
    }

    return $found;
  }
  public static function isWindowsPath($path)
  {
    return (preg_match('/^[A-Z]:\\\\/i', $path) || substr($path, 0, 2) === '\\\\');
  }
  public static function isAbsolute($path)
  {
    if (empty($path)) {
      return false;
    }

    return $path[0] === '/' || preg_match('/^[A-Z]:\\\\/i', $path) || substr($path, 0, 2) === '\\\\' || self::isRegisteredStreamWrapper($path);
  }
  public static function isRegisteredStreamWrapper($path)
  {
    return preg_match('/^[A-Z]+(?=:\/\/)/i', $path, $matches) && in_array($matches[0], stream_get_wrappers());
  }
  public static function normalizePath($path)
  {
    return Folder::correctSlashFor($path);
  }
  public static function correctSlashFor($path)
  {
    return Folder::isWindowsPath($path) ? '\\' : '/';
  }
  public static function slashTerm($path)
  {
    if (Folder::isSlashTerm($path)) {
      return $path;
    }

    return $path.Folder::correctSlashFor($path);
  }
  public static function addPathElement($path, $element)
  {
    $element = (array)$element;
    array_unshift($element, rtrim($path, DS));

    return implode(DS, $element);
  }
  public function inNeptunePath($path = '')
  {
    $dir = substr(Folder::slashTerm(ROOT), 0, -1);
    $newdir = $dir.$path;

    return $this->inPath($newdir);
  }
  public function inPath($path, $reverse = false)
  {
    if ( ! Folder::isAbsolute($path) ) {
      die('The $path argument is expected to be an absolute path.');
    }

    $dir = Folder::slashTerm($path);
    $current = Folder::slashTerm($this->pwd());

    if ( ! $reverse ) {
      $return = preg_match('/^'.preg_quote($dir, '/').'(.*)/', $current);
    }else {
      $return = preg_match('/^'.preg_quote($current, '/').'(.*)/', $dir);
    }

    return (bool)$return;
  }
  public function chmod($path, $mode = false, $recursive = true, array $exceptions = [])
  {
    if ( ! $mode ) {
      $mode = $this->mode;
    }
    if ($recursive === false && is_dir($path)) {
      if (@chmod($path, intval($mode, 8))) {
        $this->_messages[] = sprintf('%s changed to %s', $path, $mode);

        return true;
      }

      $this->_errors[] = sprintf('%s NOT changed to %s', $path, $mode);

      return false;
    }
      if (is_dir($path)) {
          $paths = $this->tree($path);
          foreach ($paths as $type) {
              foreach ($type as $fullpath) {
                  $check = explode(DIRECTORY_SEPARATOR, $fullpath);
                  $count = count($check);
                  if (in_array($check[$count - 1], $exceptions)) {
                      continue;
                  }
                  //@codingStandardsIgnoreStart
                  if (@chmod($fullpath, intval($mode, 8))) {
                      //@codingStandardsIgnoreEnd
                      $this->_messages[] = sprintf('%s changed to %s', $fullpath, $mode);
                  } else {
                      $this->_errors[] = sprintf('%s NOT changed to %s', $fullpath, $mode);
                  }
              }
          }
          if (empty($this->_errors)) {
              return true;
          }
      }
      return false;
  }
  public function subdirectories($path = null, $fullPath = true)
  {
    if ( ! $path ) {
      $path = $this->path;
    }

    $subdirectories = [];

    try {
      $iterator = new DirectoryIterator($path);
    }catch (Exception $e) {
      return [];
    }

    foreach ($iterator as $item) {
      if ( ! $item->isDir() || $item->isDot() ) {
        continue;
      }

      $subdirectories[] = $fullPath ? $item->getRealPath() : $item->getFilename();
    }

    return $subdirectories;
  }
  public function tree($path = null, $exceptions = false, $type = null)
  {
    if ( ! $path ) {
      $path = $this->path;
    }

    $files = [];
    $directories = [$path];

    if (is_array($exceptions)) {
      $exceptions = array_flip($exceptions);
    }

    $skipHidden = false;

    if ($exceptions === true) {
      $skipHidden = true;
    }else if (isset($exceptions['.'])) {
      $skipHidden = true;
      unset($exceptions['.']);
    }

    try {
      $directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::KEY_AS_PATHNAME | RecursiveDirectoryIterator::CURRENT_AS_SELF);
      $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
    }catch (Exception $e) {
      if ($type === null) {
        return [[], []];
      }

      return [];
    }

    foreach ($iterator as $itemPath => $fsIterator) {
      if ($skipHidden) {
        $subPathName = $fsIterator->getSubPathname();
        if ($subPathName{0} === '.' || strpos($subPathName, DS.'.') !== false) {
          continue;
        }
      }

      $item = $fsIterator->current();

      if ( ! empty($exceptions) && isset($exceptions[$item->getFilename()]) ) {
        continue;
      }
      if ($item->isFile()) {
        $files[] = $itemPath;
      }else if ($item->isDir() && !$item->isDot()) {
        $directories[] = $itemPath;
      }
    }
    if ($type === null) {
      return [$directories, $files];
    }
    if ($type === 'dir') {
      return $directories;
    }

    return $files;
  }
  public function create($pathname, $mode = false)
  {
    if (is_dir($pathname) || empty($pathname)) {
      return true;
    }
    if ( ! self::isAbsolute($pathname) ) {
      $pathname = self::addPathElement($this->pwd(), $pathname);
    }
    if ( ! $mode ) {
      $mode = $this->mode;
    }
    if (is_file($pathname)) {
      $this->_errors[] = sprintf('%s is a file', $pathname);

      return false;
    }

    $pathname = rtrim($pathname, DS);
    $nextPathname = substr($pathname, 0, strrpos($pathname, DS));

    if ($this->create($nextPathname, $mode)) {
      if (!file_exists($pathname)) {
        $old = umask(0);

        if (mkdir($pathname, $mode, true)) {
          umask($old);
          $this->_messages[] = sprintf('%s created', $pathname);

          return true;
        }

        umask($old);
        $this->_errors[] = sprintf('%s NOT created', $pathname);

        return false;
      }
    }

    return false;
  }
  public function dirsize()
  {
    $size = 0;
    $directory = Folder::slashTerm($this->path);
    $stack = [$directory];
    $count = count($stack);

    for ($i = 0, $j = $count; $i < $j; ++$i) {
      if (is_file($stack[$i])) {
        $size += filesize($stack[$i]);
      }else if (is_dir($stack[$i])) {
        $dir = dir($stack[$i]);

        if ($dir) {
          while (($entry = $dir->read()) !== false) {
            if ($entry === '.' || $entry === '..') {
              continue;
            }

            $add = $stack[$i].$entry;

            if (is_dir($stack[$i].$entry)) {
              $add = Folder::slashTerm($add);
            }

            $stack[] = $add;
          }

          $dir->close();
        }
      }

      $j = count($stack);
    }

    return $size;
  }
  public function delete($path = null)
  {
    if ( ! $path ) {
      $path = $this->pwd();
    }
    if ( ! $path ) {
      return false;
    }

    $path = Folder::slashTerm($path);

    if (is_dir($path)) {
      try {
        $directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::CURRENT_AS_SELF);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
      }catch (Exception $e) {
        return false;
      }

      foreach ($iterator as $item) {
        $filePath = $item->getPathname();
        if ($item->isFile() || $item->isLink()) {
          if (@unlink($filePath)) {
            $this->_messages[] = sprintf('%s removed', $filePath);
          }else {
            $this->_errors[] = sprintf('%s NOT removed', $filePath);
          }
        }else if ($item->isDir() && !$item->isDot()) {
          if (@rmdir($filePath)) {
            $this->_messages[] = sprintf('%s removed', $filePath);
          }else {
            $this->_errors[] = sprintf('%s NOT removed', $filePath);

            return false;
          }
        }
      }

      $path = rtrim($path, DS);

      if (@rmdir($path)) {
        $this->_messages[] = sprintf('%s removed', $path);
      }else {
        $this->_errors[] = sprintf('%s NOT removed', $path);

        return false;
      }
    }

    return true;
  }
  public function copy($options)
  {
    if ( ! $this->pwd() ) {
      return false;
    }

    $to = null;

    if (is_string($options)) {
      $to = $options;
      $options = [];
    }
    $options += [
      'to' => $to,
      'from' => $this->path,
      'mode' => $this->mode,
      'skip' => [],
      'scheme' => Folder::MERGE,
      'recursive' => true,
    ];

    $fromDir = $options['from'];
    $toDir = $options['to'];
    $mode = $options['mode'];

    if ( ! $this->cd($fromDir) ) {
      $this->_errors[] = sprintf('%s not found', $fromDir);

      return false;
    }
    if ( ! is_dir($toDir) ) {
      $this->create($toDir, $mode);
    }
    if ( ! is_writable($toDir) ) {
      $this->_errors[] = sprintf('%s not writable', $toDir);

      return false;
    }

    $exceptions = array_merge(['.', '..', '.svn'], $options['skip']);

    if ($handle = @opendir($fromDir)) {
      while (($item = readdir($handle)) !== false) {
        $to = Folder::addPathElement($toDir, $item);

        if ( ($options['scheme'] != Folder::SKIP || ! is_dir($to)) && ! in_array($item, $exceptions) ) {
          $from = Folder::addPathElement($fromDir, $item);

          if (is_file($from) && (!is_file($to) || $options['scheme'] != Folder::SKIP)) {
              if (copy($from, $to)) {
                chmod($to, intval($mode, 8));
                touch($to, filemtime($from));
                $this->_messages[] = sprintf('%s copied to %s', $from, $to);
              }else {
                $this->_errors[] = sprintf('%s NOT copied to %s', $from, $to);
              }
          }
          if (is_dir($from) && file_exists($to) && $options['scheme'] === Folder::OVERWRITE) {
            $this->delete($to);
          }
          if (is_dir($from) && $options['recursive'] === false) {
            continue;
          }
          if (is_dir($from) && !file_exists($to)) {
            $old = umask(0);

            if (mkdir($to, $mode, true)) {
              umask($old);
              $old = umask(0);
              chmod($to, $mode);
              umask($old);
              $this->_messages[] = sprintf('%s created', $to);
              $options = ['to' => $to, 'from' => $from] + $options;
              $this->copy($options);
            }else {
              $this->_errors[] = sprintf('%s not created', $to);
            }
          }else if (is_dir($from) && $options['scheme'] === Folder::MERGE) {
            $options = ['to' => $to, 'from' => $from] + $options;
            $this->copy($options);
          }
        }
      }

      closedir($handle);
    }else {
      return false;
    }

    return empty($this->_errors);
  }
  public function move($options)
  {
    $to = null;

    if (is_string($options)) {
      $to = $options;
      $options = (array)$options;
    }

    $options += ['to' => $to, 'from' => $this->path, 'mode' => $this->mode, 'skip' => [], 'recursive' => true];

    if ($this->copy($options) && $this->delete($options['from'])) {
      return (bool)$this->cd($options['to']);
    }

    return false;
  }
  public function messages($reset = true)
  {
    $messages = $this->_messages;

    if ($reset) {
      $this->_messages = [];
    }

    return $messages;
  }
  public function errors($reset = true)
  {
    $errors = $this->_errors;

    if ($reset) {
      $this->_errors = [];
    }

    return $errors;
  }
  public function realpath($path)
  {
    if (strpos($path, '..') === false) {
      if ( ! Folder::isAbsolute($path) ) {
        $path = Folder::addPathElement($this->path, $path);
      }

      return $path;
    }

    $path = str_replace('/', DS, trim($path));
    $parts = explode(DS, $path);
    $newparts = [];
    $newpath = '';

    if ($path[0] === DS) {
      $newpath = DS;
    }
    while (($part = array_shift($parts)) !== null) {
      if ($part === '.' || $part === '') {
        continue;
      }
      if ($part === '..') {
        if ( ! empty($newparts) ) {
          array_pop($newparts);

          continue;
        }

        return false;
      }

      $newparts[] = $part;
    }

    $newpath .= implode(DIRECTORY_SEPARATOR, $newparts);

    return Folder::slashTerm($newpath);
  }
  public static function isSlashTerm($path)
  {
    $lastChar = $path[strlen($path) - 1];

    return $lastChar === '/' || $lastChar === '\\';
  }
}
