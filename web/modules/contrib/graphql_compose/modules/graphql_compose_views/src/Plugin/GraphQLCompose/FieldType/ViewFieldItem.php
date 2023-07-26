<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\graphql_compose_views\Plugin\views\display\GraphQL;
use Drupal\views\Views;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "viewfield",
 * )
 */
class ViewFieldItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait {
    getProducers as getProducersTrait;
  }

  /**
   * Check if the view should be query embedded ot raw data.
   *
   * @return bool
   *   True if the view should be query embedded.
   */
  protected function isEmbeddedQuery(): bool {
    return (bool) ($this->configuration['viewfield_query'] ?? FALSE ?: FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): ?string {
    return (string) $this->t('This is a viewfield query proxy. Page size and contextual filters are applied within the CMS. See the actual view base query for more documentation on filters and options available. @parent', [
      '@parent' => parent::getDescription(),
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * Swap the type depending on config.
   */
  public function getTypeSdl(): string {
    return $this->isEmbeddedQuery() ? 'ViewResultUnion' : 'ViewReference';
  }

  /**
   * {@inheritdoc}
   */
  public function getArgsSdl(): array {

    if (!$this->isEmbeddedQuery()) {
      return [];
    }

    // It's unknown if the proxied view accepts
    // any of these values without loading it.
    return [
      'page' => [
        'type' => Type::int(),
        'description' => (string) $this->t('If enabled: The page number to display.'),
      ],
      'offset' => [
        'type' => Type::int(),
        'description' => (string) $this->t('If enabled: The number of items skipped from beginning of this view.'),
      ],
      'filter' => [
        'type' => Type::listOf($this->gqlSchemaTypeManager->get('KeyValueInput')),
        'description' => (string) $this->t('If enabled: The filters to apply to this view. Filters may not apply unless exposed.'),
      ],
      'sortKey' => [
        'type' => Type::string(),
        'description' => (string) $this->t('If enabled: Sort the view by this key.'),
      ],
      'sortDir' => [
        'type' => $this->gqlSchemaTypeManager->get('SortDirection'),
        'description' => (string) $this->t('If enabled: Sort the view direction.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {

    if (!$this->isEmbeddedQuery()) {
      // Revert to default functionality.
      return $this->getProducersTrait($builder);
    }

    return $builder->compose(
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue($this->getFieldName())),

      // Reference to the entity.
      $builder->context('entity', $builder->callback(fn (EntityReferenceFieldItemList $list) => $list->getEntity())),

      // View field values.
      $builder->callback(fn (EntityReferenceFieldItemList $list) => $list->first()?->getValue()),

      // Bind in the args.
      $builder->context('view_id', $builder->callback(fn($parent) => $parent['target_id'] ?? NULL ?: NULL)),
      $builder->context('display_id', $builder->callback(fn($parent) => $parent['display_id'] ?? NULL ?: NULL)),
      $builder->context('page_size', $builder->callback(fn($parent) => $parent['items_to_display'] ?? NULL ?: NULL)),
      $builder->context('arguments', $builder->callback(fn($parent) => explode('/', $parent['arguments'] ?? NULL ?: ''))),

      // Extract contextual filters with token replacement.
      $builder->context('arguments',
        $builder->produce('viewfield_contextual_filters')
          ->map('entity', $builder->fromContext('entity'))
          ->map('filters', $builder->fromContext('arguments'))),

      // Pass to normal view renderer.
      $builder->produce('views_executable')
        ->map('view_id', $builder->fromContext('view_id'))
        ->map('display_id', $builder->fromContext('display_id'))
        ->map('page', $builder->fromArgument('page'))
        ->map('page_size', $builder->fromContext('page_size'))
        ->map('offset', $builder->fromArgument('offset'))
        ->map('filter', $builder->fromArgument('filter'))
        ->map('contextual_filter', $builder->fromContext('arguments'))
        ->map('sort_key', $builder->fromArgument('sortKey'))
        ->map('sort_dir', $builder->fromArgument('sortDir'))
    );
  }

  /**
   * {@inheritdoc}
   *
   * If not embedding the view, just return field data to a ViewReference type.
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata) {

    if (empty($item->target_id) || empty($item->display_id)) {
      return NULL;
    }

    $view = Views::getView($item->target_id);
    $view->setDisplay($item->display_id);

    if (!$view || !$view->access($item->display_id)) {
      return NULL;
    }

    $metadata->addCacheableDependency($view);
    $display = $view->getDisplay();

    $args = empty($item->arguments) ? NULL : explode('/', $item->arguments ?? '');
    $size = is_numeric($item->items_to_display) ? (int) $item->items_to_display : NULL;

    return [
      'view' => $item->target_id,
      'display' => $item->display_id,
      'contextualFilter' => $args,
      'pageSize' => $size,
      'query' => $display instanceof GraphQL ? $display->getGraphQlQueryName() : NULL,
    ];
  }

}
