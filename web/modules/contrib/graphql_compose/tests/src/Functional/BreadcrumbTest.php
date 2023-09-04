<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

/**
 * Tests specific to GraphQL Compose breadcrumbs.
 *
 * @group graphql_compose
 */
class BreadcrumbTest extends GraphQLComposeBrowserTestBase {

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
    'graphql_compose_routes',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->nodes[1] = $this->createNode([
      'type' => 'test',
      'title' => 'Test 1',
      'status' => 1,
      'langcode' => 'en',
      'path' => [
        'alias' => '/level1',
      ],
    ]);

    $this->nodes[2] = $this->createNode([
      'type' => 'test',
      'title' => 'Test 2',
      'status' => 1,
      'langcode' => 'en',
      'path' => [
        'alias' => '/level1/level2',
      ],
    ]);

    $this->nodes[3] = $this->createNode([
      'type' => 'test',
      'title' => 'Test 3',
      'status' => 1,
      'langcode' => 'en',
      'path' => [
        'alias' => '/level1/level2/level3',
      ],
    ]);

    $this->setEntityConfig('node', 'test', [
      'enabled' => TRUE,
      'routes_enabled' => TRUE,
    ]);
  }

  /**
   * Test load entity by route and get its breadcrumbs.
   */
  public function testNodeBreadcrumbs(): void {
    $query = <<<GQL
      query {
        route(path: "/level1/level2/level3") {
          ... on RouteInternal {
            breadcrumbs {
              url
              title
              internal
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $breadcrumbs = $content['data']['route']['breadcrumbs'];

    $this->assertNotEmpty($breadcrumbs);
    $this->assertIsArray($breadcrumbs);
    $this->assertCount(3, $breadcrumbs);

    $this->assertNotEmpty($breadcrumbs[1]['title']);
    $this->assertNotEmpty($breadcrumbs[2]['title']);

    $this->assertEquals(
      $breadcrumbs[1]['title'],
      (string) $this->nodes[1]->label(),
    );

    $this->assertEquals(
      $breadcrumbs[2]['title'],
      (string) $this->nodes[2]->label(),
    );

    // Make another check of a lower level breadcrumb.
    $query = <<<GQL
      query {
        route(path: "/level1/level2") {
          ... on RouteInternal {
            breadcrumbs {
              url
              title
              internal
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $breadcrumbs = $content['data']['route']['breadcrumbs'];

    $this->assertNotEmpty($breadcrumbs);
    $this->assertIsArray($breadcrumbs);
    $this->assertCount(2, $breadcrumbs);
  }

}
