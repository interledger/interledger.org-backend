<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_builder\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\graphql_compose_layout_builder\EnabledBundlesTrait;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "BlockFieldUnion"
 * )
 */
class BlockFieldUnion extends GraphQLComposeSchemaTypeBase {

  use EnabledBundlesTrait;

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {

    $types = [];
    $union_types = [];

    // Check for enabled bundles.
    $bundles = $this->getEnabledBundlePlugins();

    foreach ($bundles as $bundle) {
      $fields = $this->gqlFieldTypeManager->getBundleFields(
        $bundle->entityTypePlugin->getPluginId(),
        $bundle->entity->id()
      );

      foreach ($fields as $field) {
        // Create a block field for each bundle and field.
        $type_name = $this->getLayoutBuilderFieldTypeSdl($field);

        // Interface fields will duplicate the type.
        if (array_key_exists($type_name, $types)) {
          continue;
        }

        // Create the block for the union of fields.
        $union_types[$type_name] = $types[$type_name] = new ObjectType([
          'name' => $type_name,
          'description' => $field->getDescription(),
          'fields' => fn() => [
            $field->getNameSdl() => fn (): Type => static::type(
              $field->getTypeSdl(),
              $field->isMultiple(),
              $field->isRequired()
            ),
          ],
        ]);
      }
    }

    ksort($union_types);

    // Combine all the types into a union.
    $types[] = new UnionType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t("A block field plugin is a modular piece of content with extra field information, that can be displayed in various regions of a website's layout."),
      'types' => fn() => array_merge(
        $union_types,

        // It's HIGHLY possible that something will go wrong here.
        // Layout Builder exposes fields we don't have a type for.
        // Or the field isn't data exposed.
        // This is a catch-all for those cases.
        [static::type('UnsupportedType')],
      ),
    ]);

    return $types;
  }

}
