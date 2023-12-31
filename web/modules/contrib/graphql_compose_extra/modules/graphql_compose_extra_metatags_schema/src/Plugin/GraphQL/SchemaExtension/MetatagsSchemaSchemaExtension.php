<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_extra_metatags_schema\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

/**
 * Adds Entity Type GraphQL Compose plugins to the GraphQL API.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_extra_metatags_schema_schema",
 *   name = "GraphQL Compose Metatags Schema",
 *   description = "Add metatag information to the Schema.",
 *   schema = "graphql_compose"
 * )
 */
class MetatagsSchemaSchemaExtension extends SdlSchemaExtensionPluginBase
{

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function registerResolvers(ResolverRegistryInterface $registry)
  {
    $registry->addTypeResolver(
      'MetaTagUnion',
      function ($value) {

        switch ($value['tag']) {
          case 'link':
            return 'MetaTagLink';

          case 'meta':
            if (array_key_exists('jsonld', $value['attributes'])) {
              return 'MetaTagSchema';
            }
            if (array_key_exists('name', $value['attributes'])) {
              return 'MetaTagValue';
            }
            if (array_key_exists('property', $value['attributes'])) {
              return 'MetaTagProperty';
            }
          default:
            throw new \InvalidArgumentException("Could not resolve type for meta tag value");
        }
      }

    );
  }
}
