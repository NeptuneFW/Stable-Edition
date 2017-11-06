<?php

namespace Larissa\Handler;

use Exception;
use ErrorException;
use InvalidArgumentException;
use RuntimeException;
use Traversable;
use UnexpectedValueException;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Larissa\Exception\Formatter;
use Larissa\Util\Misc;
use Larissa\Util\TemplateHelper;

class PrettyPageHandler extends Handler
{
  private $searchPaths = [];
  private $resourceCache = [];
  private $customCss = null;
  private $extraTables = [];
  private $handleUnconditionally = false;
  private $pageTitle = 'Whoops! There was an error.';
  private $applicationPaths;
  private $blacklist = [
    '_GET' => [],
    '_POST' => [],
    '_FILES' => [],
    '_COOKIE' => [],
    '_SESSION' => [],
    '_SERVER' => [],
    '_ENV' => [],
  ];
  protected $editor;
  protected $editors = [
    'sublime'  => 'subl://open?url=file://%file&line=%line',
    'textmate' => 'txmt://open?url=file://%file&line=%line',
    'emacs'    => 'emacs://open?url=file://%file&line=%line',
    'macvim'   => 'mvim://open/?url=file://%file&line=%line',
    'phpstorm' => 'phpstorm://open?file=%file&line=%line',
    'idea'     => 'idea://open?file=%file&line=%line',
    'vscode'   => 'vscode://file/%file:%line',
  ];
  private $templateHelper;

  public function __construct()
  {
    if (ini_get('xdebug.file_link_format') || extension_loaded('xdebug')) {
      $this->editors['xdebug'] = function ($file, $line) {
        return str_replace(['%f', '%l'], [$file, $line], ini_get('xdebug.file_link_format'));
      };
    }

    $this->searchPaths[] = __DIR__.'/../Resources';
    $this->blacklist('_SERVER', 'PHP_AUTH_PW');
    $this->templateHelper = new TemplateHelper();

    if (class_exists('Symfony\Component\VarDumper\Cloner\VarCloner')) {
      $cloner = new VarCloner();

      $cloner->addCasters(['*' => function ($obj, $a, $stub, $isNested, $filter = 0) {
        $class = $stub->class;
        $classes = [$class => $class] + class_parents($class) + class_implements($class);

        foreach ($classes as $class) {
          if (isset(AbstractCloner::$defaultCasters[$class])) {
            return $a;
          }
        }

        return [];
      }]);

      $this->templateHelper->setCloner($cloner);
    }
  }
  public function handle()
  {
    if ( ! $this->handleUnconditionally() ) {
      if (PHP_SAPI === 'cli') {
        if (isset($_ENV['whoops-test'])) {
          throw new Exception('Use handleUnconditionally instead of whoops-test environment variable');
        }
        return Handler::DONE;
      }
    }

    $templateFile = $this->getResource('views/layout.html.php');
    $cssFile = $this->getResource('css/larissa.base.css');
    $zeptoFile = $this->getResource('js/zepto.min.js');
    $prettifyFile = $this->getResource('js/prettify.min.js');
    $clipboard = $this->getResource('js/clipboard.min.js');
    $jsFile = $this->getResource('js/larissa.base.js');

    if ($this->customCss) {
      $customCssFile = $this->getResource($this->customCss);
    }

      $inspector = $this->getInspector();
      $frames = $this->getExceptionFrames();
      $code = $this->getExceptionCode();
      $vars = [
        'page_title' => $this->getPageTitle(),
        'stylesheet' => file_get_contents($cssFile),
        'zepto' => file_get_contents($zeptoFile),
        'prettify' => file_get_contents($prettifyFile),
        'clipboard' => file_get_contents($clipboard),
        'javascript' => file_get_contents($jsFile),
        'header' => $this->getResource('views/header.html.php'),
        'header_outer' => $this->getResource('views/header_outer.html.php'),
        'frame_list' => $this->getResource('views/frame_list.html.php'),
        'frames_description' => $this->getResource('views/frames_description.html.php'),
        'frames_container' => $this->getResource('views/frames_container.html.php'),
        'panel_details' => $this->getResource('views/panel_details.html.php'),
        'panel_details_outer' => $this->getResource('views/panel_details_outer.html.php'),
        'panel_left' => $this->getResource('views/panel_left.html.php'),
        'panel_left_outer' => $this->getResource('views/panel_left_outer.html.php'),
        'frame_code' => $this->getResource('views/frame_code.html.php'),
        'env_details' => $this->getResource('views/env_details.html.php'),
        'title' => $this->getPageTitle(),
        'name' => explode('\\', $inspector->getExceptionName()),
        'message' => $inspector->getExceptionMessage(),
        'docref_url' => $inspector->getExceptionDocrefUrl(),
        'code' => $code,
        'plain_exception' => Formatter::formatExceptionPlain($inspector),
        'frames' => $frames,
        'has_frames' => !!count($frames),
        'handler' => $this,
        'handlers' => $this->getRun()->getHandlers(),
        'active_frames_tab' => count($frames) && $frames->offsetGet(0)->isApplication() ? 'application' : 'all',
        'has_frames_tabs' => $this->getApplicationPaths(),
        'tables' => [
          'GET Data' => $this->masked($_GET, '_GET'),
          'POST Data' => $this->masked($_POST, '_POST'),
          'Files' => isset($_FILES) ? $this->masked($_FILES, '_FILES') : [],
          'Cookies' => $this->masked($_COOKIE, '_COOKIE'),
          'Session' => isset($_SESSION) ? $this->masked($_SESSION, '_SESSION') : [],
          'Server/Request Data' => $this->masked($_SERVER, '_SERVER'),
          'Environment Variables' => $this->masked($_ENV, '_ENV'),
        ],
      ];

      if (isset($customCssFile)) {
        $vars['stylesheet'] .= file_get_contents($customCssFile);
      }

      $extraTables = array_map(function ($table) use ($inspector) {
        return $table instanceof \Closure ? $table($inspector) : $table;
      }, $this->getDataTables());

      $vars['tables'] = array_merge($extraTables, $vars['tables']);
      $plainTextHandler = new PlainTextHandler();
      $plainTextHandler->setException($this->getException());
      $plainTextHandler->setInspector($this->getInspector());
      $vars['preface'] = "<!--\n\n\n" .  $this->templateHelper->escape($plainTextHandler->generateResponse()) . "\n\n\n\n\n\n\n\n\n\n\n-->";
      $this->templateHelper->setVariables($vars);
      $this->templateHelper->render($templateFile);

      return Handler::QUIT;
  }
  protected function getExceptionFrames()
  {
    $frames = $this->getInspector()->getFrames();

    if ($this->getApplicationPaths()) {
      foreach ($frames as $frame) {
        foreach ($this->getApplicationPaths() as $path) {
          if (strpos($frame->getFile(), $path) === 0) {
            $frame->setApplication(true);

            break;
          }
        }
      }
    }

    return $frames;
  }
  protected function getExceptionCode()
  {
    $exception = $this->getException();
    $code = $exception->getCode();

    if ($exception instanceof ErrorException) {
      $code = Misc::translateErrorCode($exception->getSeverity());
    }

    return (string) $code;
  }
  public function contentType()
  {
    return 'text/html';
  }
  public function addDataTable($label, array $data)
  {
    $this->extraTables[$label] = $data;
  }
  public function addDataTableCallback($label, $callback)
  {
    if ( ! is_callable($callback) ) {
        throw new InvalidArgumentException('Expecting callback argument to be callable');
    }
    $this->extraTables[$label] = function (\Larissa\Exception\Inspector $inspector = null) use ($callback) {
      try {
        $result = call_user_func($callback, $inspector);

        return is_array($result) || $result instanceof Traversable ? $result : [];
      }catch (Exception $e) {
        return [];
      }
    };
  }
  public function getDataTables($label = null)
  {
    if ($label !== null) {
      return isset($this->extraTables[$label]) ? $this->extraTables[$label] : [];
    }

    return $this->extraTables;
  }
  public function handleUnconditionally($value = null)
  {
    if (func_num_args() == 0) {
      return $this->handleUnconditionally;
    }

    $this->handleUnconditionally = (bool) $value;
  }
  public function addEditor($identifier, $resolver)
  {
    $this->editors[$identifier] = $resolver;
  }
  public function setEditor($editor)
  {
    if ( ! is_callable($editor) && ! isset($this->editors[$editor]) ) {
      throw new InvalidArgumentException('Unknown editor identifier: '.$editor.'. Known editors:'.implode(',', array_keys($this->editors)));
    }

    $this->editor = $editor;
  }
  public function getEditorHref($filePath, $line)
  {
    $editor = $this->getEditor($filePath, $line);
    if (empty($editor)) {
        return false;
    }
    if ( ! isset($editor['url']) || ! is_string($editor['url']) ) {
      throw new UnexpectedValueException(__METHOD__.' should always resolve to a string or a valid editor array; got something else instead.');
    }

    $editor['url'] = str_replace('%line', rawurlencode($line), $editor['url']);
    $editor['url'] = str_replace('%file', rawurlencode($filePath), $editor['url']);

    return $editor['url'];
  }
  public function getEditorAjax($filePath, $line)
  {
    $editor = $this->getEditor($filePath, $line);

    if ( ! isset($editor['ajax']) || ! is_bool($editor['ajax']) ) {
      throw new UnexpectedValueException(__METHOD__.' should always resolve to a bool; got something else instead.');
    }

    return $editor['ajax'];
  }
  protected function getEditor($filePath, $line)
  {
    if ( ! $this->editor || ( ! is_string($this->editor) && ! is_callable($this->editor) ) ) {
      return [];
    }
    if ( is_string($this->editor) && isset($this->editors[$this->editor]) && ! is_callable($this->editors[$this->editor]) ) {
      return [
        'ajax' => false,
        'url' => $this->editors[$this->editor],
      ];
    }
    if (is_callable($this->editor) || (isset($this->editors[$this->editor]) && is_callable($this->editors[$this->editor]))) {
      if (is_callable($this->editor)) {
        $callback = call_user_func($this->editor, $filePath, $line);
      }else {
        $callback = call_user_func($this->editors[$this->editor], $filePath, $line);
      }
      if (is_string($callback)) {
        return [
          'ajax' => false,
          'url' => $callback,
        ];
      }
      return [
        'ajax' => isset($callback['ajax']) ? $callback['ajax'] : false,
        'url' => isset($callback['url']) ? $callback['url'] : $callback,
      ];
    }

    return [];
  }
  public function setPageTitle($title)
  {
    $this->pageTitle = (string) $title;
  }
  public function getPageTitle()
  {
    return $this->pageTitle;
  }
  public function addResourcePath($path)
  {
    if ( ! is_dir($path) ) {
      throw new InvalidArgumentException('"'.$path.'" is not a valid directory');
    }

    array_unshift($this->searchPaths, $path);
  }
  public function addCustomCss($name)
  {
    $this->customCss = $name;
  }
  public function getResourcePaths()
  {
    return $this->searchPaths;
  }
  protected function getResource($resource)
  {
    if (isset($this->resourceCache[$resource])) {
      return $this->resourceCache[$resource];
    }
    foreach ($this->searchPaths as $path) {
      $fullPath = $path.'/'.$resource;

      if (is_file($fullPath)) {
        $this->resourceCache[$resource] = $fullPath;

        return $fullPath;
      }
    }

    throw new RuntimeException('Could not find resource "'.$resource.'" in any resource paths. (searched: '.join(', ', $this->searchPaths).')');
  }
  public function getResourcesPath()
  {
    $allPaths = $this->getResourcePaths();

    return end($allPaths) ?: null;
  }
  public function setResourcesPath($resourcesPath)
  {
    $this->addResourcePath($resourcesPath);
  }
  public function getApplicationPaths()
  {
    return $this->applicationPaths;
  }
  public function setApplicationPaths($applicationPaths)
  {
    $this->applicationPaths = $applicationPaths;
  }
  public function setApplicationRootPath($applicationRootPath)
  {
    $this->templateHelper->setApplicationRootPath($applicationRootPath);
  }
  public function blacklist($superGlobalName, $key)
  {
    $this->blacklist[$superGlobalName][] = $key;
  }
  private function masked(array $superGlobal, $superGlobalName)
  {
    $blacklisted = $this->blacklist[$superGlobalName];
    $values = $superGlobal;

    foreach ($blacklisted as $key) {
      if (isset($superGlobal[$key])) {
        $values[$key] = str_repeat('*', strlen($superGlobal[$key]));
      }
    }

    return $values;
  }
}
