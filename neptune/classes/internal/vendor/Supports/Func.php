<?php

namespace Sup;

class Func
{
  public function callArray(callable $callback, array $params = [])
  {
    return call_user_func_array($callback, $params);
  }
  public function call(...$args)
  {
    return call_user_func(...$args);
  }
  public function staticCallArray(callable $callback, array $params = [])
  {
    return forward_static_call_array($callback, $params);
  }
  public function staticCall(...$args)
  {
    return forward_static_call(...$args);
  }
  public function shutdown(...$args)
  {
    return register_shutdown_function(...$args);
  }
  public function tick(...$args)
  {
    return register_tick_function(...$args);
  }
  public function untick(...$args)
  {
    return unregister_tick_function(...$args);
  }
  public function defined()
  {
    return get_defined_functions();
  }
}
