<?php

namespace Drupal\entity_reference_purger\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity reference purger queue worker.
 *
 * @QueueWorker(
 *   id = "entity_reference_purger",
 *   title = @Translation("Entity reference purger queue"),
 *   cron = {"time" = 60}
 * )
 */
class EntityReferencePurgerWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data) {
      $entity_type = $data['entity_type'];
      $entity_id = $data['entity_id'];
      $field_name = $data['field_name'];
      $delta = $data['delta'];

      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $entity = $entity_storage->load($entity_id);
      if ($entity instanceof EntityInterface) {
        $entity->get($field_name)->removeItem($delta);
        $entity->save();
      }
    }
  }

}
