<?php

namespace Drupal\Tests\image_styles_builder\Kernel;

use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\ImageEffect\ScaleAndCropImageEffect;
use Drupal\image\Plugin\ImageEffect\ScaleImageEffect;
use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle as ImageStylePlugin;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Image Style generator service.
 *
 * @coversDefaultClass \Drupal\image_styles_builder\ImageStyleGenerator
 *
 * @group image_styles_builder
 * @group image_styles_builder_kernel
 *
 * @internal
 */
final class ImageStyleGeneratorTest extends KernelTestBase {

  /**
   * The Image Style generator.
   *
   * @var \Drupal\image_styles_builder\ImageStyleGenerator
   */
  protected $imageStyleGenerator;

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

    $this->imageStyleGenerator = $this->container->get('image_styles_builder.manager.image_style_generator');
  }

  /**
   * @covers ::generate
   */
  public function testGenerateExpectedReturn() {
    $image_style = new ImageStylePlugin('64x64', [
      [
        'type' => 'image_scale_and_crop',
        'width' => 64,
        'height' => 64,
      ],
      [
        'type' => 'image_scale',
        'width' => NULL,
        'height' => 64,
      ],
    ], 'example');

    $style = $this->imageStyleGenerator->generate($image_style);
    self::assertInstanceOf(ImageStyle::class, $style);
    self::assertEquals('example_64x64', $style->getName());
    self::assertCount(2, $style->getEffects());

    $effects = $style->getEffects()->getIterator()->getArrayCopy();
    $image_effect1 = array_shift($effects);
    $image_effect2 = array_shift($effects);
    self::assertInstanceOf(ScaleAndCropImageEffect::class, $image_effect1);
    self::assertInstanceOf(ScaleImageEffect::class, $image_effect2);
  }

}
