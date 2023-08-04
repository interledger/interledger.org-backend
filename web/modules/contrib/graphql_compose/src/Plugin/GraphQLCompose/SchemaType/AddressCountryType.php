<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "AddressCountry"
 * )
 */
class AddressCountryType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('address')) {
      return [];
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Address country.'),
      'fields' => fn() => [
        'name' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The name of the country.'),
        ],
        'code' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The code of the country.'),
        ],
      ],
    ]);

    return $types;
  }

}
