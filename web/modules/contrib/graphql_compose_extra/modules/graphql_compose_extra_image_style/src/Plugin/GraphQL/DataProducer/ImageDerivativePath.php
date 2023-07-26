<?php

namespace Drupal\graphql_compose_extra_image_style\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\FileInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns an image style derivative of an image.
 *
 * @DataProducer(
 *   id = "image_derivative_path",
 *   name = @Translation("Image Derivative"),
 *   description = @Translation("Returns an image derivative."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Image derivative properties")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       required = FALSE
 *     ),
 *     "style" = @ContextDefinition("string",
 *       label = @Translation("Image style")
 *     )
 *   }
 * )
 */
class ImageDerivativePath extends DataProducerPluginBase implements ContainerFactoryPluginInterface
{

    /**
     * The rendering service.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected $renderer;

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
    {
        return new static(
            $configuration,
            $pluginId,
            $pluginDefinition,
            $container->get('renderer')
        );
    }

    /**
     * ImageDerivative constructor.
     *
     * @param array                                 $configuration
     *   The plugin configuration array.
     * @param string                                $pluginId
     *   The plugin id.
     * @param mixed                                 $pluginDefinition
     *   The plugin definition.
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *   The renderer service.
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        array $configuration,
        $pluginId,
        $pluginDefinition,
        RendererInterface $renderer
    ) {
        parent::__construct($configuration, $pluginId, $pluginDefinition);
        $this->renderer = $renderer;
    }

    /**
     * Resolver.
     *
     * @param \Drupal\file\FileInterface                               $entity
     * @param string                                                   $style
     * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
     *
     * @return array|null
     */
    public function resolve(FileInterface $entity = null, $style, RefinableCacheableDependencyInterface $metadata)
    {
        // Return if we dont have an entity.
        if (!$entity) {
            return null;
        }

        $access = $entity->access('view', null, true);
        $metadata->addCacheableDependency($access);
        if ($access->isAllowed() && $image_style = ImageStyle::load($style)) {

            $width = $entity->width;
            $height = $entity->height;

            if (empty($width) || empty($height)) {
                /**
                 * @var \Drupal\Core\Image\ImageInterface $image
                 */
                $image = \Drupal::service('image.factory')->get($entity->getFileUri());
                if ($image->isValid()) {
                    $width = $image->getWidth();
                    $height = $image->getHeight();
                }
            }

            // Determine the dimensions of the styled image.
            $dimensions = [
                'width' => $width,
                'height' => $height,
            ];

            $image_style->transformDimensions($dimensions, $entity->getFileUri());
            $metadata->addCacheableDependency($image_style);

            // The underlying URL generator that will be invoked will leak cache
            // metadata, resulting in an exception. By wrapping within a new render
            // context, we can capture the leaked metadata and make sure it gets
            // incorporated into the response.
            $context = new RenderContext();
            $url = $this->renderer->executeInRenderContext(
                $context,
                function () use ($image_style, $entity) {
                    return $image_style->buildUrl($entity->getFileUri());
                }
            );

            $path = $this->renderer->executeInRenderContext(
                $context,
                function () use ($url) {
                    $image_path = \Drupal::service('file_url_generator')->generateString($url);
                    return $image_path;
                }
            );

            if (!$context->isEmpty()) {
                $metadata->addCacheableDependency($context->pop());
            }

            return [
                'url' => $url,
                'path' => $path,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
            ];
        }

        return NULL;
    }
}
