<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get enum value.
 *
 * @DataProducer(
 *   id = "schema_enum_value",
 *   name = @Translation("Schema enum value"),
 *   description = @Translation("Dig out a value from an enum"),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("FieldItemListInterface")
 *   ),
 *   consumes = {
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Schema type defined in graphql compose plugin")
 *     ),
 *     "value" = @ContextDefinition("any",
 *       label = @Translation("Enum(s) to return")
 *     )
 *   }
 * )
 */
class SchemaEnumValue extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Schema type manager.
   *
   * @var \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager
   */
  protected GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->gqlSchemaTypeManager = $container->get('graphql_compose.schema_type_manager');

    return $instance;
  }

  /**
   * Finds the requested enum value.
   *
   * @param string $type
   *   The enum type to search.
   * @param string|array $value
   *   The value(s) to search for.
   *
   * @return string|array|null
   *   The found value(s).
   */
  public function resolve(string $type, string|array $value): string | array | null {

    $type = $this->gqlSchemaTypeManager->get($type);
    if (!$type || !$value) {
      return NULL;
    }

    /** @var \GraphQL\Type\Definition\EnumType $type */
    if (!is_array($value)) {
      return $type->getValue($value)?->value ?: NULL;
    }

    $result = [];
    foreach ($value as $key) {
      if (!is_string($key)) {
        continue;
      }
      if ($found = $type->getValue($key)?->value) {
        $result[] = $found;
      }
    }

    return $result ?: NULL;
  }

}
