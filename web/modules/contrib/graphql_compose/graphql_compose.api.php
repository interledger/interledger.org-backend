<?php

/**
 * @file
 * Hooks provided by GraphQL Compose module.
 */

/**
 * Alter the result from language singularize.
 *
 * @param string $original
 *   Original string to be converted.
 * @param array $singular
 *   Result from the language interface.
 */
function hook_graphql_compose_singularize_alter($original, array &$singular): void {
  if (preg_match('/media$/i', $original)) {
    $singular = [$original];
  }
}

/**
 * Alter the result from language pluralize.
 *
 * @param string $original
 *   Original string to be converted.
 * @param array $plural
 *   Result from the language interface.
 */
function hook_graphql_compose_pluralize_alter($original, array &$plural): void {
  if (preg_match('/media$/i', $original)) {
    $plural = [$original . 'Items'];
  }
}

/**
 * Change enabled state of a field.
 *
 * @param bool $enabled
 *   Field is enabled or not.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
 *   Fiel definition.
 */
function hook_graphql_compose_field_enabled_alter(
  bool &$enabled,
  \Drupal\Core\Field\FieldDefinitionInterface $field_definition
) {
  $entity_type = $field_definition->getTargetEntityTypeId();

  if ($entity_type === 'user' && $field_definition->getName() === 'mail') {
    $enabled = FALSE;
  }
}

/**
 * Alter results for producers which use FieldProducerPluginBase.
 *
 * @param array $results
 *   The results being returned.
 * @param array $context
 *   Context Passed to resolver. Eg $context['field'].
 * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
 *   Context for metadata expansion.
 */
function hook_graphql_compose_field_results_alter(
  array &$results,
  array $context,
  \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
) {
  $field = $context['value']->getFieldDefinition();
  if ($field->getName() === 'field_potato' && empty($results)) {
    $results = ['Chips'];
  }
}

/**
 * Alter defined interfaces on an entity type.
 *
 * @param array $interfaces
 *   Interfaces defined on entity type.
 * @param \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeInterface $plugin
 *   The current entity type being processed.
 */
function hook_graphql_compose_entity_interfaces_alter(
  array &$interfaces,
  \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeInterface $plugin
) {
  if ($plugin->getBaseId() === 'block') {
    $interfaces[] = 'TestBlocks';
  }
}

/**
 * Change enabled state of an entity plugin bundle.
 *
 * @param bool $enabled
 *   Field is enabled or not.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The current entity bundle being processed.
 */
function hook_graphql_compose_entity_bundle_enabled_alter(
  bool &$enabled,
  \Drupal\Core\Entity\EntityInterface $entity
) {
  if ($entity->id() === 'user') {
    $enabled = FALSE;
  }
}

/**
 * Alter defined base fields available to an entity type.
 *
 * @param array $fields
 *   Fields defined on entity type.
 * @param string $entity_type_id
 *   The current entity type being processed.
 */
function hook_graphql_compose_entity_base_fields_alter(array &$fields, string $entity_type_id) {
  if ($entity_type_id === 'user') {
    unset($fields['mail']);
  }
}

/**
 * Alter the entity type form GraphQL settings.
 *
 * @param array $form
 *   Drupal form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Drupal form state.
 * @param array $settings
 *   Current settings.
 */
function hook_graphql_compose_entity_type_form_alter(
  array &$form,
  \Drupal\Core\Form\FormStateInterface $form_state,
  array $settings,
) {
  // $entity_type = $form_state->get('entity_type');
  $form['graphql_compose']['my_setting'] = [
    'default_value' => $settings['my_setting'] ?? NULL,
  ];
}

/**
 * Operations to execute on save.
 *
 * @param array $form
 *   Drupal form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Drupal form state.
 * @param array $settings
 *   Settings to write to config.
 */
function hook_graphql_compose_entity_type_form_submit_alter(
  array $form,
  \Drupal\Core\Form\FormStateInterface $form_state,
  array &$settings,
) {
  $settings['my_setting'] = $form_state->getValue([
    'graphql_compose',
    'my_setting',
  ]);
}
