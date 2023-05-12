<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_fragments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\graphql\Entity\ServerInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\TypeWithFields;
use GraphQL\Type\Definition\UnionType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the GraphiQL query builder IDE.
 */
class FragmentController extends ControllerBase {

  /**
   * Construct a new Fragment Controller.
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
   * Controller for the GraphiQL query builder IDE.
   *
   * @param \Drupal\graphql\Entity\ServerInterface $graphql_server
   *   The server.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   The render array.
   */
  public function show(ServerInterface $graphql_server, Request $request) {

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

    // Sort multidimentional array by key.
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
