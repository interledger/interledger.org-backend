<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Form;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function Symfony\Component\String\u;

/**
 * Configure GraphQL Compose settings for this server.
 */
class SchemaForm extends ConfigFormBase {

  /**
   * Construct a new GraphQL Compose settings form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Drupal entity type bundle service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Drupal entity field manager.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager $gqlEntityTypeManager
   *   GraphQL Compose entity type manager.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager $gqlFieldTypeManager
   *   GraphQL Compose field type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Drupal module handler.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected GraphQLComposeEntityTypeManager $gqlEntityTypeManager,
    protected GraphQLComposeFieldTypeManager $gqlFieldTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('graphql_compose.entity_type_manager'),
      $container->get('graphql_compose.field_type_manager'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graphql_compose_schema';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['graphql_compose.settings'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfig() {
    return $this->config('graphql_compose.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_plugin_types = $this->gqlEntityTypeManager->getDefinitions();

    // Hide types that are not supported by GraphQL Compose.
    $entity_types = array_filter($entity_types, fn (EntityTypeInterface $type) => array_key_exists($type->id(), $entity_plugin_types));

    // Sort by entity label.
    uasort($entity_types, fn (EntityTypeInterface $a, EntityTypeInterface $b) => strcmp(
      (string) $a->getLabel(), (string) $b->getLabel()
    ));

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'graphql_compose/settings.admin';

    $form['layout'] = [
      '#type' => 'container',
      '#name' => 'layout',
    ];

    $form['layout']['entity_tabs'] = [
      '#type' => 'vertical_tabs',
      '#name' => 'entity-tabs',
    ];

    // Loop every entity type.
    foreach ($entity_types as $entity_type_id => $entity_type) {

      // Visual containers.
      $form['layout']['entity_tabs'][$entity_type_id] = [
        '#type' => 'details',
        '#title' => $entity_type->getLabel(),
        '#attributes' => [
          'class' => ['entity-type-tab'],
        ],
        '#group' => 'layout][entity_tabs',
      ];

      $form['layout']['entity_tabs'][$entity_type_id]['bundle_tabs'] = [
        '#type' => 'vertical_tabs',
        '#group' => 'layout][entity_tabs][' . $entity_type_id,
      ];

      if ($entity_type instanceof ConfigEntityTypeInterface) {
        // Config entities like menu and image styles.
        // We load all config entities of this type.
        $config_entities = $this->entityTypeManager->getStorage($entity_type->id())->loadMultiple();

        // Sort by label.
        uasort($config_entities, fn ($a, $b) => strcmp($a->label(), $b->label()));

        // Build entity "bundle" form without fields.
        foreach ($config_entities as $config_entity) {
          $this->buildEntityTypeBundle($form, $form_state, $entity_type, $config_entity);
        }
      }
      else {
        // Otherwise use bundle info.
        if ($storage_type = $entity_type->getBundleEntityType()) {
          $entity_bundles = $this->entityTypeManager->getStorage($storage_type)->loadMultiple();

          // Sort by bundle label.
          uasort($entity_bundles, fn (EntityInterface $a, EntityInterface $b) => strcmp(
            (string) $a->label(), (string) $b->label()
          ));
        }
        else {
          // Has no bundles, we'll just use the base entity type.
          $entity_bundles = [$entity_type->id() => $entity_type];
        }

        // Build entity "bundle" with fields.
        foreach ($entity_bundles as $bundle) {
          $this->buildEntityTypeBundle($form, $form_state, $entity_type, $bundle);
          if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
            $this->buildEntityTypeBundleFields($form, $form_state, $entity_type, $bundle);
          }
        }
      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Build the config form for a "bundle" of an entity type.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityTypeInterface $bundle
   *   The entity bundle.
   */
  protected function buildEntityTypeBundle(array &$form, FormStateInterface $form_state, EntityTypeInterface $entity_type, EntityInterface|EntityTypeInterface $bundle) {
    $entity_type_id = $entity_type->id();
    $bundle_id = $bundle->id();
    $settings = $this->getConfig()->get("entity_config.$entity_type_id.$bundle_id") ?: [];

    $form['settings'][$entity_type_id][$bundle_id] = [
      '#type' => 'details',
      '#title' => $bundle instanceof EntityTypeInterface ? $bundle->getLabel() : $bundle->label(),
      '#name' => $entity_type_id . '_tabs_' . $bundle_id,
      '#attributes' => [
        'class' => ['entity-bundle-tab'],
      ],
      '#group' => 'layout][entity_tabs][' . $entity_type_id . '][bundle_tabs',
      '#parents' => [
        'settings', 'entity_config', $entity_type_id, $bundle_id,
      ],
    ];

    $form['settings'][$entity_type_id][$bundle_id]['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable GraphQL'),
      '#default_value' => $settings['enabled'] ?? FALSE,
      '#description' => $this->t('Expose this type via GraphQL.'),
      '#weight' => -1,
      '#attributes' => [
        'class' => ['entity-bundle-enabled'],
      ],
    ];

    if ($entity_type instanceof ContentEntityTypeInterface) {
      $form['settings'][$entity_type_id][$bundle_id]['query_load_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Single Query'),
        '#default_value' => $settings['query_load_enabled'] ?? FALSE,
        '#description' => $this->t('Add a query to load this type by UUID.'),
      ];
    }

    // Allow other modules to add to this entity form.
    $this->moduleHandler->invokeAll('graphql_compose_entity_type_form_alter', [
      &$form['settings'][$entity_type_id][$bundle_id],
      $form_state,
      $entity_type,
      $bundle_id,
      $settings,
    ]);
  }

  /**
   * Build fields for content entities. Eg Node types. Media types.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityTypeInterface $bundle
   *   The entity bundle.
   */
  protected function buildEntityTypeBundleFields(array &$form, FormStateInterface $form_state, EntityTypeInterface $entity_type, EntityInterface|EntityTypeInterface $bundle) {
    $bundle_id = $bundle->id();
    $entity_type_id = $entity_type->id();

    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);
    $field_plugin_types = $this->gqlFieldTypeManager->getDefinitions();

    // Hide base fields.
    $fields = array_filter($fields, fn (FieldDefinitionInterface $field) => !$field instanceof BaseFieldDefinition);
    $fields = array_filter($fields, fn (FieldDefinitionInterface $field) => !$field instanceof BaseFieldOverride);

    // Hide fields that are not supported by GraphQL Compose.
    $fields = array_filter($fields, fn (FieldDefinitionInterface $field) => array_key_exists($field->getType(), $field_plugin_types));

    if (empty($fields)) {
      return;
    }

    $form['settings'][$entity_type_id][$bundle_id]['_fields'] = [
      '#type' => 'fieldset',
      '#title' => 'Fields',
      '#parents' => [
        'settings', 'field_config', $entity_type_id, $bundle_id,
      ],
    ];

    foreach ($fields as $field_id => $field) {
      $settings = $this->getConfig()->get("field_config.$entity_type_id.$bundle_id.$field_id") ?: [];

      $form['settings'][$entity_type_id][$bundle_id]['_fields'][$field_id] = [
        '#id' => $field_id,
        '#type' => 'details',
        '#title' => $this->t('@label (@id)', [
          '@label' => $field->getLabel(),
          '@id' => $field_id,
        ]),
      ];

      // Allow users to enable and disable the field.
      $form['settings'][$entity_type_id][$bundle_id]['_fields'][$field_id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Field'),
        '#default_value' => $settings['enabled'] ?? FALSE,
        '#weight' => -1,
      ];

      // Allow other modules to add to this entity form.
      $this->moduleHandler->invokeAll('graphql_compose_field_type_form_alter', [
        &$form['settings'][$entity_type_id][$bundle_id]['_fields'][$field_id],
        $form_state,
        $field,
        $settings,
      ]);

      // Hint at what the default value will be.
      $placeholder = u($field->getName())
        ->trimPrefix('field_')
        ->camel()
        ->toString();

      $form['settings'][$entity_type_id][$bundle_id]['_fields'][$field_id]['name_sdl'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Schema field name'),
        '#maxlength' => 255,
        '#size' => 20,
        '#default_value' => $settings['name_sdl'] ?? FALSE,
        '#description' => $this->t('Leave blank to use automatically generated name.'),
        '#element_validate' => ['::validateNameSdl'],
        '#placeholder' => $placeholder,
      ];
    }
  }

  /**
   * Callback for name sdl validation.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The form.
   */
  public static function validateNameSdl(array &$element, FormStateInterface &$form_state, array $form): void {
    $field_value = $element['#value'];

    if (!empty($field_value) && !preg_match('/^[a-z]([A-Za-z0-9]+)?$/', $field_value)) {
      $message = t('@name must start with a lowercase letter and contain only letters and numbers.', [
        '@name' => $element['#title'] ?? 'Field name',
      ]);
      $form_state->setError($element, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entity_config = $form_state->getValue(['settings', 'entity_config'], []);
    $field_config = $form_state->getValue(['settings', 'field_config'], []);

    self::sortAndFilterSettings($entity_config);
    self::sortAndFilterSettings($field_config);

    $this->getConfig()
      ->set('entity_config', $entity_config)
      ->set('field_config', $field_config)
      ->save();

    _graphql_compose_cache_flush();

    parent::submitForm($form, $form_state);
  }

  /**
   * Recursively sort and filter settings.
   *
   * @param array $settings
   *   The array to sort and filter.
   */
  public static function sortAndFilterSettings(array &$settings): void {

    ksort($settings);

    foreach ($settings as &$value) {
      if (is_string($value)) {
        $value = trim($value);
      }
      if (is_array($value)) {
        self::sortAndFilterSettings($value);
      }
    }

    $settings = array_filter($settings, fn ($value) => !empty($value));
  }

}
