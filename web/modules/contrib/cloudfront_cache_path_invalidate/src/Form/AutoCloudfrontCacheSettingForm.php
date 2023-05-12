<?php

namespace Drupal\cloudfront_cache_path_invalidate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Configure Automatic Cloudfront Cache settings for this site.
 */
class AutoCloudfrontCacheSettingForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'cci_Cloudfront_auto_cache.settings';

  /**
   * Constructs a new Cloudfront Cache clear form object.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cci_auto_Cloudfront_cache_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $this->config(self::SETTINGS);
    // Gather the number of names in the form already.
    $group_counts = $form_state->get('ecgroupcounttemp');

    if ($group_counts === NULL) {
      $current_count = $values->get('ecgroupcount') ? $values->get('ecgroupcount') : 1;
      $form_state->set('ecgroupcounttemp', $current_count);
      $group_counts = $current_count;
    }

    $form['#tree'] = TRUE;
    $form['ec_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Automatic Cloudfront URl Invalidate Settings for hook_enity_*()'),
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    $entity_options = ['' => '--Select--'];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if (NULL === $entity_type->getBundleEntityType()) {
        $entity_options[$entity_type->id()] = $entity_type->getBundleLabel();
      }

    }

    // When the AJAX request occurs, this form will be build in order to process
    // form state before the AJAX callback is called. We can use this
    // opportunity to populate the form as we wish based on the changes to the
    // form that caused the AJAX request. If the user caused the AJAX request,
    // then it would have been setting a value for instrument_family_options.
    // So if there's a value in that dropdown before we build it here, we grab
    // it's value to help us build the specific instrument dropdown. Otherwise
    // we can just use the value of the first item as the default value.
    $selected_entity_type = '';
    for ($i = 0; $i < $group_counts; $i++) {
      $current_values = $form_state->getValue(['ec_fieldset']);
      $entity_type_values = $current_values ? array_column($current_values, 'ecentitytype') : [];
      $selected_entity_type = '';
      if (isset($entity_type_values[$i])) {
        $selected_entity_type = $entity_type_values[$i];
      }
      elseif (isset($values->get('ecentitytype')[$i])) {
        $selected_entity_type = $values->get('ecentitytype')[$i];
      }
      else {
        $selected_entity_type = key($entity_options);
      }

      $form['ec_fieldset'][$i]['ecentitytype'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Type'),
        '#options' => $entity_options,
        '#required' => TRUE,
        '#weight' => -1,
        '#default_value' => isset($values->get('ecentitytype')[$i]) ? $values->get('ecentitytype')[$i] : '',
        '#data' => $i,
        '#ajax' => [
          'callback' => '::entityTypeBundle',
          'wrapper' => 'entity-bundle-container_' . $i,
        ],
      ];

      $entity_bundle_options = ['' => '--Select--'];

      if ($selected_entity_type) {
        foreach ($this->entityTypeManager->getStorage($selected_entity_type)->loadMultiple() as $entity_bundle) {
          $entity_bundle_options[$entity_bundle->id()] = $entity_bundle->label();
        }
      }

      $form['ec_fieldset'][$i]['entity_bundle_container']['ecentitytypebundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Bundle'),
        '#options' => $entity_bundle_options,
        '#required' => TRUE,
        '#weight' => 2,
        '#prefix' => "<div id='entity-bundle-container_" . $i . "'>",
        '#suffix' => '</div>',
        '#default_value' => isset($values->get('ecentitytypebundle')[$i]) ? $values->get('ecentitytypebundle')[$i] : '',
      ];

      $form['ec_fieldset'][$i]['detail_page'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Detail Page'),
        '#default_value' => isset($values->get('detail_page')[$i]) ? $values->get('detail_page')[$i] : '',
        '#weight' => 3,
      ];

      $form['ec_fieldset'][$i]['ec_cloudfront_url'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Cloudfront URL to invalidate'),
        '#description' => $this->t('Specify the existing path you wish to invalidate. For example: /sector/*, /state/*. Enter one value per line'),
        '#placeholder' => 'Cloudfront URL',
        '#weight' => 4,
        '#default_value' => isset($values->get('ec_cloudfront_url')[$i]) ? $values->get('ec_cloudfront_url')[$i] : '',
      ];

    }

    $form['ec_fieldset']['actions'] = [
      '#type' => 'actions',
    ];
    $form['ec_fieldset']['actions']['add_more_ec'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add more'),
      '#submit' => [
        '::addMore',
      ],
      '#ajax' => [
        'callback' => '::addMoreCallback',
        'wrapper' => ['names-fieldset-wrapper'],
      ],
    ];

    // If there is more than one name, add the remove button.
    if ($group_counts >= 1) {
      $form['ec_fieldset']['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [
          '::removeCallback',
        ],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::addMoreCallback',
          'wrapper' => ['names-fieldset-wrapper', 'entity-bundle-container'],
        ],
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;

  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addMoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['ec_fieldset'];
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function entityTypeBundle(array &$form, FormStateInterface $form_state) {

    $triggerdElement = $form_state->getTriggeringElement();

    return $form['ec_fieldset'][$triggerdElement['#data']]['entity_bundle_container'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addMore(array &$form, FormStateInterface $form_state) {
    $add_button = $form_state->get('ecgroupcounttemp') + 1;
    $form_state->set('ecgroupcounttemp', $add_button);

    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('ecgroupcounttemp');

    if ($name_field >= 1) {
      $remove_button = $name_field - 1;
      $form_state->set('ecgroupcounttemp', $remove_button);
    }

    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fieldset_values = $form_state->getValue(['ec_fieldset']);
    // Get the URL.
    foreach (array_column($fieldset_values, 'ec_cloudfront_url') as $key => $value) {
      $url_value = explode("\n", $value);
      if (!empty($url_value) && is_array($url_value) && count($url_value) > 0) {
        foreach ($url_value as $value) {
          if (substr($value, 0, 1) != '/' && !empty($value)) {
            $form_state->setErrorByName('ec_fieldset[' . $key . '][ec_cloudfront_url]', $this->t('The Cloudfront URL is not valid.'));
          }
        }
      }
      else {
        $form_state->setErrorByName('ec_fieldset[' . $key . '][ec_cloudfront_url]', $this->t('The Cloudfront URL is not valid.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['ec_fieldset']);

    $values_ecentitytype = array_column($values, 'ecentitytype');
    $values_ecentitytypebundle = array_column(array_column($values, 'entity_bundle_container'), 'ecentitytypebundle');
    $values_detail_page = array_column($values, 'detail_page');
    $values_url = array_column($values, 'ec_cloudfront_url');

    $this->config(self::SETTINGS)
      ->set('ecentitytype', $values_ecentitytype)
      ->set('ecentitytypebundle', $values_ecentitytypebundle)
      ->set('detail_page', $values_detail_page)
      ->set('ec_cloudfront_url', $values_url)
      ->set('ecgroupcount', count($values))
      ->save();

  }

}
