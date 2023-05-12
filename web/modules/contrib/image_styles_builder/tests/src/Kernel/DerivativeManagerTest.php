<?php

namespace Drupal\Tests\image_styles_builder\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Derivative discovery Plugin mechanism.
 *
 * @coversDefaultClass \Drupal\image_styles_builder\DerivativeManager
 *
 * @group image_styles_builder
 * @group image_styles_builder_kernel
 *
 * @internal
 */
final class DerivativeManagerTest extends KernelTestBase {

  /**
   * The Derivative Plugin manager.
   *
   * @var \Drupal\image_styles_builder\DerivativeManager
   */
  protected $derivativeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
    'image_styles_builder',
    'image_styles_builder_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->derivativeManager = $this->container->get('plugin.manager.image_styles_builder.derivative');
  }

  /**
   * @covers ::getDefinitions
   * @covers ::getDiscovery
   */
  public function testGetDefinitionsReturnsExpected() {
    $definitions = $this->derivativeManager->getDefinitions();
    self::assertEquals([
      'default' => [
        'id' => 'default',
        'label' => new TranslatableMarkup('Default'),
        'suffix' => 'isbt_default',
        'styles' => [
          '9_2_64x14' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 64,
                'height' => 14,
              ],
            ],
          ],
          '9_2_128x28' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'data' => [
                  'width' => 128,
                  'height' => 28,
                ],
              ],
            ],
          ],
          '9_2_640x142' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 640,
                'height' => 142,
                'data' => [
                  'width' => 1280,
                  'height' => 426,
                ],
              ],
            ],
          ],
          '9_2_640x142_webp' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 640,
                'height' => 142,
              ],
              [
                'type' => 'image_convert',
                'data' => [
                  'extension' => 'webp',
                ],
              ],
            ],
          ],
          '9_2_1080x240' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 1080,
                'height' => 240,
              ],
            ],
          ],
          '16_10_64x40' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 64,
                'height' => 40,
              ],
            ],
          ],
          '16_10_128x80' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 128,
                'height' => 80,
              ],
            ],
          ],
          '16_10_640x400' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 640,
                'height' => 400,
              ],
            ],
          ],
          '16_10_1080x675' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 1080,
                'height' => 675,
              ],
            ],
          ],
          '1_1_64x64' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 64,
                'height' => 64,
              ],
            ],
          ],
          '1_1_128x128' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 128,
                'height' => 128,
              ],
            ],
          ],
          'original_64' => [
            'effects' => [
              [
                'type' => 'image_scale',
                'width' => 64,
              ],
            ],
          ],
          'original_128' => [
            'effects' => [
              [
                'type' => 'image_scale',
                'width' => 128,
              ],
            ],
          ],
          'original_1080' => [
            'effects' => [
              [
                'type' => 'image_scale',
                'width' => 1080,
              ],
            ],
          ],
        ],
        'provider' => 'image_styles_builder_test',
      ],
    ], $definitions);
  }

}
