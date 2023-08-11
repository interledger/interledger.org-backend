<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests specific to GraphQL Compose entity edge: Node.
 *
 * @group graphql_compose
 */
class EdgesTest extends GraphQLComposeBrowserTestBase {

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
    'graphql_compose_edges',
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

    $this->createContentType([
      'type' => 'test_alt',
      'name' => 'Test Alt node type',
    ]);

    $this->nodes[1] = $this->createNode([
      'type' => 'test',
      'title' => 'Test 1',
      'status' => 1,
    ]);

    $this->nodes[2] = $this->createNode([
      'type' => 'test',
      'title' => 'Test 2',
      'status' => 1,
    ]);

    $this->nodes[3] = $this->createNode([
      'type' => 'test_alt',
      'title' => 'Test 3',
      'status' => 1,
    ]);

    $this->nodes[4] = $this->createNode([
      'type' => 'test',
      'title' => 'Japanese',
      'status' => 1,
      'langcode' => 'ja',
    ]);

    $this->nodes[5] = $this->createNode([
      'type' => 'test',
      'title' => 'Unpublished',
      'status' => 0,
    ]);

    $this->setEntityConfig('node', 'test', [
      'enabled' => TRUE,
      'edges_enabled' => TRUE,
    ]);

    $this->setEntityConfig('node', 'test_alt', [
      'enabled' => TRUE,
      'edges_enabled' => TRUE,
    ]);
  }

  /**
   * Check results are expected order.
   */
  public function testEdgeLoad(): void {
    $query = <<<GQL
      query {
        nodeTests(first: 10) {
          nodes {
            __typename
            title
            status
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty($content['data']['nodeTests']['nodes']);

    $this->assertEquals([
      $content['data']['nodeTests']['nodes'][0]['title'],
      $content['data']['nodeTests']['nodes'][1]['title'],
    ], [
      $this->nodes[1]->title->value,
      $this->nodes[2]->title->value,
    ]);

    // Check only one type loads.
    $types = array_map(
      fn ($row) => $row['__typename'],
      $content['data']['nodeTests']['nodes']
    );

    $types = array_unique($types);

    $this->assertEquals(
      ['NodeTest'],
      $types
    );

    // Ensure only published content.
    $unpublished = array_filter(
      $content['data']['nodeTests']['nodes'],
      fn ($row) => !$row['status'],
    );

    $this->assertEmpty($unpublished);
  }

  /**
   * Check results are expected order (reverse).
   */
  public function testEdgeLoadReverse(): void {
    $query = <<<GQL
      query {
        nodeTests(first: 10, reverse: true) {
          nodes {
            title
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty($content['data']['nodeTests']['nodes']);

    $this->assertEquals([
      $this->nodes[2]->title->value,
      $this->nodes[1]->title->value,
    ], [
      $content['data']['nodeTests']['nodes'][0]['title'],
      $content['data']['nodeTests']['nodes'][1]['title'],
    ]);
  }

  /**
   * Check cursors go fwd and back.
   */
  public function testEdgeCursors(): void {

    // First page.
    $query = <<<GQL
      query {
        nodeTests(first: 1) {
          nodes {
            title
          }
          pageInfo {
            endCursor
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty(
      $content['data']['nodeTests']['pageInfo']['endCursor']
    );

    $this->assertEquals(
      $this->nodes[1]->title->value,
      $content['data']['nodeTests']['nodes'][0]['title']
    );

    $endCursor = $content['data']['nodeTests']['pageInfo']['endCursor'];

    // Second page.
    $query = <<<GQL
      query {
        nodeTests(first: 1, after: "{$endCursor}") {
          nodes {
            title
          }
          pageInfo {
            startCursor
            hasNextPage
            hasPreviousPage
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals(
      $this->nodes[2]->title->value,
      $content['data']['nodeTests']['nodes'][0]['title']
    );

    $this->assertFalse(
      $content['data']['nodeTests']['pageInfo']['hasNextPage']
    );

    $this->assertTrue(
      $content['data']['nodeTests']['pageInfo']['hasPreviousPage']
    );

    $startCursor = $content['data']['nodeTests']['pageInfo']['startCursor'];

    // And back to first page.
    $query = <<<GQL
      query {
        nodeTests(last: 1, before: "{$startCursor}") {
          nodes {
            title
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals(
      $this->nodes[1]->title->value,
      $content['data']['nodeTests']['nodes'][0]['title']
    );
  }

  /**
   * Test language filtering.
   */
  public function testEdgeLoadByLangcode(): void {

    $query = <<<GQL
      query {
        nodeTests(first: 1, langcode: "ja") {
          nodes {
            title
          }
          pageInfo {
            hasNextPage
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals(
      $this->nodes[4]->title->value,
      $content['data']['nodeTests']['nodes'][0]['title']
    );

    $this->assertFalse(
      $content['data']['nodeTests']['pageInfo']['hasNextPage']
    );
  }

}
