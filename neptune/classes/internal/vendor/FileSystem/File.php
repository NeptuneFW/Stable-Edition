<?php

namespace FileSystem;

use finfo;

class File
{
  protected $folder;
  protected $name;
  protected $info = [];
  protected $handle;
  protected $lock;
  protected $path;

  public function __construct($path, $create = false, $mode = 0755)
  {
    $this->folder = new Folder(dirname($path), $create, $mode);

    if ( ! is_dir($path) ) {
      $this->name = basename($path);
    }

    $this->pwd();
    $create && !$this->exists() && $this->safe($path) && $this->create();
  }
  public function __destruct()
  {
    $this->close();
  }
  public function create()
  {
    $dir = $this->Folder->pwd();

    if ( is_dir($dir) && is_writable($dir) && ! $this->exists() ) {
      if (touch($this->path)) {
        return true;
      }
    }

    return false;
  }
  public function open($mode = 'r', $force = false)
  {
    if ( ! $force && is_resource($this->handle) ) {
      return true;
    }
    if ($this->exists() === false && $this->create() === false) {
      return false;
    }

    $this->handle = fopen($this->path, $mode);

    return is_resource($this->handle);
  }
  public function read($bytes = false, $mode = 'rb', $force = false)
  {
    if ($bytes === false && $this->lock === null) {
      return file_get_contents($this->path);
    }
    if ($this->open($mode, $force) === false) {
      return false;
    }
    if ($this->lock !== null && flock($this->handle, LOCK_SH) === false) {
      return false;
    }
    if (is_int($bytes)) {
      return fread($this->handle, $bytes);
    }

    $data = '';

    while ( ! feof($this->handle) ) {
      $data .= fgets($this->handle, 4096);
    }
    if ($this->lock !== null) {
      flock($this->handle, LOCK_UN);
    }
    if ($bytes === false) {
      $this->close();
    }

    return trim($data);
  }
  public function offset($offset = false, $seek = SEEK_SET)
  {
    if ($offset === false) {
      if (is_resource($this->handle)) {
        return ftell($this->handle);
      }
    }else if ($this->open() === true) {
      return fseek($this->handle, $offset, $seek) === 0;
    }

    return false;
  }
  public static function prepare($data, $forceWindows = false)
  {
    $lineBreak = "\n";
    if (DS === '\\' || $forceWindows === true) {
      $lineBreak = "\r\n";
    }

    return strtr($data, [
      "\r\n" => $lineBreak,
      "\n" => $lineBreak,
      "\r" => $lineBreak
    ]);
  }
  public function write($data, $mode = 'w', $force = false)
  {
    $success = false;

    if ($this->open($mode, $force) === true) {
      if ($this->lock !== null && flock($this->handle, LOCK_EX) === false) {
        return false;
      }
      if (fwrite($this->handle, $data) !== false) {
        $success = true;
      }
      if ($this->lock !== null) {
        flock($this->handle, LOCK_UN);
      }
    }

    return $success;
  }
  public function append($data, $force = false)
  {
    return $this->write($data, 'a', $force);
  }
  public function close()
  {
    if ( ! is_resource($this->handle) ) {
      return true;
    }

    return fclose($this->handle);
  }
  public function delete()
  {
    if (is_resource($this->handle)) {
      fclose($this->handle);
      $this->handle = null;
    }
    if ($this->exists()) {
      return unlink($this->path);
    }

    return false;
  }
  public function info()
  {
    if ( ! $this->info ) {
      $this->info = pathinfo($this->path);
    }
    if ( ! isset($this->info['filename']) ) {
      $this->info['filename'] = $this->name();
    }
    if ( ! isset($this->info['filesize']) ) {
      $this->info['filesize'] = $this->size();
    }
    if ( ! isset($this->info['mime']) ) {
      $this->info['mime'] = $this->mime();
    }

    return $this->info;
  }
  public function ext()
  {
    if ( ! $this->info ) {
      $this->info();
    }
    if (isset($this->info['extension'])) {
      return $this->info['extension'];
    }

    return false;
  }
  public function name()
  {
    if ( ! $this->info ) {
      $this->info();
    }
    if (isset($this->info['extension'])) {
      return basename($this->name, '.' . $this->info['extension']);
    }
    if ($this->name) {
      return $this->name;
    }

    return false;
  }
  public function safe($name = null, $ext = null)
  {
    if ( ! $name ) {
      $name = $this->name;
    }
    if ( ! $ext ) {
      $ext = $this->ext();
    }

    return preg_replace('/(?:[^\w\.-]+)/', '_', basename($name, $ext));
  }
  public function md5($maxsize = 5)
  {
    if ($maxsize === true) {
      return md5_file($this->path);
    }

    $size = $this->size();

    if ($size && $size < ($maxsize * 1024) * 1024) {
      return md5_file($this->path);
    }

    return false;
  }
  public function pwd()
  {
    if ($this->path === null) {
      $dir = $this->Folder->pwd();

      if (is_dir($dir)) {
        $this->path = $this->Folder->slashTerm($dir).$this->name;
      }
    }

    return $this->path;
  }
  public function exists()
  {
    $this->clearStatCache();

    return (file_exists($this->path) && is_file($this->path));
  }
  public function perms()
  {
    if ($this->exists()) {
      return substr(sprintf('%o', fileperms($this->path)), -4);
    }

    return false;
  }
  public function size()
  {
    if ($this->exists()) {
      return filesize($this->path);
    }

    return false;
  }
  public function writable()
  {
    return is_writable($this->path);
  }
  public function executable()
  {
    return is_executable($this->path);
  }
  public function readable()
  {
    return is_readable($this->path);
  }
  public function owner()
  {
    if ($this->exists()) {
      return fileowner($this->path);
    }

    return false;
  }
  public function group()
  {
    if ($this->exists()) {
      return filegroup($this->path);
    }

    return false;
  }
  public function lastAccess()
  {
    if ($this->exists()) {
      return fileatime($this->path);
    }

    return false;
  }
  public function lastChange()
  {
    if ($this->exists()) {
      return filemtime($this->path);
    }

    return false;
  }
  public function folder()
  {
    return $this->Folder;
  }
  public function copy($dest, $overwrite = true)
  {
    if ( ! $this->exists() || is_file($dest) && ! $overwrite ) {
      return false;
    }

    return copy($this->path, $dest);
  }
  public function mime()
  {
    if ( ! $this->exists() ) {
      return false;
    }
    if (class_exists('finfo')) {
      $finfo = new finfo(FILEINFO_MIME);
      $type = $finfo->file($this->pwd());

      if ( ! $type ) {
        return false;
      }

      list($type) = explode(';', $type);

      return $type;
    }
    if (function_exists('mime_content_type')) {
      return mime_content_type($this->pwd());
    }

    return false;
  }
  public function clearStatCache($all = false)
  {
    if ($all === false) {
      clearstatcache(true, $this->path);
    }

    clearstatcache();
  }
  public function replaceText($search, $replace)
  {
    if ( ! $this->open('r+') ) {
      return false;
    }
    if ($this->lock !== null && flock($this->handle, LOCK_EX) === false) {
      return false;
    }

    $replaced = $this->write(str_replace($search, $replace, $this->read()), 'w', true);

    if ($this->lock !== null) {
      flock($this->handle, LOCK_UN);
    }

    $this->close();

    return $replaced;
  }
}
