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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->config('graphql_compose.settings');
    $config->set('settings.custom', [
      [
        'type' => 'string',
        'description' => 'A custom string.',
        'name' => 'banana',
        'value' => 'BaNaNa',
      ],
      [
        'type' => 'string',
        'description' => '',
        'name' => 'bananas',
        'value' => 'ba',
      ],
      [
        'type' => 'string',
        'description' => '',
        'name' => 'bananas',
        'value' => 'na',
      ],
      [
        'type' => 'string',
        'description' => '',
        'name' => 'bananas',
        'value' => 'nas',
      ],
      [
        'type' => 'int',
        'description' => '',
        'name' => 'intval',
        'value' => '123',
      ],
      [
        'type' => 'boolean',
        'description' => '',
        'name' => 'boolval',
        'value' => 'yes',
      ],
    ]);
    $config->save();
  }

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

  /**
   * Test adding custom data to the schema.
   */
  public function testCustomSchemaInfo(): void {
    $query = <<<GQL
      query {
        info {
          banana
          bananas
          intval
          boolval
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty($content['data']['info']['banana']);
    $this->assertNotEmpty($content['data']['info']['bananas']);

    $this->assertEquals('BaNaNa', $content['data']['info']['banana']);
    $this->assertCount(3, $content['data']['info']['bananas']);

    $this->assertEquals('ba', $content['data']['info']['bananas'][0]);
    $this->assertEquals('na', $content['data']['info']['bananas'][1]);
    $this->assertEquals('nas', $content['data']['info']['bananas'][2]);

    $this->assertNotEmpty($content['data']['info']['intval']);
    $this->assertIsInt($content['data']['info']['intval']);
    $this->assertEquals(123, $content['data']['info']['intval']);

    $this->assertNotEmpty($content['data']['info']['boolval']);
    $this->assertIsBool($content['data']['info']['boolval']);
    $this->assertEquals(TRUE, $content['data']['info']['boolval']);
  }

}
