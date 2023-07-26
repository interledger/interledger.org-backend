<?php

namespace Drupal\graphql_compose_extra_image_style\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

/**
 * Add image styles to the Schema.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_extra_image_style_schema",
 *   name = "GraphQL Compose Image Style Path",
 *   description = "Add image styles to the Schema with path.",
 *   schema = "graphql_compose"
 * )
 */
class ImageStylePathSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    // Add style() query to Image types.
    $registry->addFieldResolver(
      'Image',
      'style',
      $builder->compose(
        $builder->produce('property_path')
          ->map('value', $builder->fromContext('field_value'))
          ->map('path', $builder->fromValue('entity')),

        $builder->produce('image_derivative_path')
          ->map('entity', $builder->fromParent())
          ->map('style',
            $builder->compose(
              $builder->produce('schema_enum_value')
                ->map('type', $builder->fromValue('ImageStylePathAvailable'))
                ->map('value', $builder->fromArgument('name')),
              $builder->context('name_arg', $builder->fromParent()),
            )
          )
      ),
    );

    // Load up the image style entity on a new field "style".
    $registry->addFieldResolver(
      'ImageStylePathDerivative',
      'style',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('image_style'))
        ->map('id', $builder->fromContext('name_arg'))
    );
  }

}
