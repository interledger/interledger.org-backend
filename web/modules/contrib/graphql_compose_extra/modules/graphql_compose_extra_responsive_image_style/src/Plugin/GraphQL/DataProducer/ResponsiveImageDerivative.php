<?php

namespace Drupal\graphql_compose_extra_responsive_image_style\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\FileInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns an responsive image style derivative of an image.
 *
 * @DataProducer(
 *   id = "responsive_image_derivative",
 *   name = @Translation("Responsive Image Derivative"),
 *   description = @Translation("Returns an image derivative."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Responsive Image derivative properties")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       required = FALSE
 *     ),
 *     "responsive" = @ContextDefinition("string",
 *       label = @Translation("Responsive Image style")
 *     )
 *   }
 * )
 */
class ResponsiveImageDerivative extends DataProducerPluginBase implements ContainerFactoryPluginInterface
{

    protected $dataStructure = [
        'srcset',
        'media',
        'type',
    ];

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

        $entityUri = $entity->getFileUri();
        $access = $entity->access('view', null, true);
        $metadata->addCacheableDependency($access);
        if ($access->isAllowed()) {

            $image = \Drupal::service('image.factory')->get($entityUri);

            if ($image->isValid()) {
                $width = $image->getWidth();
                $height = $image->getHeight();
            }

            $variables = [
                'uri' => $image->getSource(),
                'width' => $width,
                'height' => $height,
            ];

            $responsive_image_style = ResponsiveImageStyle::load($style);

            $metadata->addCacheableDependency($responsive_image_style);

            $context = new RenderContext();

            // Load all defined breakpoints.
            $breakpoints = array_reverse(
                \Drupal::service('breakpoint.manager')->getBreakpointsByGroup($responsive_image_style->getBreakpointGroup())
            );

            // Create source for each breakpoint.
            foreach ($responsive_image_style->getKeyedImageStyleMappings() as $breakpoint_id => $multipliers) {
                if (isset($breakpoints[$breakpoint_id]) && $breakpoint_id == "responsive_image.viewport_sizing") {
                    $source = _responsive_image_build_source_attributes($variables, $breakpoints[$breakpoint_id], $multipliers);
                }
            }

            // Map responsive image data to data structure.
            foreach ($this->dataStructure as $name) {
                $structured_source[$name] = isset($source->storage()[$name]) ? $source->storage()[$name]->value() : '';
            }

            // Create Uri from the fallback image style.
            $fallback = $responsive_image_style->getFallbackImageStyle();

            if (isset($fallback)) {
                $fallback_style = ImageStyle::load($fallback);

                $fallback_file_url = $this->renderer->executeInRenderContext(
                    $context,
                    function () use ($fallback_style, $entityUri) {
                        return $fallback_style->buildUrl($entityUri);
                    }
                );

                $fallback_file_path = $this->renderer->executeInRenderContext(
                    $context,
                    function () use ($fallback_file_url) {
                        $image_path = \Drupal::service('file_url_generator')->generateString($fallback_file_url);
                        return $image_path;
                    }
                );

                $fallback_file_uri = $this->renderer->executeInRenderContext(
                    $context,
                    function () use ($fallback_style, $entityUri) {
                        $uri = $fallback_style->buildUri($entityUri);
                        return $uri;
                    }
                );

                // Return root-relative URL for fallback image.
                if (isset($fallback_file_uri)) {
                    if (file_exists($entityUri) && !file_exists($fallback_file_uri)) {
                        $fallback_style->createDerivative($entityUri, $fallback_file_uri);
                    }
                    $fallback_image = \Drupal::service('image.factory')->get($fallback_file_uri);
                }
            }

            if (!$context->isEmpty()) {
                $metadata->addCacheableDependency($context->pop());
            }

            return [
                'path' => $fallback_file_path, //deprecated
                'url' => $fallback_file_url,
                'srcSetPath' => $structured_source['srcset'], //deprecated
                'srcSet' => $structured_source['srcset'],
                'width' => isset($fallback_image) ? $fallback_image->getWidth() : '',
                'height' => isset($fallback_image) ? $fallback_image->getHeight() : '',
            ];
        }

        return NULL;
    }
}
