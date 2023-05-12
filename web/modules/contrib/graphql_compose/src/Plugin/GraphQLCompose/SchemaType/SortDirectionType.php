<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\EnumType;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "SortDirection",
 * )
 */
class SortDirectionType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $sorts = [
      'ASC' => [
        'value' => 'ASC',
        'description' => (string) $this->t('Ascending'),
      ],
      'DESC' => [
        'value' => 'DESC',
        'description' => (string) $this->t('Descending'),
      ],
    ];

    $types[] = new EnumType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('List of menus available to load.'),
      'values' => $sorts,
    ]);

    return $types;
  }

}
