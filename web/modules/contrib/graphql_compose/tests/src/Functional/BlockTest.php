<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

/**
 * Test block plugin loading.
 *
 * @group graphql_compose
 */
class BlockTest extends GraphQLComposeBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'graphql_compose_blocks',
  ];

  /**
   * Test load entity by id.
   */
  public function testPluginBlock(): void {
    $query = <<<GQL
      query {
        block(id: "system_powered_by_block") {
          ... on BlockPlugin {
            id
            title
            render
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals(
      'system_powered_by_block',
      $content['data']['block']['id']
    );

    $this->assertNull($content['data']['block']['title']);

    $this->assertStringContainsStringIgnoringCase(
      'Powered by',
      $content['data']['block']['render']
    );
  }

}
