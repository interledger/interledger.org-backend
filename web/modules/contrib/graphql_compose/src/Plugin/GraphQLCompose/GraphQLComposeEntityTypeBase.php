<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use Drupal\graphql_compose\Wrapper\EntityTypeWrapper;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('graphql_compose.field_type_manager'),
      $container->get('graphql_compose.schema_type_manager'),
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
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager $gqlFieldTypeManager
   *   Field type plugin manager.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager
   *   SDL type plugin manager.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected GraphQLComposeFieldTypeManager $gqlFieldTypeManager,
    protected GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Entity type @id.', ['@id' => $this->getPluginId()]);
  }

  /**
   * Interfaces for the schema. Eg [Node, ContentPage].
   */
  protected function getInterfaces(): array {
    $interfaces = $this->pluginDefinition['interfaces'] ?? [];

    if ($this->gqlFieldTypeManager->getInterfaceFields($this->getPluginId())) {
      $interfaces[] = $this->getInterfaceTypeSdl();
    }

    return $interfaces;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix(): string {
    return $this->pluginDefinition['prefix'] ?? '';
  }

  /**
   * Type for the Schema. Title cased singular. Eg ParagraphText.
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

    // Create union.
    if ($bundles) {
      $union = new UnionType([
        'name' => $this->getUnionTypeSdl(),
        'types' => fn() => array_map(
          fn($bundle): Type => $this->gqlSchemaTypeManager->get($bundle->getTypeSdl()),
          $bundles
        ) ?: [$this->gqlSchemaTypeManager->get('UnsupportedType')],
      ]);

      $this->gqlSchemaTypeManager->add($union);
    }

  }

  /**
   * {@inheritdoc}
   *
   * Resolve unions only if there is multiple enabled bundles.
   */
  public function registerResolvers(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {
    $bundles = $this->getBundles();

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
          throw new \InvalidArgumentException('Could not resolve entity type.');
        }
      );

      $fields = $this->gqlFieldTypeManager->getBundleFields($this->getPluginId(), $bundle->entity->id());

      // Add fields to bundle type.
      foreach ($fields as $field) {
        $registry->addFieldResolver(
          $bundle->getTypeSdl(),
          $field->getNameSdl(),
          $field->getProducers($builder)
        );
      }

      // Enable loading by query with id.
      if ($bundle->isQueryLoadEnabled()) {
        $registry->addFieldResolver(
          'Query',
          $bundle->getNameSdl(),
          $builder->produce('entity_load_by_uuid')
            ->map('type', $builder->fromValue($this->getPluginId()))
            ->map('bundles', $builder->fromValue([$bundle->entity->id()]))
            ->map('uuid', $builder->fromArgument('id'))
        );
      }
    }

    // Resolve type wide union.
    if ($bundles) {
      $registry->addTypeResolver(
        $this->getUnionTypeSdl(),
        function ($value) use ($entity_class) {
          if ($value instanceof ($entity_class)) {
            $bundle = $this->getBundle($value->bundle());
            if ($bundle) {
              return $bundle->getTypeSdl();
            }
          }
          throw new \InvalidArgumentException('Could not resolve entity of type ' . $entity_class . '::' . $value->bundle() . ', is it enabled in the schema?');
        }
      );
    }
  }

}
