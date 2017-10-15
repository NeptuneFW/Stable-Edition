<?php

return [
  'psr4' => [
    'namespaces' => [
      'Controllers\\',
      'Models\\',
    ],
    'directories' => [
      'astronaut/applications/"current_application"/' => [
        'controllers',
        'models',
      ],
    ],
  ],
  'classmap' => [
    'neptune/"all"',
    [
      'test' => 'test',
      'falan-oyle-boyle' => 'falan'
    ],
  ],
];
