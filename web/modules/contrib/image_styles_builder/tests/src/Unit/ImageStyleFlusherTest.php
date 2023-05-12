<?php

namespace Drupal\Tests\image_styles_builder\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\image_styles_builder\ImageStyleFlusher;
use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests image style flusher.
 *
 * @coversDefaultClass \Drupal\image_styles_builder\ImageStyleFlusher
 * @group image_styles_builder
 *
 * @internal
 */
final class ImageStyleFlusherTest extends UnitTestCase {

  /**
   * An instance of the ImageStyleFlusher.
   *
   * @var \Drupal\image_styles_builder\ImageStyleFlusher
   */
  protected $imageStyleFlusher;

  /**
   * A mocked instance of an image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $imageStyleStorage;

  /**
   * A mocked instance of a logger.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the dependencies to inject into the image style flusher.
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->imageStyleStorage = $this->prophesize(EntityStorageInterface::class);
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);

    $entity_type_manager->getStorage('image_style')
      ->willReturn($this->imageStyleStorage);

    $logger_factory->get('image_styles_builder')
      ->willReturn($this->logger)
      ->shouldBeCalledOnce();

    // Instantiate the image style flusher.
    $this->imageStyleFlusher = new ImageStyleFlusher(
      $entity_type_manager->reveal(),
      $logger_factory->reveal(),
    );
  }

  /**
   * Tests the logger will create a notice when image styles does not exists.
   *
   * @covers ::flush
   */
  public function testLoggerImageStyleNotExists() {
    $this->imageStyleStorage->load('foo')
      ->willReturn(FALSE)
      ->shouldBeCalledOnce();

    $this->logger->notice('The image style @machine_name does not exists.', ['@machine_name' => 'foo'])
      ->shouldBeCalledOnce();

    $image_style = new ImageStyle('foo', []);
    self::assertNull($this->imageStyleFlusher->flush($image_style));
  }

}
