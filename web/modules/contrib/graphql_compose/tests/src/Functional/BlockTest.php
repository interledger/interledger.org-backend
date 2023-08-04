<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;

/**
 * Test block plugin loading.
 *
 * @group graphql_compose
 */
class BlockTest extends GraphQLComposeBrowserTestBase {

  /**
   * Block content to place.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected BlockContentInterface $blockContent;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'graphql_compose_blocks',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a block type to place.
    $block_type = BlockContentType::create([
      'id' => 'basic_block',
      'label' => 'Basic block',
    ]);
    $block_type->save();

    block_content_add_body_field($block_type->id());

    $this->blockContent = BlockContent::create([
      'info' => 'My content block',
      'type' => 'basic_block',
      'body' => [
        [
          'value' => 'This is the block content',
          'format' => filter_default_format(),
        ],
      ],
    ]);

    $this->blockContent->save();

    // Enable block type.
    $this->setEntityConfig('block_content', 'basic_block', [
      'enabled' => TRUE,
    ]);

    $this->setFieldConfig('block_content', 'basic_block', 'body', [
      'enabled' => TRUE,
    ]);
  }

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

  /**
   * Test load entity by id.
   */
  public function testBlockContent(): void {
    $query = <<<GQL
      query {
        block(id: "block_content:{$this->blockContent->uuid()}") {
          ... on BlockContent {
            id
            title
            render
            entity {
              ... on BlockContentBasicBlock {
                id
                title
                langcode {
                  id
                }
                reusable
                changed {
                  timestamp
                }
                body {
                  processed
                }
              }
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertStringContainsStringIgnoringCase(
      'This is the block content',
      $content['data']['block']['entity']['body']['processed']
    );

    $this->assertStringContainsStringIgnoringCase(
      'This is the block content',
      $content['data']['block']['render']
    );
  }

}
