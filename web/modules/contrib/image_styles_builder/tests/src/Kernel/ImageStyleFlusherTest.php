<?php

namespace Drupal\Tests\image_styles_builder\Kernel;

use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Image Style flusher service.
 *
 * @coversDefaultClass \Drupal\image_styles_builder\ImageStyleFlusher
 *
 * @group image_styles_builder
 * @group image_styles_builder_kernel
 *
 * @internal
 */
final class ImageStyleFlusherTest extends KernelTestBase {

  /**
   * The Image Style flusher.
   *
   * @var \Drupal\image_styles_builder\ImageStyleFlusher
   */
  protected $imageStyleFlusher;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
    'image_styles_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->imageStyleStorage = $this->container->get('entity_type.manager')->getStorage('image_style');
    $this->imageStyleStorage->create(['name' => 'foo', 'label' => 'Foo'])->save();
    $this->imageStyleStorage->create(['name' => 'bar', 'label' => 'Bar'])->save();
    $this->imageStyleFlusher = $this->container->get('image_styles_builder.manager.image_style_flusher');
  }

  /**
   * @covers ::flush
   */
  public function testGenerateExpectedReturn() {
    self::assertCount(2, $this->imageStyleStorage->loadMultiple());
    $img_style = new ImageStyle('foo', []);
    $this->imageStyleFlusher->flush($img_style);
    self::assertCount(1, $this->imageStyleStorage->loadMultiple());
  }

}
