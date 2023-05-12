<?php

namespace Drupal\config_pages\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\config_pages\ConfigPagesInterface;

use Drupal\Core\Url;

/**
 * Defines the config page entity class.
 *
 * @ContentEntityType(
 *   id = "config_pages",
 *   label = @Translation("Config page"),
 *   bundle_label = @Translation("Config page type"),
 *   handlers = {
 *     "storage" = "Drupal\config_pages\ConfigPagesStorage",
 *     "access" = "Drupal\config_pages\ConfigPagesAccessControlHandler",
 *     "list_builder" = "Drupal\config_pages\ConfigPagesListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\config_pages\ConfigPagesViewsData",
 *     "form" = {
 *       "add" = "Drupal\config_pages\ConfigPagesForm",
 *       "edit" = "Drupal\config_pages\ConfigPagesForm",
 *       "default" = "Drupal\config_pages\ConfigPagesForm"
 *     },
 *     "translation" = "Drupal\config_pages\ConfigPagesTranslationHandler"
 *   },
 *   admin_permission = "administer config_pages types",
 *   base_table = "config_pages",
 *   links = {
 *     "canonical" = "/config_pages/{config_pages}",
 *     "edit-form" = "/config_pages/{config_pages}",
 *     "collection" = "/admin/structure/config_pages/config-pages-content",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "label",
 *     "context" = "context",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "config_pages_type",
 *   field_ui_base_route = "entity.config_pages_type.edit_form",
 *   render_cache = TRUE,
 * )
 */
class ConfigPages extends ContentEntityBase implements ConfigPagesInterface {

  use EntityChangedTrait;

  /**
   * The theme the config page is being created in.
   *
   * When creating a new config page from the config page library, the user is
   * redirected to the configure form for that config page in the given theme.
   * The theme is stored against the config page when the config page
   * add form is shown.
   *
   * @var string
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    if ($duplicate->revision_id) {
      $duplicate->revision_id->value = NULL;
    }
    $duplicate->id->value = NULL;
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function setTheme($theme) {
    $this->theme = $theme;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->theme;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Config page ID'))
      ->setDescription(t('The config page ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The config page UUID.'))
      ->setReadOnly(TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ConfigPage description'))
      ->setDescription(t('A brief description of your config page.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', ['region' => 'hidden'])
      ->setDisplayConfigurable('form', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('ConfigPage type'))
      ->setDescription(t('The config page type.'))
      ->setSetting('target_type', 'config_pages_type');

    $fields['context'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Context'))
      ->setDescription(t('The Config Page context.'))
      ->setRevisionable(FALSE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the config page was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    return \Drupal::entityTypeManager()
      ->getStorage('config_pages')
      ->create($values);
  }

  /**
   * Helper function.
   *
   * @param string $type
   *   Config page type to load.
   * @param string $context
   *   Context which should be used to load entity.
   *
   * @return \Drupal\config_pages\Entity\ConfigPages|null
   *   Returns config page entity.
   */
  public static function config($type, $context = NULL) {

    // Build conditions.
    if (!empty($type)) {
      $conditions['type'] = $type;

      // Get current context if NULL.
      if ($context == NULL) {
        $type = ConfigPagesType::load($type);
        if (!is_object($type)) {
          return NULL;
        }
        $conditions['context'] = $type->getContextData();
      }
      else {
        $conditions['context'] = $context;
      }

      $list = \Drupal::entityTypeManager()
        ->getStorage('config_pages')
        ->loadByProperties($conditions);
    }

    // Try to get the fallback config page.
    if (!$list && $context == NULL) {
      $conditions['context'] = $type->getContextData(TRUE);
      $list = \Drupal::entityTypeManager()
        ->getStorage('config_pages')
        ->loadByProperties($conditions);
    }

    return $list ? current($list) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    $config_pages_type = ConfigPagesType::load($this->bundle());
    $menu = $config_pages_type ? $config_pages_type->get('menu') : [];
    $path = $menu['path'] ?? '';

    return $path
      ? Url::fromRoute('config_pages.' . $this->bundle(), ['config_pages' => $this->id()], $options)
      : Url::fromRoute('entity.config_pages.canonical', ['config_pages' => $this->id()], $options);
  }

}
