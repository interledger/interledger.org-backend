<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Wrapper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager;
use Drupal\graphql_compose\LanguageInflector;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeInterface;

use function Symfony\Component\String\u;

/**
 * Wrapper for an entity.
 */
class EntityTypeWrapper {

  use StringTranslationTrait;

  /**
   * Language inflector service.
   */
  public LanguageInflector $inflector;

  /**
   * Field type plugin manager.
   */
  public GraphQLComposeFieldTypeManager $gqlFieldTypeManager;

  /**
   * Drupal entity field manager.
   */
  public EntityFieldManagerInterface $entityFieldManager;

  /**
   * Drupal entity type manager.
   */
  public EntityTypeManagerInterface $entityTypeManager;

  /**
   * Drupal config factory.
   */
  public ConfigFactoryInterface $configFactory;

  /**
   * Entity Type plugin bundle constructor.
   */
  public function __construct(
    public GraphQLComposeEntityTypeInterface $entityTypePlugin,
    public mixed $entity
  ) {
    $this->inflector = \Drupal::service('graphql_compose.language_inflector');
    $this->gqlFieldTypeManager = \Drupal::service('graphql_compose.field_type_manager');
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->configFactory = \Drupal::service('config.factory');
  }

  /**
   * Type of this entity and bundle. Eg paragraphText.
   */
  public function getNameSdl(): string {
    return u($this->entity->id())
      ->title()
      ->prepend($this->entityTypePlugin->getPrefix())
      ->camel()
      ->toString();
  }

  /**
   * Type of this entity and bundle, plural. Eg paragraphTexts.
   */
  public function getNamePluralSdl(): string {
    // Converting the original to a singular can have side effects
    // (wrong words in languages). Assume if someone has named a content
    // type "News" the plural version could be "NewsItems".
    // This should mostly work. Pages, PagesItems.
    $plural = $this->inflector->pluralize($this->getNameSdl())[0];

    // This is a failure catch. News = NewsItems.
    // If someone needs to make further adjustments, use the pluralize hook.
    if ($plural === $this->getNameSdl()) {
      $plural .= $this->t('Items');
    }

    return $plural;
  }

  /**
   * Type for the Schema. Title cased singular. Eg ParagraphText.
   */
  public function getTypeSdl(): string {
    return u($this->getNameSdl())
      ->camel()
      ->title()
      ->toString();
  }

  /**
   * Return the bundle description or the defined desceription on the plugin.
   */
  public function getDescription(): ?string {
    return method_exists($this->entity, 'getDescription')
        ? $this->entity->getDescription()
        : NULL;
  }

  /**
   * Check if this entity bundle is enabled.
   */
  public function isEnabled(): bool {
    if ($this->entity instanceof ConfigEntityTypeInterface) {
      return TRUE;
    }

    return $this->getSetting('enabled') ?: FALSE;
  }

  /**
   * Enabled single resolution query for type. Eg nodePage()
   */
  public function isQueryLoadEnabled(): bool {
    return $this->getSetting('query_load_enabled') ?: FALSE;
  }

  /**
   * Get a config setting.
   */
  public function getSetting($setting) {
    $config = $this->configFactory->get('graphql_compose.settings');

    $entity_type = $base_type = $this->entity;
    if ($entity_type instanceof ConfigEntityInterface) {
      if ($bundle_of = $entity_type->getEntityType()->getBundleOf()) {
        $base_type = $this->entityTypeManager->getDefinition($bundle_of);
      }
    }

    return $config->get($base_type->id() . '.' . $entity_type->id() . '.' . $setting) ?: NULL;
  }

}
