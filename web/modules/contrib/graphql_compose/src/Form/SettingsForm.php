<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure GraphQL Compose settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graphql_compose_settings';
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

    $form['info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Schema information'),
      '#description' => $this->t('Add schema information to the <em>info</em> query.'),
    ];

    $form['info']['schema_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Schema version'),
      '#default_value' => $this->getConfig()->get('settings.schema_version'),
      '#size' => 10,
    ];

    $form['info']['schema_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Schema description'),
      '#default_value' => $this->getConfig()->get('settings.schema_description'),
      '#maxlength' => 255,
    ];

    $form['site'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Site information'),
      '#description' => $this->t('Add site information to the <em>info</em> query.'),
    ];

    $form['site']['site_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add site name'),
      '#default_value' => $this->getConfig()->get('settings.site_name'),
    ];

    $form['site']['site_slogan'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add site slogan'),
      '#default_value' => $this->getConfig()->get('settings.site_slogan'),
    ];

    $form['site']['site_front'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add home path'),
      '#default_value' => $this->getConfig()->get('settings.site_front'),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
    ];

    $form['advanced']['expose_entity_ids'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose entity IDs'),
      '#description' => $this->t('You schema will always have UUIDs enabled. Leaving this disabled can help protect your schema against enumeration attacks. Exposing will allow loading entities by numeric id.'),
      '#default_value' => $this->getConfig()->get('settings.expose_entity_ids'),
    ];

    $form['advanced']['simple_unions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Simple entity type unions'),
      '#description' => $this->t('Enable to reduce the amount of unions in your schema. This can help simplify your schema depending on your use case. Disabling this option will split up entity references into separate types per field.'),
      '#default_value' => $this->getConfig()->get('settings.simple_unions'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->getConfig()
      ->set('settings.expose_entity_ids', $form_state->getValue('expose_entity_ids'))
      ->set('settings.schema_description', $form_state->getValue('schema_description'))
      ->set('settings.schema_version', $form_state->getValue('schema_version'))
      ->set('settings.simple_unions', $form_state->getValue('simple_unions'))
      ->set('settings.site_front', $form_state->getValue('site_front'))
      ->set('settings.site_name', $form_state->getValue('site_name'))
      ->set('settings.site_slogan', $form_state->getValue('site_slogan'))
      ->save();

    _graphql_compose_cache_flush();

    parent::submitForm($form, $form_state);
  }

}
