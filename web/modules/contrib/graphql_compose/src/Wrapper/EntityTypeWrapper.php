<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Wrapper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql_compose\LanguageInflector;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager;
use function Symfony\Component\String\u;

/**
 * Wrapper for an entity.
 */
class EntityTypeWrapper {

  use StringTranslationTrait;

  /**
   * Language inflector service.
   *
   * @var \Drupal\graphql_compose\LanguageInflector
   */
  public LanguageInflector $inflector;

  /**
   * Field type plugin manager.
   *
   * @var \Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager
   */
  public GraphQLComposeFieldTypeManager $gqlFieldTypeManager;

  /**
   * Drupal entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public EntityFieldManagerInterface $entityFieldManager;

  /**
   * Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public EntityTypeManagerInterface $entityTypeManager;

  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public ConfigFactoryInterface $configFactory;

  /**
   * Constructs a EntityTypeWrapper object.
   *
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeInterface $entityTypePlugin
   *   The entity type plugin.
   * @param mixed $entity
   *   The entity to wrap.
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
   * Type of this entity and bundle.
   *
   * @return string
   *   The GraphQL type name of the entity type. Eg paragraphText.
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
   *
   * @return string
   *   The GraphQL type name of the entity type, plural.
   */
  public function getNamePluralSdl(): string {
    $plural = $this->inflector->pluralize($this->getNameSdl())[0];

    return $plural;
  }

  /**
   * Type for the Schema. Title cased singular.
   *
   * @return string
   *   The GraphQL type of the entity type. Eg ParagraphText.
   */
  public function getTypeSdl(): string {
    return u($this->getNameSdl())
      ->camel()
      ->title()
      ->toString();
  }

  /**
   * Return the bundle description or the defined description on the plugin.
   *
   * @return string|null
   *   The description of the wrapped entity.
   */
  public function getDescription(): ?string {
    return method_exists($this->entity, 'getDescription')
        ? $this->entity->getDescription()
        : NULL;
  }

  /**
   * Check if this entity bundle is enabled.
   *
   * @return bool
   *   True if the bundle is enabled.
   */
  public function isEnabled(): bool {
    if ($this->entity instanceof ConfigEntityTypeInterface) {
      return TRUE;
    }

    return (bool) $this->getSetting('enabled') ?: FALSE;
  }

  /**
   * Enabled single resolution query for type. Eg nodePage()
   *
   * @return bool
   *   True if the query load option is enabled.
   */
  public function isQueryLoadEnabled(): bool {
    return (bool) $this->getSetting('query_load_enabled') ?: FALSE;
  }

  /**
   * Get a config setting.
   *
   * @param string $setting
   *   The setting to get.
   *
   * @return mixed
   *   The setting value or null.
   */
  public function getSetting(string $setting): mixed {
    $settings = $this->configFactory->get('graphql_compose.settings');

    $entity_type = $base_type = $this->entity;
    if ($entity_type instanceof ConfigEntityInterface) {
      if ($bundle_of = $entity_type->getEntityType()->getBundleOf()) {
        $base_type = $this->entityTypeManager->getDefinition($bundle_of);
      }
    }

    return $settings->get('entity_config.' . $base_type->id() . '.' . $entity_type->id() . '.' . $setting) ?: NULL;
  }

}
