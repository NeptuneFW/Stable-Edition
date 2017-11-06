<?php

namespace Larissa\Handler;

use Larissa\Exception\Inspector;
use Larissa\RunInterface;

interface HandlerInterface
{
  public function handle();
  public function setRun(RunInterface $run);
  public function setException($exception);
  public function setInspector(Inspector $inspector);
}
