<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\graphql_compose_views\Plugin\views\display\GraphQL;
use Drupal\graphql_compose_views\Plugin\views\row\GraphQLEntityRow;
use Drupal\graphql_compose_views\Plugin\views\row\GraphQLFieldRow;
use Drupal\views\Views;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

use function Symfony\Component\String\u;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "View",
 * )
 */
class View extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   *
   * Add dynamic view types that use View interface.
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Views represent collections of curated data from the site.'),
      'fields' => fn() => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('The ID of the view.'),
        ],
        'view' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The machine name of the view.'),
        ],
        'display' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The machine name of the display.'),
        ],
        'langcode' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The language code of the view.'),
        ],
        'label' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The human friendly label of the view.'),
        ],
        'description' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The description of the view.'),
        ],
        'pageInfo' => [
          'type' => Type::nonNull(static::type('ViewPageInfo')),
          'description' => (string) $this->t('Information about the page in the view.'),
        ],
      ],
    ]);

    $viewStorage = $this->entityTypeManager->getStorage('view');

    $union_types = [];

    foreach (Views::getApplicableViews('graphql_display') as $applicable_view) {
      // Destructure view and display ids.
      [$view_id, $display_id] = $applicable_view;

      /** @var \Drupal\views\ViewEntityInterface|null $view_entity */
      if (!$view_entity = $viewStorage->load($view_id)) {
        continue;
      }

      $view = $view_entity->getExecutable();
      $view->setDisplay($display_id);

      // The underlying entity is unsupported. Don't even bother.
      if (!$base_type = $view->getBaseEntityType()) {
        continue;
      }

      /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
      $display = $view->getDisplay();
      $row_plugin = $display->getPlugin('row');

      $base_type_plugin = $this->gqlEntityTypeManager->getPluginInstance($base_type->id());
      if (!$base_type_plugin) {
        $row_type = 'UnsupportedType';
      }
      elseif ($row_plugin instanceof GraphQLEntityRow) {
        $row_type = $base_type_plugin->getUnionTypeSdl();
      }
      elseif ($row_plugin instanceof GraphQLFieldRow) {
        $row_type = $display->getGraphQlRowName();
      }

      // Get the description for the view.
      $view_description = $view->storage->get('description') ?: $this->t('Result for view @view display @display.', [
        '@view' => $view_id,
        '@display' => $display_id,
      ]);

      // Create type for view base on View Interface.
      $types[] = new ObjectType([
        'name' => $display->getGraphQlResultName(),
        'description' => (string) $view_description,
        'interfaces' => fn () => [static::type('View')],
        'fields' => fn() => [
          'id'          => Type::nonNull(Type::id()),
          'view'        => Type::nonNull(Type::string()),
          'display'     => Type::nonNull(Type::string()),
          'langcode'    => Type::string(),
          'label'       => Type::string(),
          'description' => Type::string(),
          'results'     => Type::nonNull(Type::listOf(Type::nonNull(static::type($row_type)))),
          'pageInfo'    => Type::nonNull(static::type('ViewPageInfo')),
        ],
      ]);

      // Keep a union of all the view types.
      $union_types[] = $display->getGraphQlResultName();

      $types = [
        ...$types,
        ...$this->getSortTypes($display),
        ...$this->getFieldTypes($display),
        ...$this->getFilterTypes($display),
        ...$this->getContextualFilterTypes($display),
      ];
    }

    // Create type for view base on View Interface.
    $types[] = new UnionType([
      'name' => 'ViewResultUnion',
      'description' => (string) $this->t('All available view result types.'),
      'types' => fn() => array_map(
        fn(string $result_name): Type => static::type($result_name),
        $union_types
      ) ?: [static::type('UnsupportedType')],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();

    $viewStorage = $this->entityTypeManager->getStorage('view');

    foreach (Views::getApplicableViews('graphql_display') as $applicable_view) {
      // Destructure view and display ids.
      [$view_id, $display_id] = $applicable_view;

      /** @var \Drupal\views\ViewEntityInterface|null $view_entity */
      if (!$view_entity = $viewStorage->load($view_id)) {
        continue;
      }

      $view = $view_entity->getExecutable();
      $view->setDisplay($display_id);

      /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
      $display = $view->getDisplay();

      if (!$display->getOption('graphql_query_exposed')) {
        continue;
      }

      // Get the description for the view.
      $view_description = $view->storage->get('description') ?: $this->t('Query for view @view display @display.', [
        '@view' => $view_id,
        '@display' => $display_id,
      ]);

      $extensions[] = new ObjectType([
        'name' => 'Query',
        'fields' => fn() => [
          $display->getGraphQlQueryName() => [
            'type' => static::type($display->getGraphQlResultName()),
            'description' => (string) $view_description,
            'args' => [
              ...$this->getSortArgs($display),
              ...$this->getPagerArgs($display),
              ...$this->getFilterArgs($display),
              ...$this->getContextualFilterArgs($display),
            ],
          ],
        ],
      ]);
    }

    return $extensions;
  }

  /**
   * Get sort types for display.
   *
   * @param \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display
   *   The view display.
   *
   * @return \GraphQL\Type\Definition\Type[]
   *   Sort types.
   */
  private function getSortTypes(GraphQL $display): array {
    $types = [];

    $exposed_sorts = array_filter(
      $display->getOption('sorts') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    if (!empty($exposed_sorts)) {
      $types[] = new EnumType([
        'name' => $display->getGraphQlSortInputName(),
        'values' => $display->getGraphQlSortEnums(),
      ]);
    }

    return $types;
  }

  /**
   * Get sort args for display.
   *
   * @param \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display
   *   The view display.
   *
   * @return array
   *   Sort args for GraphQL.
   */
  private function getSortArgs(GraphQL $display): array {
    $args = [];

    // Pagination enabled at a set limit.
    $exposed_sorts = array_filter(
      $display->getOption('sorts') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    if ($exposed_sorts) {
      $args['sortKey'] = [
        'type' => static::type($display->getGraphQlSortInputName()),
        'description' => (string) $this->t('Sort the view by this key.'),
      ];
    }

    if ($display->getOption('exposed_form')['options']['expose_sort_order'] ?? FALSE) {
      $args['sortDir'] = [
        'type' => static::type('SortDirection'),
        'description' => (string) $this->t('Sort the view direction.'),
      ];
    }

    return $args;
  }

  /**
   * Get pager types for display.
   *
   * @param \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display
   *   The view display.
   *
   * @return array
   *   Pager types.
   */
  private function getPagerArgs(GraphQL $display): array {

    if (!in_array($display->getOption('pager')['type'] ?? '', ['full', 'mini'])) {
      return [];
    }

    $args = [];

    $args['page'] = [
      'type' => Type::int(),
      'description' => (string) $this->t('The page number to display.'),
      'defaultValue' => 0,
    ];

    $pager_options = $display->getOption('pager')['options'] ?? [];

    // Allow setting items per page.
    if ($pager_options['expose']['items_per_page'] ?? FALSE) {
      $args['pageSize'] = [
        'type' => Type::int(),
        'description' => (string) $this->t('@label. Allowed values are: @input.', [
          '@label' => $pager_options['expose']['items_per_page_label'],
          '@input' => $pager_options['expose']['items_per_page_options'],
        ]),
        'defaultValue' => $pager_options['items_per_page'] ?? 10,
      ];
    }

    if ($pager_options['expose']['offset'] ?? FALSE) {
      $args['offset'] = [
        'type' => Type::int(),
        'description' => (string) $this->t('@label. The number of items skipped from beginning of this view.', [
          '@label' => $pager_options['expose']['offset_label'],
        ]),
        'defaultValue' => $pager_options['offset'] ?? 0,
      ];
    }

    return $args;
  }

  /**
   * Get field types for display.
   *
   * @param \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display
   *   The view display.
   *
   * @return \GraphQL\Type\Definition\Type[]
   *   Field types.
   */
  private function getFieldTypes(GraphQL $display): array {

    $types = [];

    $row_plugin = $display->getPlugin('row');

    // Build new type for fields based vew.
    if (!$row_plugin instanceof GraphQLFieldRow) {
      return [];
    }

    $options = $display->getPlugin('row')->options['field_options'];
    $fields = $display->view->display_handler->getOption('fields') ?: [];
    $fields = array_filter($fields, function ($field) {
      return empty($field['exclude']);
    });

    $type_fields = [];
    foreach (array_keys($fields) as $field_id) {
      $option = $options[$field_id];

      $default_alias = u($field_id)
        ->trimPrefix('field_')
        ->camel()
        ->toString();

      // Alias set by user.
      $field_alias = $option['alias'] ?: $default_alias;

      // Raw output could be anything.
      // We're going to need a custom scalar and dump junk into it.
      if ($option['type'] === 'Scalar') {
        $types[] = $custom_scalar = new CustomScalarType([
          'name' => $display->getGraphQlName($field_alias . 'Field'),
          'description' => (string) $this->t('Output of @field. Contents unknown.', [
            '@field' => $field_alias,
          ]),
        ]);

        $field_type = $custom_scalar;
      }
      else {
        $field_type = call_user_func([Type::class, $option['type']]);
      }

      $type_fields[$field_alias] = $field_type;
    }

    $types[] = new ObjectType([
      'name' => $display->getGraphQlRowName(),
      'description' => (string) $this->t('Result for view @view display @display.', [
        '@view' => $display->view->id(),
        '@display' => $display->display['id'],
      ]),
      'fields' => fn() => $type_fields,
    ]);

    return $types;
  }

  /**
   * Get filter types for display.
   *
   * @param \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display
   *   The view display.
   *
   * @return \GraphQL\Type\Definition\Type[]
   *   Filter types.
   */
  private function getFilterTypes(GraphQL $display): array {

    $types = [];

    // Create exposed input for view filters.
    // Create the filter input.
    $exposed_filters = array_filter(
      $display->getOption('filters') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    // Re-key filters by filter identifier.
    $exposed_filters = array_reduce(
      $exposed_filters,
      fn ($carry, $current) => $carry + [
        $current['expose']['identifier'] => $current,
      ],
      []
    );

    // Map the input type.
    $filter_fields = array_map(
      function ($filter) {
        $type = Type::string();

        switch ($filter['plugin_id']) {
          case 'boolean':
            $type = Type::boolean();
            break;

          case 'numeric':
            $type = Type::int();
            break;

          // This could be expanded to handle more types.
          // Things like date ranges etc.
          default:
            $type = Type::string();
            break;
        }

        if ($filter['expose']['multiple'] ?? FALSE) {
          $type = Type::listOf($type);
        }

        if ($filter['expose']['required'] ?? FALSE) {
          $type = Type::nonNull($type);
        }

        return [
          'type' => $type,
          'description' => (string) $this->t('@label @description', [
            '@label' => $filter['expose']['label'] ?? '',
            '@description' => $filter['expose']['description'] ?? '',
          ]),
        ];
      },
      $exposed_filters
    );

    if (!empty($filter_fields)) {
      $types[] = new InputObjectType([
        'name' => $display->getGraphQlFilterInputName(),
        'fields' => fn() => $filter_fields,
      ]);
    }

    return $types;
  }

  /**
   * Get filter arguments for display.
   *
   * @param \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display
   *   The view display.
   *
   * @return array
   *   Filter arguments.
   */
  private function getFilterArgs(GraphQL $display): array {

    $args = [];

    $exposed_filters = array_filter(
      $display->getOption('filters') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    if ($exposed_filters) {
      $args['filter'] = [
        'type' => static::type($display->getGraphQlFilterInputName()),
        'description' => (string) $this->t('Filter the view.'),
      ];
    }

    return $args;
  }

  /**
   * Get contextual filter types for display.
   *
   * @param \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display
   *   The view display.
   *
   * @return \GraphQL\Type\Definition\Type[]
   *   Contextual filter types.
   */
  private function getContextualFilterTypes(GraphQL $display): array {

    $types = [];

    // Create exposed input for contextual filters.
    $contextual_filters = $display->getOption('arguments') ?: [];

    $contextual_fields = array_map(
      fn () => Type::string(),
      $contextual_filters
    );

    if (!empty($contextual_fields)) {
      $types[] = new InputObjectType([
        'name' => $display->getGraphQlContextualFilterInputName(),
        'fields' => fn() => $contextual_fields,
      ]);
    }

    return $types;
  }

  /**
   * Get contextual filter arguments for display.
   *
   * @param \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display
   *   The view display.
   *
   * @return array
   *   Contextual filter arguments.
   */
  private function getContextualFilterArgs(GraphQL $display): array {
    $args = [];

    $contextual_filters = $display->getOption('arguments') ?: [];

    if ($contextual_filters) {
      $args['contextualFilter'] = [
        'type' => static::type($display->getGraphQlContextualFilterInputName()),
        'description' => (string) $this->t('Contextual filters for the view.'),
      ];
    }

    return $args;
  }

}
