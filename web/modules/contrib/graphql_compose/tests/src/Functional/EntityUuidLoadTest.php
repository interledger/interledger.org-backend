<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\node\NodeInterface;

/**
 * Test UUID load switching.
 *
 * @group graphql_compose
 */
class EntityUuidLoadTest extends GraphQLComposeBrowserTestBase {

  /**
   * The test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

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
      'status' => 1,
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
   * Check entity can load by uuid.
   */
  public function testLoadContentEntity(): void {
    // Try normal load by UUID.
    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          id
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals($this->node->uuid(), $content['data']['nodeTest']['id']);

    // Enable entity ids.
    $settings['config']['graphql_compose.settings']['settings']['expose_entity_ids'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];

    $this->writeSettings($settings);

    _graphql_compose_cache_flush();

    // Try load by ID.
    $query = <<<GQL
    query {
      nodeTest(id: "{$this->node->id()}") {
        id
        uuid
      }
    }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals($this->node->id(), $content['data']['nodeTest']['id']);
    $this->assertEquals($this->node->uuid(), $content['data']['nodeTest']['uuid']);

    // Try load by UUID again.
    $query = <<<GQL
    query {
      nodeTest(id: "{$this->node->uuid()}") {
        id
        uuid
      }
    }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals($this->node->id(), $content['data']['nodeTest']['id']);
    $this->assertEquals($this->node->uuid(), $content['data']['nodeTest']['uuid']);
  }

}
