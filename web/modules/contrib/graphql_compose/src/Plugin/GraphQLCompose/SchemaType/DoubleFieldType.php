<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType\DoubleFieldItem;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "DoubleField",
 * )
 */
class DoubleFieldType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('double_field')) {
      return [];
    }

    // Find all exposed double fields.
    // This assumes all fields have been bootstrapped.
    $fields = $this->gqlFieldTypeManager->getFields();

    array_walk_recursive($fields, function ($field) use (&$types) {
      if ($field instanceof DoubleFieldItem) {

        $types[$field->getTypeSdl()] = new ObjectType([
          'name' => $field->getTypeSdl(),
          'description' => (string) $this->t('A double field is a speciality field provided by the CMS.'),
          'fields' => fn() => [
            'first' => [
              'type' => static::type($field->getSubfieldTypeSdl('first')),
              'description' => (string) $this->t('The first value of the double field.'),
            ],
            'second' => [
              'type' => static::type($field->getSubfieldTypeSdl('second')),
              'description' => (string) $this->t('The second value of the double field.'),
            ],
          ],
        ]);
      }
    });

    return array_values($types);
  }

}
