<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType\ViewPager;
use Drupal\views\ViewExecutable as ViewsViewExecutable;

/**
 * Load a Route or Redirect based on Path.
 *
 * @DataProducer(
 *   id = "views_executable",
 *   name = @Translation("Views Executable"),
 *   description = @Translation("A views executable."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Views executable")
 *   ),
 *   consumes = {
 *     "view_id" = @ContextDefinition("string",
 *       label = @Translation("View ID")
 *     ),
 *     "display_id" = @ContextDefinition("string",
 *       label = @Translation("View Display ID")
 *     ),
 *     "page" = @ContextDefinition("integer",
 *       label = @Translation("Page"),
 *       default_value = 0,
 *       required = FALSE
 *     ),
 *     "page_size" = @ContextDefinition("integer",
 *       label = @Translation("Items per page"),
 *       required = FALSE
 *     ),
 *     "offset" = @ContextDefinition("integer",
 *       label = @Translation("Result offset"),
 *       default_value = 0,
 *       required = FALSE
 *     ),
 *     "filter" = @ContextDefinition("any",
 *       label = @Translation("View filters"),
 *       required = FALSE
 *     ),
 *     "contextual_filter" = @ContextDefinition("any",
 *       label = @Translation("View contextual filters"),
 *       required = FALSE
 *     ),
 *     "sort_key" = @ContextDefinition("string",
 *       label = @Translation("Sort by key"),
 *       required = FALSE
 *     ),
 *     "sort_dir" = @ContextDefinition("string",
 *       label = @Translation("Sort direction"),
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class ViewsExecutable extends DataProducerPluginBase {

  /**
   * Resolver.
   */
  public function resolve(string $view_id, string $display_id, ?int $page, ?int $page_size, ?int $offset, ?array $filter, ?array $contextual_filter, ?string $sort_key, ?string $sort_dir, RefinableCacheableDependencyInterface $metadata): ?ViewsViewExecutable {

    /** @var \Drupal\views\ViewEntityInterface|null $view_entity */
    $view_entity = \Drupal::entityTypeManager()->getStorage('view')->load($view_id);
    $view = $view_entity->getExecutable();
    $view->setDisplay($display_id);

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();

    // Default exposed input.
    $exposed_input = $view->getExposedInput();

    $isPaged = ViewPager::isPaged($view);
    if ($isPaged) {
      $view->setCurrentPage($page ?? 0);

      if ($display->getOption('pager')['options']['expose']['offset'] ?? FALSE) {
        $view->setOffset($offset ?? 0);
      }

      // Limit to enabled config.
      $items_per_page = $display->getOption('pager')['options']['expose']['items_per_page'] ?? FALSE;
      $items_per_page_options = $display->getOption('pager')['options']['expose']['items_per_page_options'] ?? FALSE;

      if ($page_size && $items_per_page && $items_per_page_options) {
        $allowed = array_map('trim', explode(',', $items_per_page_options));
        if (in_array($page_size, $allowed)) {
          $view->setItemsPerPage($page_size);
        }
      }
    }

    // Input filters.
    $exposed_filters = array_filter(
      $display->getOption('filters') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    $exposed_filters = array_map(
      fn($filter) => $filter['expose']['identifier'],
      $exposed_filters
    );

    if ($filter && $exposed_filters) {
      foreach ($filter as $key => $value) {
        if (in_array($key, $exposed_filters)) {
          $exposed_input[$key] = is_bool($value) ? (string) intval($value) : $value;
        }
      }
    }

    // Sorts.
    $exposed_sorts = array_filter(
      $display->getOption('sorts') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    if ($exposed_sorts) {
      $sort_enums = $display->getGraphQlSortEnums();
      if (array_key_exists($sort_key, $sort_enums)) {
        $exposed_input['sort_by'] = $sort_enums[$sort_key]['value'];
      }
    }

    // Set sort order.
    $exposed_form = $display->getOption('exposed_form');
    if ($exposed_form['options']['expose_sort_order'] ?? FALSE) {
      $exposed_input['sort_order'] = $sort_dir === 'ASC' ? 'ASC' : 'DESC';
    }

    $view->setExposedInput($exposed_input);

    // Construct contextual filters.
    $context_args = [];
    $contextual_filters = $display->getOption('arguments') ?: [];

    if ($contextual_filter && $contextual_filters) {
      foreach ($contextual_filter as $key => $value) {
        if (isset($contextual_filters[$key])) {
          $context_args[$key] = is_bool($value) ? (string) intval($value) : $value;
        }
      }
    }

    $metadata->addCacheableDependency($view_entity);

    // Execute the view.
    $render_context = new RenderContext();

    $executed_view = \Drupal::service('renderer')->executeInRenderContext(
      $render_context,
      function () use ($view, $context_args) {
        $view->preExecute($context_args);
        $view->execute();
        return $view;
      }
    );

    if (!$render_context->isEmpty()) {
      $metadata->addCacheableDependency($render_context->pop());
    }

    return $executed_view;
  }

}
