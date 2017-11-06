<?php

namespace Larissa\Handler;

use Larissa\Exception\Formatter;

class JsonResponseHandler extends Handler
{
  private $returnFrames = false;
  private $jsonApi = false;

  public function setJsonApi($jsonApi = false)
  {
    $this->jsonApi = (bool) $jsonApi;

    return $this;
  }
  public function addTraceToOutput($returnFrames = null)
  {
    if (func_num_args() == 0) {
      return $this->returnFrames;
    }

    $this->returnFrames = (bool) $returnFrames;

    return $this;
  }
  public function handle()
  {
    if ($this->jsonApi === true) {
      $response = [
        'errors' => [
          Formatter::formatExceptionAsDataArray($this->getInspector(), $this->addTraceToOutput()),
        ],
      ];
    }else {
      $response = [
        'error' => Formatter::formatExceptionAsDataArray($this->getInspector(), $this->addTraceToOutput()),
      ];
    }

    echo json_encode($response, defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0);

    return Handler::QUIT;
  }
  public function contentType()
  {
    return 'application/json';
  }
}
