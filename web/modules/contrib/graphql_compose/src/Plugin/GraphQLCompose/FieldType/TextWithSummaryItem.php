<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "text_with_summary",
 *   type_sdl = "TextSummary",
 * )
 */
class TextWithSummaryItem extends TextItem {

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata) {

    $result = parent::resolveFieldItem($item, $context, $metadata);
    $result['summary'] = $item->summary;

    return $result;
  }

}
