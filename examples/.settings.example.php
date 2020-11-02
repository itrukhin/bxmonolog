<?php

return array (
  'exception_handling' =>
  array (
    'value' => 
    array (
      'debug' => ((bool) $_ENV['APP_DEBUG']),
      'handled_errors_types' => E_ALL & ~E_WARNING & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED,
      'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_COMPILE_WARNING & ~E_DEPRECATED,
      'ignore_silence' => false,
      'assertion_throws_exception' => true,
      'assertion_error_type' => 256,
      'log' => 
      array (
        'class_name' => '\App\Monolog\ExceptionHandlerLog',
      ),
    ),
    'readonly' => false,
  ),
);
