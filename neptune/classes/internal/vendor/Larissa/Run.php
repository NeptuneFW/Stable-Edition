<?php

namespace Larissa;

use InvalidArgumentException;
use Larissa\Exception\ErrorException;
use Larissa\Exception\Inspector;
use Larissa\Handler\CallbackHandler;
use Larissa\Handler\Handler;
use Larissa\Handler\HandlerInterface;
use Larissa\Util\Misc;
use Larissa\Util\SystemFacade;

final class Run implements RunInterface
{
  private $isRegistered;
  private $allowQuit = true;
  private $sendOutput = true;
  private $sendHttpCode = 500;
  private $handlerStack = [];
  private $silencedPatterns = [];
  private $system;
  private $canThrowExceptions = true;

  public function __construct(SystemFacade $system = null)
  {
    $this->system = $system ?: new SystemFacade;
  }
  public function pushHandler($handler)
  {
    if (is_callable($handler)) {
      $handler = new CallbackHandler($handler);
    }
    if ( ! $handler instanceof HandlerInterface ) {
      throw new InvalidArgumentException('Argument to '.__METHOD__.' must be a callable, or instance of Whoops\\Handler\\HandlerInterface');
    }

    $this->handlerStack[] = $handler;

    return $this;
  }
  public function popHandler()
  {
    return array_pop($this->handlerStack);
  }
  public function getHandlers()
  {
    return $this->handlerStack;
  }
  public function clearHandlers()
  {
    $this->handlerStack = [];

    return $this;
  }
  private function getInspector($exception)
  {
    return new Inspector($exception);
  }
  public function register()
  {
    if ( ! $this->isRegistered ) {
      class_exists('\\Larissa\\Exception\\ErrorException');
      class_exists('\\Larissa\\Exception\\FrameCollection');
      class_exists('\\Larissa\\Exception\\Frame');
      class_exists('\\Larissa\\Exception\\Inspector');

      $this->system->setErrorHandler([$this, self::ERROR_HANDLER]);
      $this->system->setExceptionHandler([$this, self::EXCEPTION_HANDLER]);
      $this->system->registerShutdownFunction([$this, self::SHUTDOWN_HANDLER]);
      $this->isRegistered = true;
    }

    return $this;
  }
  public function unregister()
  {
    if ($this->isRegistered) {
      $this->system->restoreExceptionHandler();
      $this->system->restoreErrorHandler();
      $this->isRegistered = false;
    }

    return $this;
  }
  public function allowQuit($exit = null)
  {
    if (func_num_args() == 0) {
      return $this->allowQuit;
    }

    return $this->allowQuit = (bool) $exit;
  }
  public function silenceErrorsInPaths($patterns, $levels = 10240)
  {
    $this->silencedPatterns = array_merge($this->silencedPatterns, array_map(function ($pattern) use ($levels) {
      return [
        'pattern' => $pattern,
        'levels' => $levels,
      ];
    }, (array) $patterns));

    return $this;
  }
  public function getSilenceErrorsInPaths()
  {
    return $this->silencedPatterns;
  }
  public function sendHttpCode($code = null)
  {
    if (func_num_args() == 0) {
      return $this->sendHttpCode;
    }
    if ( ! $code ) {
      return $this->sendHttpCode = false;
    }
    if ($code === true) {
      $code = 500;
    }
    if ($code < 400 || 600 <= $code) {
      throw new InvalidArgumentException('Invalid status code "'.$code.'", must be 4xx or 5xx');
    }

    return $this->sendHttpCode = $code;
  }
  public function writeToOutput($send = null)
  {
    if (func_num_args() == 0) {
      return $this->sendOutput;
    }

    return $this->sendOutput = (bool) $send;
  }
  public function handleException($exception)
  {
    $inspector = $this->getInspector($exception);
    $this->system->startOutputBuffering();
    $handlerResponse = null;
    $handlerContentType = null;

    foreach (array_reverse($this->handlerStack) as $handler) {
      $handler->setRun($this);
      $handler->setInspector($inspector);
      $handler->setException($exception);
      $handlerResponse = $handler->handle($exception);
      $handlerContentType = method_exists($handler, 'contentType') ? $handler->contentType() : null;

      if (in_array($handlerResponse, [Handler::LAST_HANDLER, Handler::QUIT])) {
        break;
      }
    }

    $willQuit = $handlerResponse == Handler::QUIT && $this->allowQuit();
    $output = $this->system->cleanOutputBuffer();

    if ($this->writeToOutput()) {
      if ($willQuit) {
        while ($this->system->getOutputBufferLevel() > 0) {
            $this->system->endOutputBuffering();
        }
        if (Misc::canSendHeaders() && $handlerContentType) {
          header("Content-Type: {$handlerContentType}");
        }
      }

      $this->writeToOutputNow($output);
    }
    if ($willQuit) {
      $this->system->flushOutputBuffer();
      $this->system->stopExecution(1);
    }

    return $output;
  }
  public function handleError($level, $message, $file = null, $line = null)
  {
    if ($level & $this->system->getErrorReportingLevel()) {
      foreach ($this->silencedPatterns as $entry) {
        $pathMatches = (bool) preg_match($entry['pattern'], $file);
        $levelMatches = $level & $entry['levels'];

        if ($pathMatches && $levelMatches) {
          return true;
        }
      }

      $exception = new ErrorException($message, $level, $level, $file, $line);

      if ($this->canThrowExceptions) {
        throw $exception;
      }else {
        $this->handleException($exception);
      }

      return true;
    }

    return false;
  }
  public function handleShutdown()
  {
    $this->canThrowExceptions = false;
    $error = $this->system->getLastError();

    if ($error && Misc::isLevelFatal($error['type'])) {
      $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
    }
  }
  private function writeToOutputNow($output)
  {
    if ($this->sendHttpCode() && Misc::canSendHeaders()) {
      $this->system->setHttpResponseCode($this->sendHttpCode());
    }

    echo $output;

    return $this;
  }
}
