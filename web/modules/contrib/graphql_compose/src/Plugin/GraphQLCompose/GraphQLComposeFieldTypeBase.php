<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use Drupal\graphql_compose\Wrapper\EntityTypeWrapper;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function Symfony\Component\String\u;

/**
 * Base class that can be used for schema extension plugins.
 */
abstract class GraphQLComposeFieldTypeBase extends PluginBase implements GraphQLComposeFieldTypeInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('graphql_compose.entity_type_manager'),
      $container->get('graphql_compose.field_type_manager'),
      $container->get('graphql_compose.schema_type_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Entity Type plugin constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity type bundle information service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager service.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager $gqlEntityTypeManager
   *   Entity type plugin manager.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager $gqlFieldTypeManager
   *   Field type plugin manager.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager
   *   SDL type plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected GraphQLComposeEntityTypeManager $gqlEntityTypeManager,
    protected GraphQLComposeFieldTypeManager $gqlFieldTypeManager,
    protected GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager,
    protected ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition(): ?FieldDefinitionInterface {
    return $this->configuration['field_definition'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType(): string {
    return $this->configuration['field_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): string {
    return $this->configuration['field_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityWrapper(): ?EntityTypeWrapper {
    return $this->configuration['entity'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): ?string {
    return $this->configuration['description'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNameSdl(): string {
    $name_sdl = $this->configuration['name_sdl'];

    return u($name_sdl)
      ->trimPrefix('field_')
      ->camel()
      ->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeSdl(): string {
    return $this->pluginDefinition['type_sdl'] ?? $this->configuration['type_sdl'];
  }

  /**
   * Get common union name between entity bundles.
   */
  public function getUnionTypeSdl(): string
  {
    $definition = $this->getFieldDefinition();
    $field_parent_entity = $definition->getTargetEntityTypeId();
    $field_parent_bundle = $definition->getTargetBundle();
    $parent_entity_plugin_type = $this->gqlEntityTypeManager->getPluginInstance($field_parent_entity);
    $parent_bundle_sdl = $parent_entity_plugin_type->getBundle($field_parent_bundle)->getTypeSdl();
    $name_sdl = u($this->getNameSdl())
      ->camel()
      ->title()
      ->prepend('Field')
      ->append('Union')
      ->toString();

    return $parent_bundle_sdl . $name_sdl;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiple(): bool {
    return $this->configuration['multiple'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return $this->configuration['required'] ?? FALSE;
  }

  public function registerTypes(): void
  {

    $type = $this->getFieldType();
    if ($type === "entity_reference" || $type === "entity_reference_revisions") {
      $settings = $this->getFieldDefinition()->getSettings();
      $target_type = $settings["target_type"];
      if ($target_type != 'domain' && isset($settings["handler_settings"]["target_bundles"])) {
        $entity_plugin_type = $this->gqlEntityTypeManager->getPluginInstance($target_type);
        $target_bundles = $settings["handler_settings"]["target_bundles"];

        if ($target_bundles && count($target_bundles) > 1) {
          $bundles = [];
          foreach ($target_bundles as $key => $bundle) {
            if ($entity_plugin_type->getBundle($bundle)) {
              $bundles[$key] = $entity_plugin_type->getBundle($bundle);
            }
          }
          $union = new UnionType([
            'name' => $this->getUnionTypeSdl(),
            'types' => fn () => array_map(
              fn (EntityTypeWrapper $bundle): Type => $this->gqlSchemaTypeManager->get($bundle->getTypeSdl()),
              $bundles
            ) ?: [$this->gqlSchemaTypeManager->get('UnsupportedType')],
          ]);

          $this->gqlSchemaTypeManager->add($union);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Resolve unions only if there is multiple enabled bundles.
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void
  {

    $type = $this->getFieldType();
    if ($type === "entity_reference" || $type === "entity_reference_revisions") {
      $settings = $this->getFieldDefinition()->getSettings();
      $target_type = $settings["target_type"];
      if ($target_type != 'domain' && isset($settings["handler_settings"]["target_bundles"])) {
        $bundles = $settings["handler_settings"]["target_bundles"];
        if ($bundles && count($bundles) > 1) {
          $entity_plugin_type = $this->gqlEntityTypeManager->getPluginInstance($target_type);
          $entity_class = $this->entityTypeManager
            ->getDefinition($target_type)
            ->getClass();
          $registry->addTypeResolver(
            $this->getUnionTypeSdl(),
            function ($value) use ($entity_class, $entity_plugin_type) {
              if ($value instanceof ($entity_class)) {
                return $entity_plugin_type->getBundle($value->bundle())->getTypeSdl();
              }
              throw new \InvalidArgumentException('Could not resolve entity union type.');
            }
          );
        }
      }
    }
  }

}
