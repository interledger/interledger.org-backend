<?php

namespace Drupal\Tests\image_styles_builder\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\image_styles_builder\DerivativeManager;
use Drupal\image_styles_builder\Plugin\Derivative\DerivativeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the cache of the derivative manager.
 *
 * @coversDefaultClass \Drupal\image_styles_builder\DerivativeManager
 * @group image_styles_builder
 *
 * @internal
 */
final class DerivativeManagerCacheTest extends UnitTestCase {

  /**
   * An instance of the DerivativeManager. This is the system under test.
   *
   * @var \Drupal\image_styles_builder\DerivativeManager
   */
  protected $derivativeManager;

  /**
   * A mocked instance of the dependency injection container.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * A mocked instance of the module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

  /**
   * A mocked instance of a cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cacheBackend;

  /**
   * The ID of a mocked derivative plugin used in the test.
   *
   * @var string
   */
  protected $pluginId = 'test_derivative';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the dependencies to inject into the derivative manager.
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);

    // Instantiate the derivative manager. This is the system under test.
    $this->derivativeManager = new DerivativeManager(
      $this->moduleHandler->reveal(),
      $this->cacheBackend->reveal(),
    );
  }

  /**
   * Tests that derivative plugins are cached upon creation.
   *
   * @covers ::createInstance
   */
  public function testCreateInstance() {
    // It is expected that the derivative manager will retrieve the complete
    // list of derivatives from the cache backend in order to look up the
    // definition of the derivative plugin that we are creating.
    // Even though we are creating multiple instances, this should only be
    // called once since the result should be cached in memory.
    $this->cacheBackend->get('image_style_builder')
      ->willReturn((object) [
        'data' => $this->getMockDerivativeDefinitions(),
      ])
      ->shouldBeCalledOnce();

    // Request the same plugin instance multiple times from the derivative
    // manager. The first instance is cached, and all subsequent invocations
    // will retrieve the instance from cache.
    for ($i = 0; $i < 5; ++$i) {
      $plugin = $this->derivativeManager->createInstance($this->pluginId);
      self::assertInstanceOf(DerivativeInterface::class, $plugin);
    }
  }

  /**
   * Gets a mocked derivative plugin definition.
   *
   * @return array
   *   The mocked derivative definition.
   */
  protected function getMockDerivativeDefinitions() {
    return [
      $this->pluginId => [
        'suffix' => 'default',
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
          '9_2_1080x240' => [
            'effects' => [
              [
                'type' => 'scale_and_crop',
                'width' => 1080,
                'height' => 240,
              ],
              [
                'type' => 'image_scale',
                'width' => 2580,
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
        ],
      ],
    ];
  }

}
