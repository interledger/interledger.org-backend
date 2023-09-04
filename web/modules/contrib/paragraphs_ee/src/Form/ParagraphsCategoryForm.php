<?php

namespace Drupal\paragraphs_ee\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the add and edit forms of Paragraphs category entities.
 */
class ParagraphsCategoryForm extends EntityForm implements ParagraphsCategoryFormInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ParagraphsCategoryFormInterface {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static($entity_type_manager);
  }

  /**
   * Gets the actual form array to be built.
   *
   * @param array<mixed> $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array<mixed>
   *   The form build array.
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\paragraphs_ee\ParagraphsCategoryInterface $paragraphs_category */
    $paragraphs_category = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $paragraphs_category->label(),
      '#description' => $this->t("Label for the Paragraphs category."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $paragraphs_category->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$paragraphs_category->isNew(),
      '#element_validate' => [[$this, 'validateMachineName']],
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#rows' => 3,
      '#default_value' => $paragraphs_category->getDescription(),
      '#description' => $this->t("Description for the Paragraphs category."),
      '#required' => FALSE,
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $paragraphs_category->getWeight(),
    ];

    return $form;
  }

  /**
   * Form submission handler for the 'save' action.
   *
   * Normally this method should be overridden to provide specific messages to
   * the user and redirect the form after the entity has been saved.
   *
   * @param array<mixed> $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\paragraphs_ee\ParagraphsCategoryInterface $paragraphs_category */
    $paragraphs_category = $this->entity;
    $status = $paragraphs_category->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label Paragraphs category.', [
        '%label' => $paragraphs_category->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label Paragraphs category was not saved.', [
        '%label' => $paragraphs_category->label(),
      ]));
    }

    $form_state->setRedirect('entity.paragraphs_category.collection');
    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function exist(string $id): bool {
    $entity = $this->entityTypeManager
      ->getStorage('paragraphs_category')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateMachineName(array $element, FormStateInterface $form_state): void {
    $reserved_names = [
      'uncategorized',
    ];
    if (in_array($form_state->getValue('id'), $reserved_names)) {
      $form_state->setError($element, $this->t('The machine name you entered is reserved. Please use another one.'));
    }
  }

}
