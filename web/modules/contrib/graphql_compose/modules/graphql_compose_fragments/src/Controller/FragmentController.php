<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_fragments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\TypeWithFields;
use GraphQL\Type\Definition\UnionType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the GraphQL Compose fragment controller.
 */
class FragmentController extends ControllerBase {

  /**
   * Construct a new Fragment Controller.
   *
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager
   *   The GraphQL Compose schema type manager.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager $gqlEntityTypeManager
   *   The GraphQL Compose entity type manager.
   */
  public function __construct(
    protected GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager,
    protected GraphQLComposeEntityTypeManager $gqlEntityTypeManager
  ) {}

  /**
   * Add required dependencies.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('graphql_compose.schema_type_manager'),
      $container->get('graphql_compose.entity_type_manager')
    );
  }

  /**
   * Show fragments.
   *
   * @return array
   *   The render array.
   */
  public function show() {

    $types = [
      'unions' => [],
      'objects' => [],
    ];

    $extensions = [
      'objects' => [],
    ];

    // Load all entity types.
    foreach ($this->gqlEntityTypeManager->getPluginInstances() as $entity_type) {
      $entity_type->registerTypes();
    }

    // Load all types.
    foreach ($this->gqlSchemaTypeManager->getDefinitions() as $definition) {
      $this->gqlSchemaTypeManager->get($definition['id']);
    }

    // Expand definitions.
    foreach ($this->gqlSchemaTypeManager->getTypes() as $type) {
      // Instantiate the fields.
      if ($type instanceof TypeWithFields) {
        $type->getFields();
      }

      if ($type instanceof ObjectType) {
        $types['objects'][$type->name] = $type;
      }

      if ($type instanceof UnionType) {
        $type->getTypes();
        $types['unions'][$type->name] = $type;
      }
    }

    foreach ($this->gqlSchemaTypeManager->getExtensions() as $extension) {
      if ($extension instanceof TypeWithFields) {
        $extension->getFields();
      }

      if ($extension instanceof ObjectType) {
        $extensions['objects'][$extension->name] = $extension;
      }
    }

    // Sort multi dimensional array by key.
    foreach ($types as &$type) {
      ksort($type);
    }

    $this->messenger()->addMessage(
      $this->t('These fragments are intended as a guide, not a solution. Use them to quickly get started building your application.'),
      'info',
    );

    return [
      '#theme' => 'graphql_compose_fragments',
      '#attached' => [
        'library' => [
          'graphql_compose_fragments/fragments',
        ],
      ],
      '#types' => $types,
      '#extensions' => $extensions,
    ];
  }

}
