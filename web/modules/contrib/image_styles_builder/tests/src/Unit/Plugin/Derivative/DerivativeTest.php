<?php

namespace Drupal\Tests\image_styles_builder\Unit\Plugin\Derivative;

use Drupal\image_styles_builder\Plugin\Derivative\Derivative;
use Drupal\image_styles_builder\Plugin\Derivative\ImageEffect;
use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\image_styles_builder\Plugin\Derivative\Derivative
 * @group image_styles_builder
 *
 * @internal
 */
final class DerivativeTest extends UnitTestCase {

  /**
   * @covers ::getId
   */
  public function testGetId() {
    $plugin_definition = [
      'id' => 'test id',
      'label' => 'test label',
      'styles' => [],
    ];
    $derivative = new Derivative([], 'test', $plugin_definition);

    self::assertEquals('test id', $derivative->getId());
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $plugin_definition = [
      'styles' => [],
      'label' => 'test label',
    ];
    $derivative = new Derivative([], 'test', $plugin_definition);

    self::assertEquals('test label', $derivative->getLabel());
  }

  /**
   * @covers ::getStyle
   * @covers ::getStyles
   */
  public function testGetStyles() {
    $plugin_definition = [
      'suffix' => '',
      'styles' => [
        '9_2_64x14' => [
          'effects' => [
            [
              'type' => 'image_scale_and_crop',
              'width' => 64,
              'height' => 14,
            ],
          ],
        ],
        '9_2_64x14_webp' => [
          'effects' => [
            [
              'type' => 'image_convert',
              'data' => [
                'extension' => 'webp',
              ],
            ],
          ],
        ],
        'original_2580' => [
          'effects' => [
            [
              'type' => 'image_scale',
              'width' => 2580,
            ],
          ],
        ],
        '9_2_1080x240' => [
          'effects' => [
            [
              'type' => 'image_scale_and_crop',
              'width' => 1080,
              'height' => 240,
            ],
            [
              'type' => 'image_scale',
              'width' => 1080,
            ],
          ],
        ],
      ],
      'group' => 'default',
    ];
    $derivative = new Derivative([], 'test', $plugin_definition);

    $style_9_2_64x14 = $derivative->getStyle('9_2_64x14');
    $style_9_2_64x14_webp = $derivative->getStyle('9_2_64x14_webp');
    $style_9_2_1080x240 = $derivative->getStyle('9_2_1080x240');
    $style_original_2580 = $derivative->getStyle('original_2580');

    self::assertInstanceOf(ImageStyle::class, $style_9_2_64x14);
    self::assertInstanceOf(ImageStyle::class, $style_9_2_1080x240);
    self::assertInstanceOf(ImageStyle::class, $style_original_2580);

    self::assertEquals('9_2_64x14', $style_9_2_64x14->getId());
    self::assertContainsOnlyInstancesOf(ImageEffect::class, $style_9_2_64x14->getEffects());
    self::assertCount(1, $style_9_2_64x14->getEffects());
    self::assertEquals('image_scale_and_crop', $style_9_2_64x14->getEffects()[0]->getType());
    self::assertEquals(64, $style_9_2_64x14->getEffects()[0]->getWidth());
    self::assertEquals(14, $style_9_2_64x14->getEffects()[0]->getHeight());

    self::assertEquals('9_2_64x14_webp', $style_9_2_64x14_webp->getId());
    self::assertContainsOnlyInstancesOf(ImageEffect::class, $style_9_2_64x14_webp->getEffects());
    self::assertCount(1, $style_9_2_64x14_webp->getEffects());
    self::assertEquals('image_convert', $style_9_2_64x14_webp->getEffects()[0]->getType());
    self::assertEquals(['extension' => 'webp'], $style_9_2_64x14_webp->getEffects()[0]->getData());

    self::assertEquals('original_2580', $style_original_2580->getId());
    self::assertContainsOnlyInstancesOf(ImageEffect::class, $style_original_2580->getEffects());
    self::assertCount(1, $style_original_2580->getEffects());
    self::assertEquals('image_scale', $style_original_2580->getEffects()[0]->getType());
    self::assertEquals(2580, $style_original_2580->getEffects()[0]->getWidth());
    self::assertNull($style_original_2580->getEffects()[0]->getHeight());

    self::assertEquals('9_2_1080x240', $style_9_2_1080x240->getId());
    self::assertContainsOnlyInstancesOf(ImageEffect::class, $style_9_2_1080x240->getEffects());
    self::assertCount(2, $style_9_2_1080x240->getEffects());
    self::assertEquals('image_scale_and_crop', $style_9_2_1080x240->getEffects()[0]->getType());
    self::assertEquals(1080, $style_9_2_1080x240->getEffects()[0]->getWidth());
    self::assertEquals(240, $style_9_2_1080x240->getEffects()[0]->getHeight());
    self::assertEquals('image_scale', $style_9_2_1080x240->getEffects()[1]->getType());
    self::assertEquals(1080, $style_9_2_1080x240->getEffects()[1]->getWidth());
    self::assertNull($style_9_2_1080x240->getEffects()[1]->getHeight());

    self::assertEquals([
      '9_2_64x14' => $style_9_2_64x14,
      '9_2_64x14_webp' => $style_9_2_64x14_webp,
      '9_2_1080x240' => $style_9_2_1080x240,
      'original_2580' => $style_original_2580,
    ], $derivative->getStyles());
  }

}

namespace Drupal\image_styles_builder\Plugin\Derivative;

if (!\function_exists('t')) {

  /**
   * Mocks the t() function.
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $args
   *   An associative array of replacements to make after translation.
   *
   * @return string
   *   The translated string.
   */
  function t($string, array $args = []) {
    return strtr($string, $args);
  }

}
