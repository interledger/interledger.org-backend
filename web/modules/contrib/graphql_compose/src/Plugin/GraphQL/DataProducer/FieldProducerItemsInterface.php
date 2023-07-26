<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Classes must implement a way to process a field items.
 */
interface FieldProducerItemsInterface {

  /**
   * Resolve a field's items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   Field to process.
   * @param array $context
   *   Contextual consumes passed to the parent resolve().
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cache helper.
   *
   * @return array
   *   Result to pass to producer base.
   */
  public function resolveFieldItems(FieldItemListInterface $field, array $context, RefinableCacheableDependencyInterface $metadata): array;

}
