<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\views\ViewExecutable as ViewsViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class ViewsExecutable extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->renderer = $container->get('renderer');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * Resolve a view executable.
   *
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The view display ID.
   * @param int|null $page
   *   The page number.
   * @param int|null $page_size
   *   The page size.
   * @param int|null $offset
   *   The page offset.
   * @param array|null $filter
   *   The filters to apply.
   * @param array|null $contextual_filter
   *   The contextual filters to apply.
   * @param string|null $sort_key
   *   The sort key.
   * @param string|null $sort_dir
   *   The sort direction (ASC/DESC).
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cache metadata.
   *
   * @return \Drupal\views\ViewExecutable|null
   *   The view executable.
   */
  public function resolve(string $view_id, string $display_id, ?int $page, ?int $page_size, ?int $offset, ?array $filter, ?array $contextual_filter, ?string $sort_key, ?string $sort_dir, RefinableCacheableDependencyInterface $metadata): ?ViewsViewExecutable {

    /** @var \Drupal\views\ViewEntityInterface|null $view_entity */
    $view_entity = $this->entityTypeManager->getStorage('view')->load($view_id);
    $view = $view_entity->getExecutable();
    $view->setDisplay($display_id);

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();

    // Default exposed input.
    $exposed_input = $view->getExposedInput();

    // Pagination enabled at a set limit.
    $is_paged = in_array($display->getOption('pager')['type'] ?? '', [
      'full',
      'mini',
    ]);

    if ($is_paged) {
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

    $filter_input = [];

    // Remap any incoming KeyValueInput pairs.
    foreach ($filter ?: [] as $key => $value) {
      if (is_array($value) && isset($value['key'], $value['value'])) {
        $filter_input[$value['key']] = $value['value'];
      }
      else {
        $filter_input[$key] = $value;
      }
    }

    // Exposed input filters.
    $exposed_filters = array_filter(
      $display->getOption('filters') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    $exposed_filters = array_map(
      fn($filter) => $filter['expose']['identifier'],
      $exposed_filters
    );

    // Only allow exposed filters in exposed_input.
    foreach ($filter_input ?: [] as $key => $value) {
      if (in_array($key, $exposed_filters)) {
        $exposed_input[$key] = is_bool($value) ? (string) intval($value) : $value;
      }
    }

    // Sorts.
    $exposed_sorts = array_filter(
      $display->getOption('sorts') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    if ($exposed_sorts && $sort_key) {
      $sort_key = strtolower($sort_key);
      $sort_enums = array_change_key_case($display->getGraphQlSortEnums());
      if (array_key_exists($sort_key, $sort_enums)) {
        $exposed_input['sort_by'] = $sort_enums[$sort_key]['value'];
      }
    }

    // Set sort order.
    $exposed_sort_dir = $display->getOption('exposed_form')['options']['expose_sort_order'] ?? TRUE;
    if ($exposed_sort_dir && $sort_dir) {
      $exposed_input['sort_order'] = $sort_dir === 'ASC' ? 'ASC' : 'DESC';
    }

    // Construct contextual filters.
    // Contextual args are a bit yolo.
    $context_args = [];
    foreach ($contextual_filter ?: [] as $value) {
      $context_args[] = is_bool($value) ? (string) intval($value) : $value;
    }

    $metadata->addCacheableDependency($view_entity);

    // Execute the view.
    $render_context = new RenderContext();

    $executed_view = $this->renderer->executeInRenderContext(
      $render_context,
      function () use ($view, $context_args, $exposed_input) {
        $view->setExposedInput($exposed_input);
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
