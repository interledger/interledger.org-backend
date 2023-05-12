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
   * Field producer constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager
   *   Schema type manager.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    protected GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('graphql_compose.schema_type_manager'),
    );
  }

  /**
   * Finds the requested enum value.
   */
  public function resolve(string $type, string|array $value): string | array | null {
    /** @var \GraphQL\Type\Definition\EnumType $type */
    $type = $this->gqlSchemaTypeManager->get($type);
    if (!$type || !$value) {
      return NULL;
    }

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
