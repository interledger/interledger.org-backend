<?php

namespace Drupal\config_pages\Form;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;

/**
 * Action on clear ConfigPage submit form.
 */
class ConfigPagesClearConfirmationForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_pages_clear_confirmation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete %id?', ['%id' => $this->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity_id = $this->id;
    $entity = ConfigPages::load($entity_id);

    return $entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Only do this if you are sure!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clear it Now!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entity_id = $this->id;
    $entity = ConfigPages::load($entity_id);

    $fields = $entity->getFieldDefinitions();
    foreach ($fields as $name => $field) {

      // Process only fields added from BO.
      if ($field instanceof FieldConfigInterface) {
        $entity->set($name, $field->getDefaultValue($entity));
      }
    }
    $entity->save();

    $form_state->setRedirectUrl(Url::fromRoute('config_pages.' . $entity->bundle()));
  }

}
