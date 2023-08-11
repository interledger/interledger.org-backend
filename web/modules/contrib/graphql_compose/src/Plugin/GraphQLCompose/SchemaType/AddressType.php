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
 *   id = "Address"
 * )
 */
class AddressType extends GraphQLComposeSchemaTypeBase {

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
      'description' => (string) $this->t('Complex address data.'),
      'fields' => fn() => [
        'langcode' => Type::string(),
        'country' => static::type('AddressCountry'),
        'givenName' => Type::string(),
        'additionalName' => Type::string(),
        'familyName' => Type::string(),
        'organization' => Type::string(),
        'addressLine1' => Type::string(),
        'addressLine2' => Type::string(),
        'postalCode' => Type::string(),
        'sortingCode' => Type::string(),
        'dependentLocality' => Type::string(),
        'locality' => Type::string(),
        'administrativeArea' => Type::string(),
      ],
    ]);

    return $types;
  }

}
