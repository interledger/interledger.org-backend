<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\EnumType;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ConnectionSortKeys",
 * )
 */
class ConnectionSortKeys extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new EnumType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Choose how your sorts will occur and on which field.'),
      'values' => [
        'CREATED_AT' => [
          'value' => 'CREATED_AT',
          'description' => (string) $this->t('Sort by creation date'),
        ],
        'UPDATED_AT' => [
          'value' => 'UPDATED_AT',
          'description' => (string) $this->t('Sort by updated date'),
        ],
        'TITLE' => [
          'value' => 'TITLE',
          'description' => (string) $this->t('Sort by entity title.'),
        ],
        'STICKY' => [
          'value' => 'STICKY',
          'description' => (string) $this->t('Sort by sticky status.'),
        ],
        'PROMOTED' => [
          'value' => 'PROMOTED',
          'description' => (string) $this->t('Sort by promoted status.'),
        ],
        'POPULAR' => [
          'value' => 'POPULAR',
          'description' => (string) $this->t('Sort by popular status.'),
        ],
      ],
    ]);

    return $types;
  }

}
