<?php

namespace Drupal\config_pages\Controller;

use Drupal\config_pages\ConfigPagesInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\config_pages\ConfigPagesTypeInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\config_pages\Entity\ConfigPagesType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class controller for ConfigPage entity..
 *
 * @package Drupal\config_pages
 */
class ConfigPagesController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The config page storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $configPagesStorage;

  /**
   * The config page type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $configPagesTypeStorage;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('config_pages'),
      $entity_type_manager->getStorage('config_pages_type'),
      $container->get('theme_handler'),
      $entity_type_manager
    );
  }

  /**
   * Constructs a ConfigPages object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $config_pages_storage
   *   The config page storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $config_pages_type_storage
   *   The config page type storage.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type manager.
   */
  public function __construct(EntityStorageInterface $config_pages_storage,
                              EntityStorageInterface $config_pages_type_storage,
                              ThemeHandlerInterface $theme_handler,
                              EntityTypeManagerInterface $entity_type_manager) {
    $this->configPagesStorage = $config_pages_storage;
    $this->configPagesTypeStorage = $config_pages_type_storage;
    $this->themeHandler = $theme_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Presents the config page creation form.
   *
   * @param \Drupal\config_pages\ConfigPagesTypeInterface $config_pages_type
   *   The config page type to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(ConfigPagesTypeInterface $config_pages_type, Request $request) {
    $config_page = $this->configPagesStorage->create(
      [
        'type' => $config_pages_type->id(),
      ]);
    return $this->entityFormBuilder()->getForm($config_page);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\config_pages\ConfigPagesTypeInterface $config_pages_type
   *   The config page type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(ConfigPagesTypeInterface $config_pages_type) {
    $config_pages_types = ConfigPagesType::loadMultiple();
    $config_pages_type = $config_pages_types[$config_pages_type->id()];
    return $this->t('Add %type config page', ['%type' => $config_pages_type->label()]);
  }

  /**
   * Presents the config page creation/edit form.
   *
   * @param \Drupal\config_pages\ConfigPagesTypeInterface $config_pages_type
   *   The config page type to add.
   *
   * @return array
   *   A form array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function classInit(ConfigPagesTypeInterface $config_pages_type = NULL) {
    $cp_type = $config_pages_type->id();
    $typeEntity = ConfigPagesType::load($cp_type);

    if (empty($typeEntity)) {
      throw new NotFoundHttpException();
    }

    $contextData = $typeEntity->getContextData();

    $config_page_ids = $this->entityTypeManager
      ->getStorage('config_pages')
      ->getQuery()
      ->accessCheck()
      ->condition('type', $cp_type)
      ->condition('context', $contextData)
      ->execute();

    if (!empty($config_page_ids)) {
      $config_page_id = array_shift($config_page_ids);
      $entityStorage = $this->entityTypeManager->getStorage('config_pages');
      $config_page = $entityStorage->load($config_page_id);
    }
    else {
      $config_page = $this->configPagesStorage->create([
        'type' => $cp_type,
      ]);
    }
    return $this->entityFormBuilder()->getForm($config_page);
  }

  /**
   * Presents the config page confirmation form.
   *
   * @param \Drupal\config_pages\ConfigPagesInterface $config_pages
   *   Config Page.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function clearConfirmation(ConfigPagesInterface $config_pages) {
    return \Drupal::formBuilder()->getForm('Drupal\config_pages\Form\ConfigPagesClearConfirmationForm', $config_pages->id());
  }

  /**
   * Page title callback for config page edit forms.
   *
   * @param string|null $label
   *   Label of entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translatable page title.
   */
  public function getPageTitle($label = NULL) {
    return $this->t('<em>Edit config page</em> @label', [
      '@label' => $label,
    ]);
  }

}
