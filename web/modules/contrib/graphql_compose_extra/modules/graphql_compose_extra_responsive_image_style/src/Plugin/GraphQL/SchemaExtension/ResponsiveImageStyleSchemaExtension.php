<?php

namespace Drupal\graphql_compose_extra_responsive_image_style\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

/**
 * Add responsive image styles to the Schema.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_extra_responsive_image_style_schema",
 *   name = "GraphQL Compose Responsive Image Style",
 *   description = "Add responsive image styles to the Schema.",
 *   schema = "graphql_compose"
 * )
 */
class ResponsiveImageStyleSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

        // Add style() query to Image types.
    $registry->addFieldResolver(
      'Image',
      'responsiveVariations',
      $builder->compose(
        $builder->produce('property_path')
          ->map('value', $builder->fromContext('field_value'))
          ->map('path', $builder->fromValue('entity')),

        $builder->context('entity', $builder->fromParent()),

        $builder->produce('schema_enum_value')
          ->map('type', $builder->fromValue('ResponsiveImageStyleAvailable'))
          ->map('value', $builder->fromArgument('styles')),

        $builder->compose(
          $builder->produce('responsive_image_derivatives')
            ->map('entity', $builder->fromContext('entity'))
            ->map('styles', $builder->fromParent())
        )
      ),
    );

    // Add style() query to Image types. (Deprecated)
    $registry->addFieldResolver(
      'Image',
      'responsive',
      $builder->compose(
        $builder->produce('property_path')
          ->map('value', $builder->fromContext('field_value'))
          ->map('path', $builder->fromValue('entity')),

        $builder->produce('responsive_image_derivative')
          ->map('entity', $builder->fromParent())
          ->map('responsive',
            $builder->compose(
              $builder->produce('schema_enum_value')
                ->map('type', $builder->fromValue('ResponsiveImageStyleAvailable'))
                ->map('value', $builder->fromArgument('name')),
              $builder->context('name_arg', $builder->fromParent()),
            )
          )
      ),
    );

    // Load up the image style entity on a new field "style".  (Deprecated)
    $registry->addFieldResolver(
      'ResponsiveImageStyleDerivative',
      'responsive',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('responsive_image_style'))
        ->map('id', $builder->fromContext('name_arg'))
    );
  }

}
