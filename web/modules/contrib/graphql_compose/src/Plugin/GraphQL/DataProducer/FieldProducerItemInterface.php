<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Classes must implement a way to process a field item.
 */
interface FieldProducerItemInterface {

  /**
   * Resolve a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   Field value to process.
   * @param array $context
   *   Contextual consumes passed to the parent resolve().
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cache helper.
   *
   * @return mixed|void
   *   Result to pass to producer base.
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata);

}
