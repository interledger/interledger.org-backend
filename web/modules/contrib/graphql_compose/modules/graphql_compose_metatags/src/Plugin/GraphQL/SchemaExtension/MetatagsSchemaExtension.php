<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_metatags\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use GraphQL\Error\UserError;

/**
 * Adds Entity Type GraphQL Compose plugins to the GraphQL API.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_metatags_schema",
 *   name = "GraphQL Compose Metatags",
 *   description = "Add metatag information to the Schema.",
 *   schema = "graphql_compose"
 * )
 */
class MetatagsSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \GraphQL\Error\UserError
   *   Thrown when the type cannot be resolved.
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $registry->addTypeResolver(
      'MetaTagUnion',
      function ($value) {

        switch ($value['tag']) {
          case 'link':
            return 'MetaTagLink';

          case 'meta':
            if (array_key_exists('name', $value['attributes'])) {
              return 'MetaTagValue';
            }
            if (array_key_exists('property', $value['attributes'])) {
              return 'MetaTagProperty';
            }
          default:
            throw new UserError('Could not resolve type for meta tag value.');
        }
      }

    );
  }

}
