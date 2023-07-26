<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\NodeInterface;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;

/**
 * Test image style fields and derivatives.
 *
 * @group graphql_compose
 */
class ImageStyleTest extends GraphQLComposeBrowserTestBase {

  use ImageFieldCreationTrait;

  /**
   * The test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * The test image.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $file;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'graphql_compose_image_style',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create image style.
    ImageStyle::create([
      'name' => 'test',
      'label' => 'Test',
      'effects' => [
        [
          'id' => 'image_resize',
          'data' => [
            'width' => 200,
            'height' => 200,
          ],
        ],
      ],
    ])->save();

    $this->setEntityConfig('image_style', 'test', [
      'enabled' => TRUE,
    ]);

    // Create a file.
    $this->file = File::create(['uri' => 'core/misc/druplicon.png']);
    $this->file->setPermanent();
    $this->file->save();

    // Create the node.
    $this->createContentType([
      'type' => 'test',
      'name' => 'Test node type',
    ]);

    $this->node = $this->createNode([
      'type' => 'test',
      'title' => 'Test',
      'status' => 1,
    ]);

    $this->createImageField('field_image', 'test');

    $this->node = $this->node->load($this->node->id());

    $this->node->set('field_image', [
      'target_id' => $this->file->id(),
      'alt' => 'Test',
      'title' => 'Test',
    ])->save();

    $this->setEntityConfig('node', 'test', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);

    $this->setFieldConfig('node', 'test', 'field_image', [
      'enabled' => TRUE,
    ]);
  }

  /**
   * Test load entity by id.
   */
  public function testImageStyleVariations(): void {

    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          id
          image {
            url
            variations(styles: [TEST]) {
              url
              width
              height
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $image = $content['data']['nodeTest']['image'];

    $this->assertStringContainsString('styles/test/public', $image['variations'][0]['url']);

    $this->assertEquals(200, $image['variations'][0]['width']);
    $this->assertEquals(200, $image['variations'][0]['height']);
  }

}
