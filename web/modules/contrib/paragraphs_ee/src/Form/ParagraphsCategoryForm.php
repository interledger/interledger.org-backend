<?php

namespace Drupal\paragraphs_ee\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the add and edit forms of Paragraphs category entities.
 */
class ParagraphsCategoryForm extends EntityForm {

  /**
   * Constructs an ParagraphsCategoryForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
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
  public function form(array $form, FormStateInterface $form_state) {
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
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
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
  }

  /**
   * Helper function to check whether a Paragraphs category entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager
      ->getStorage('paragraphs_category')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
