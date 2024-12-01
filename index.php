<?php

namespace JR;

use Kirby\Cms\App as Kirby;

require_once __DIR__ . '/media.class.php';
require_once __DIR__ . '/class.php';

function staticSiteGenerate ($kirby) {
  $outputFolder = $kirby->option('jr.static_site_generator.output_folder', './static');
  $baseUrl = $kirby->option('jr.static_site_generator.base_url', '/');
  $preserve = $kirby->option('jr.static_site_generator.preserve', []);
  $skipMedia = $kirby->option('jr.static_site_generator.skip_media', false);
  $skipTemplates = array_diff($kirby->option('jr.static_site_generator.skip_templates', []), ['home']);
  $customRoutes = $kirby->option('jr.static_site_generator.custom_routes', []);
  $customFilters = $kirby->option('jr.static_site_generator.custom_filters', []);
  $ignoreUntranslatedPages = $kirby->option('jr.static_site_generator.ignore_untranslated_pages', false);
  $indexFileName = $kirby->option('jr.static_site_generator.index_file_name', 'index.html');
  if (!empty($skipTemplates)) {
      array_push($customFilters, ['intendedTemplate', 'not in', $skipTemplates]);
  }

  $pages = $kirby->site()->index();
  foreach ($customFilters as $filter) {
      $pages = $pages->filterBy(...$filter);
  }

  $staticSiteGenerator = new StaticSiteGenerator($kirby, null, $pages);
  $staticSiteGenerator->skipMedia($skipMedia);
  $staticSiteGenerator->setCustomRoutes($customRoutes);
  $staticSiteGenerator->setIgnoreUntranslatedPages($ignoreUntranslatedPages);
  $staticSiteGenerator->setIndexFileName($indexFileName);
  return $staticSiteGenerator->generate($outputFolder, $baseUrl, $preserve);
}

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
                $list = staticSiteGenerate($kirby);
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
        $list = staticSiteGenerate($cli->kirby());
        $count = count($list);
        
        $cli->success('Static site generated. File count ' . $count);
      }
    ]
  ]
]);
