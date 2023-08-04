<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\graphql\Plugin\GraphQL\DataProducer\Routing\RouteEntity;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests specific to GraphQL Compose routes.
 *
 * @group graphql_compose
 */
class RoutesTest extends GraphQLComposeBrowserTestBase {

  /**
   * The test node.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $nodes;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'graphql_compose_routes',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ConfigurableLanguage::create([
      'id' => 'ja',
      'weight' => 1,
    ])->save();

    $this->createContentType([
      'type' => 'test',
      'name' => 'Test node type',
    ]);

    $this->nodes[1] = $this->createNode([
      'type' => 'test',
      'title' => 'Test',
      'status' => 1,
      'langcode' => 'en',
      'path' => [
        'alias' => '/test',
      ],
    ]);

    $this->nodes[2] = $this->createNode([
      'type' => 'test',
      'title' => 'Test',
      'status' => 1,
      'langcode' => 'ja',
      'path' => [
        'alias' => '/test',
      ],
    ]);

    $this->setEntityConfig('node', 'test', [
      'enabled' => TRUE,
      'routes_enabled' => TRUE,
    ]);
  }

  /**
   * Test load entity by route.
   */
  public function testRouteLoadByNodeUri(): void {
    $query = <<<GQL
      query {
        route(path: "/node/{$this->nodes[1]->id()}") {
          ... on RouteInternal {
            entity {
              ... on NodeTest {
                id
              }
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals(
      $content['data']['route']['entity']['id'],
      $this->nodes[1]->uuid(),
    );
  }

  /**
   * Test load entity by route.
   */
  public function testRouteLoadByAlias(): void {
    $query = <<<GQL
      query {
        route(path: "/test") {
          ... on RouteInternal {
            entity {
              ... on NodeTest {
                id
              }
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals(
      $content['data']['route']['entity']['id'],
      $this->nodes[1]->uuid(),
    );
  }

  /**
   * Test load entity by route (language).
   */
  public function testRouteLoadWithLangcode(): void {
    $query = <<<GQL
      query {
        route(path: "/test", langcode: "ja") {
          ... on RouteInternal {
            entity {
              ... on NodeTest {
                id
              }
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals(
      $this->nodes[2]->uuid(),
      $content['data']['route']['entity']['id']
    );
  }

  /**
   * We have a tight integration with GQL core resolver.
   *
   * It's relying on the fact it's a loose type.
   * Ensure it's still possible.
   */
  public function testRouteEntityLoose(): void {
    $rp = new \ReflectionProperty(RouteEntity::class, 'entityBuffer');

    $this->assertFalse($rp->hasType());
  }

}
