<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\node\NodeInterface;

/**
 * Tests specific to GraphQL Compose entity type: Node.
 *
 * @group graphql_compose
 */
class EntityNodeTest extends GraphQLComposeBrowserTestBase {

  /**
   * The test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node_access_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType([
      'type' => 'test',
      'name' => 'Test node type',
    ]);

    $this->node = $this->createNode([
      'type' => 'test',
      'title' => 'Test',
      'body' => [
        'value' => 'Test content',
        'format' => 'plain_text',
      ],
      'status' => 1,
      'promote' => 1,
      'sticky' => 0,
      'langcode' => 'en',
    ]);

    $this->setEntityConfig('node', 'test', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);

    $this->setFieldConfig('node', 'test', 'body', [
      'enabled' => TRUE,
    ]);
  }

  /**
   * Test load entity by id.
   */
  public function testNodeLoadByUuid(): void {
    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          id
          title
          body {
            value
          }
          langcode {
            id
          }
          created {
            timestamp
          }
          status
          promote
          sticky
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty($content['data']['nodeTest']);
    $node = $content['data']['nodeTest'];

    $this->assertEquals($this->node->uuid(), $node['id']);
    $this->assertEquals('Test', $node['title']);
    $this->assertEquals('Test content', $node['body']['value']);
    $this->assertEquals('en', $node['langcode']['id']);
    $this->assertIsInt($node['created']['timestamp']);
    $this->assertTrue($node['status']);
    $this->assertTrue($node['promote']);
    $this->assertFalse($node['sticky']);
  }

  /**
   * Check node access is respected.
   */
  public function testNodeAccess() {

    // Create a new node type with a private field.
    $node_type = $this->createContentType([
      'type' => 'test_private',
      'name' => 'Test private type',
    ]);

    node_access_test_add_field($node_type);
    node_access_rebuild();

    $owner = $this->createUser();

    // Create a private node.
    $private_node = $this->createNode([
      'type' => 'test_private',
      'title' => 'Test private',
      'private' => ['value' => TRUE],
      'uid' => $owner->id(),
    ]);

    $this->setEntityConfig('node', 'test_private', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);

    $query = <<<GQL
      query {
        nodeTestPrivate(id: "{$private_node->uuid()}") {
          id
        }
      }
    GQL;

    // Try as anonymous.
    $content = $this->executeQuery($query);
    $this->assertNull($content['data']['nodeTestPrivate']);

    // Now try as user with grants permission.
    $privilegedUser = $this->createUser([
      'access content',
      'node test view',
      ...$this->graphqlPermissions,
    ]);

    $this->drupalLogin($privilegedUser);

    $content = $this->executeQuery($query);
    $this->assertNotNull($content['data']['nodeTestPrivate']['id']);

    // Now try as user without grants permission.
    $unprivilegedUser = $this->createUser([
      'access content',
      ...$this->graphqlPermissions,
    ]);
    $this->drupalLogin($unprivilegedUser);

    $content = $this->executeQuery($query);
    $this->assertNull($content['data']['nodeTestPrivate']);
  }

}
