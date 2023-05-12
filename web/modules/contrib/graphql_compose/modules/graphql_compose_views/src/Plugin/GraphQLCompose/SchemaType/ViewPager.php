<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType;

use Drupal\views\ViewExecutable;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 */
class ViewPager {

  /**
   * {@inheritdoc}
   *
   * Add dynamic view types that use View interface.
   */
  public static function getPagerTypes(ViewExecutable $view): array {
    return [];
  }

  /**
   * Check if display is paged.
   */
  public static function isPaged(ViewExecutable $view): bool {
    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();

    // Pagination enabled at a set limit.
    return in_array($display->getOption('pager')['type'] ?? '', [
      'full',
      'mini',
    ]);
  }

  /**
   * Dodgey callbacks for View.php.
   */
  public static function getPagerArgs(ViewExecutable $view): array {

    $args = [];

    if (!self::isPaged($view)) {
      return $args;
    }

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();

    $args['page'] = [
      'type' => Type::int(),
      'description' => (string) t('The page number to display.'),
      'defaultValue' => 0,
    ];

    $pager_options = $display->getOption('pager')['options'] ?? [];

    // ALlow setting items per page.
    if ($pager_options['expose']['items_per_page'] ?? FALSE) {
      $args['pageSize'] = [
        'type' => Type::int(),
        'description' => (string) t('@label. Allowed values are: @input.', [
          '@label' => $pager_options['expose']['items_per_page_label'],
          '@input' => $pager_options['expose']['items_per_page_options'],
        ]),
        'defaultValue' => $pager_options['items_per_page'] ?? 10,
      ];
    }

    if ($pager_options['expose']['offset'] ?? FALSE) {
      $args['offset'] = [
        'type' => Type::int(),
        'description' => (string) t('@label. number of items skipped from beginning of this view.', [
          '@label' => $pager_options['expose']['offset_label'],
        ]),
        'defaultValue' => $pager_options['offset'] ?? 0,
      ];
    }

    return $args;
  }

}
