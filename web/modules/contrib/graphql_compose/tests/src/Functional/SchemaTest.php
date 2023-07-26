<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

/**
 * Generic schema based tests for GraphQL Compose.
 *
 * @group graphql_compose
 */
class SchemaTest extends GraphQLComposeBrowserTestBase {

  /**
   * Test schema returns something.
   */
  public function testSchemaInfo(): void {
    $query = <<<GQL
      query {
        info {
          version
          description
          home
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty($content['data']['info']['version']);
    $this->assertNotEmpty($content['data']['info']['description']);
    $this->assertNotEmpty($content['data']['info']['home']);
  }

}
