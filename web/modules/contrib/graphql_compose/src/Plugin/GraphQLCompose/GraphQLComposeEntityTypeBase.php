<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use Drupal\graphql_compose\Wrapper\EntityTypeWrapper;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function Symfony\Component\String\u;

/**
 * Base class that can be used for schema extension plugins.
 */
abstract class GraphQLComposeEntityTypeBase extends PluginBase implements GraphQLComposeEntityTypeInterface, ContainerFactoryPluginInterface {

  /**
   * Static storage of bundles for plugin.
   *
   * @var \Drupal\graphql_compose\Wrapper\EntityTypeWrapper[]
   */
  private array $bundles;

  /**
   * Constructs a GraphQLComposeEntityTypeBase object.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Drupal module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Drupal entity type bundle service.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager $gqlFieldTypeManager
   *   GraphQL Compose field type plugin manager.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager
   *   GraphQL Compose schema type plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected GraphQLComposeFieldTypeManager $gqlFieldTypeManager,
    protected GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('graphql_compose.field_type_manager'),
      $container->get('graphql_compose.schema_type_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Entity type @id.', ['@id' => $this->getPluginId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getInterfaces(): array {
    $interfaces = $this->pluginDefinition['interfaces'] ?? [];

    if ($this->gqlFieldTypeManager->getInterfaceFields($this->getPluginId())) {
      $interfaces[] = $this->getInterfaceTypeSdl();
    }

    $this->moduleHandler->invokeAll('graphql_compose_entity_interfaces_alter', [
      &$interfaces,
      $this,
    ]);

    return $interfaces;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix(): string {
    return $this->pluginDefinition['prefix'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeSdl(): string {
    $type = $this->pluginDefinition['type_sdl'] ?? $this->getPluginId();

    return u($type)
      ->camel()
      ->title()
      ->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFields(): array {
    $base_fields = $this->pluginDefinition['base_fields'] ?? [];

    $this->moduleHandler->invokeAll('graphql_compose_entity_base_fields_alter', [
      &$base_fields,
      $this->getPluginId(),
    ]);

    return $base_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnionTypeSdl(): string {
    return u($this->getTypeSdl())
      ->append('Union')
      ->toString();
  }

  /**
   * Interfaces for the schema. Eg [Node, PageBanana].
   */
  public function getInterfaceTypeSdl(): string {
    return u($this->getTypeSdl())
      ->append('Interface')
      ->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle(string $bundle_id): ?EntityTypeWrapper {
    $bundles = $this->getBundles();
    return $bundles[$bundle_id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles(): array {
    if (isset($this->bundles)) {
      return $this->bundles;
    }

    $this->bundles = [];

    $entity_type = $this->entityTypeManager->getDefinition($this->getPluginId());
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($this->getPluginId());

    if ($storage_type = $entity_type->getBundleEntityType()) {
      $entity_types = $this->entityTypeManager->getStorage($storage_type)->loadMultiple();
    }

    foreach (array_keys($bundle_info) as $bundle_id) {
      $bundle = new EntityTypeWrapper($this, $entity_types[$bundle_id] ?? $entity_type);
      if ($bundle->isEnabled()) {
        $this->bundles[$bundle_id] = $bundle;
      }
    }

    return $this->bundles ?: [];
  }

  /**
   * {@inheritdoc}
   *
   * Register unions and interfaces only if there is multiple enabled bundles.
   */
  public function registerTypes(): void {

    $bundles = $this->getBundles();
    if (!$bundles) {
      return;
    }

    foreach ($bundles as $bundle) {
      $fields = $this->gqlFieldTypeManager->getBundleFields($this->getPluginId(), $bundle->entity->id());

      // Create bundle type.
      $entityType = new ObjectType([
        'name' => $bundle->getTypeSdl(),
        'description' => $bundle->getDescription() ?: $this->getDescription(),
        'interfaces' => fn() => array_map(
          fn($interface): Type => $this->gqlSchemaTypeManager->get($interface),
          $this->getInterfaces()
        ),
        'fields' => function () use ($fields) {
          $result = [];
          foreach ($fields as $field) {
            $result[$field->getNameSdl()] = [
              'description' => $field->getDescription(),
              'type' => $this->gqlSchemaTypeManager->get(
                $field->getTypeSdl(),
                $field->isMultiple(),
                $field->isRequired()
              ),
              'args' => $field->getArgsSdl(),
            ];
          }
          return $result;
        },
      ]);

      $this->gqlSchemaTypeManager->add($entityType);

      // Enable loading by query with id.
      if ($bundle->isQueryLoadEnabled()) {
        $entityQuery = new ObjectType([
          'name' => 'Query',
          'fields' => fn() => [
            $bundle->getNameSdl() => [
              'type' => $this->gqlSchemaTypeManager->get($bundle->getTypeSdl()),
              'description' => (string) $this->t('Fetch data for a specific @bundle', [
                '@bundle' => $bundle->getTypeSdl(),
              ]),
              'args' => [
                'id' => [
                  'type' => Type::nonNull(Type::id()),
                  'description' => (string) $this->t('The id of the @bundle to load.', [
                    '@bundle' => $bundle->getTypeSdl(),
                  ]),
                ],
              ],
            ],
          ],
        ]);

        $this->gqlSchemaTypeManager->extend($entityQuery);
      }

      // Add union types for non-simple unions.
      foreach ($fields as $field_plugin) {
        // Check it uses the union trait.
        if (!$field_plugin instanceof FieldUnionInterface) {
          continue;
        }

        // The unsupported field points to an unsupported type.
        if ($field_plugin->getUnionTypeSdl() === 'UnsupportedType') {
          continue;
        }

        // Generic unions return a generic entity union.
        if ($field_plugin->isGenericUnion()) {
          continue;
        }

        // Single unions just return the type.
        if ($field_plugin->isSingleUnion()) {
          continue;
        }

        // Create the new union type.
        $union = new UnionType([
          'name' => $field_plugin->getUnionTypeSdl(),
          'description' => $field_plugin->getDescription(),
          'types' => fn() => array_map(
            fn($type): Type => $this->gqlSchemaTypeManager->get($type),
            $field_plugin->getUnionTypeMapping()
          ) ?: [$this->gqlSchemaTypeManager->get('UnsupportedType')],
        ]);

        $this->gqlSchemaTypeManager->add($union);
      }
    }

    // Create interface.
    $interface_fields = $this->gqlFieldTypeManager->getInterfaceFields($this->getPluginId());
    if ($interface_fields) {
      $interface = new InterfaceType([
        'name' => $this->getInterfaceTypeSdl(),
        'description' => $this->getDescription(),
        'fields' => function () use ($interface_fields) {
          $fields = [];
          foreach ($interface_fields as $field) {
            $fields[$field->getNameSdl()] = [
              'type' => $this->gqlSchemaTypeManager->get(
                $field->getTypeSdl(),
                $field->isMultiple(),
                $field->isRequired()
              ),
              'description' => $field->getDescription(),
            ];
          }

          return $fields;
        },
      ]);

      $this->gqlSchemaTypeManager->add($interface);
    }

    // Create generic entity wide union.
    $union = new UnionType([
      'name' => $this->getUnionTypeSdl(),
      'description' => $this->getDescription(),
      'types' => fn() => array_map(
        fn($bundle): Type => $this->gqlSchemaTypeManager->get($bundle->getTypeSdl()),
        $bundles
      ) ?: [$this->gqlSchemaTypeManager->get('UnsupportedType')],
    ]);

    $this->gqlSchemaTypeManager->add($union);
  }

  /**
   * {@inheritdoc}
   *
   * Resolve unions only if there is multiple enabled bundles.
   */
  public function registerResolvers(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {

    $bundles = $this->getBundles();
    if (!$bundles) {
      return;
    }

    $entity_class = $this->entityTypeManager
      ->getDefinition($this->getPluginId())
      ->getClass();

    foreach ($bundles as $bundle) {

      // Add bundle type resolution.
      $registry->addTypeResolver(
        $bundle->getTypeSdl(),
        function ($value) use ($entity_class) {
          if ($value instanceof ($entity_class)) {
            return $this->getBundle($value->bundle())->getTypeSdl();
          }
          throw new UserError('Could not resolve entity type.');
        }
      );

      $fields = $this->gqlFieldTypeManager->getBundleFields($this->getPluginId(), $bundle->entity->id());

      // Add fields to bundle type.
      foreach ($fields as $field_plugin) {
        // Add field resolution.
        $registry->addFieldResolver(
          $bundle->getTypeSdl(),
          $field_plugin->getNameSdl(),
          $field_plugin->getProducers($builder)
        );
      }

      // Enable loading by query with id.
      if ($bundle->isQueryLoadEnabled()) {
        $registry->addFieldResolver(
          'Query',
          $bundle->getNameSdl(),
          $builder->produce('entity_load_by_uuid_or_id')
            ->map('type', $builder->fromValue($this->getPluginId()))
            ->map('bundles', $builder->fromValue([$bundle->entity->id()]))
            ->map('identifier', $builder->fromArgument('id'))
        );
      }

      // Add union field resolution for non-simple unions.
      foreach ($fields as $field_plugin) {
        // Check it uses the union trait.
        if (!$field_plugin instanceof FieldUnionInterface) {
          continue;
        }

        // Generic unions return a generic entity union.
        if ($field_plugin->isGenericUnion()) {
          continue;
        }

        // Single unions just return the type.
        if ($field_plugin->isSingleUnion()) {
          continue;
        }

        $map = $field_plugin->getUnionTypeMapping();

        $registry->addTypeResolver(
          $field_plugin->getUnionTypeSdl(),
          function ($value) use ($entity_class, $map) {
            if (array_key_exists($value->bundle(), $map)) {
              return $map[$value->bundle()];
            }

            throw new UserError(sprintf('Could not resolve entity of type %s::%s for this field, is it enabled in the field config?', $entity_class, $value->bundle()));
          }
        );
      }
    }

    // Resolve generic entity wide union.
    $registry->addTypeResolver(
      $this->getUnionTypeSdl(),
      function ($value) use ($entity_class) {
        if ($value instanceof ($entity_class)) {
          $bundle = $this->getBundle($value->bundle());
          if ($bundle) {
            return $bundle->getTypeSdl();
          }
        }

        throw new UserError(sprintf('Could not resolve entity of type %s::%s, is it enabled in the schema?', $entity_class, $value->bundle()));
      }
    );

  }

}
