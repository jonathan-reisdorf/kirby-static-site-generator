<?php

namespace JR;

use Kirby\Cms\App as Kirby;

require_once __DIR__ . '/media.class.php';
require_once __DIR__ . '/class.php';

Kirby::plugin('jr/static-site-generator', [
  'api' => [
    'routes' => function ($kirby) {
        $endpoint = $kirby->option('jr.static_site_generator.endpoint');
        if (!$endpoint) {
            return [];
        }

        return [
          [
            'pattern' => $endpoint,
            'action' => function () use ($kirby) {
                $list = StaticSiteGenerator::generateFromConfig($kirby);
                $count = count($list);
                return ['success' => true, 'files' => $list, 'message' => "$count files generated / copied"];
            },
            'method' => 'POST'
          ]
        ];
    }
  ],
  'fields' => [
    'staticSiteGenerator' => [
      'props' => [
        'endpoint' => function () {
          return $this->kirby()->option('jr.static_site_generator.endpoint');
        }
      ]
    ]
  ],
  'commands' => [
    'ssg:generate' => [
      'description' => 'Generate Static Site',
      'args' => [],
      'command' => function($cli) {
        $list = StaticSiteGenerator::generateFromConfig($cli->kirby());
        $count = count($list);

        $cli->success("Static site generated. $count files generated / copied");
      }
    ]
  ]
]);
