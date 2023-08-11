<?php

/**
 * @file
 * Hooks provided by GraphQL Compose module.
 */

/**
 * Add custom types to the schema.
 *
 * @param Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $manager
 *   The GraphQL Compose Schema Type Manager.
 */
function hook_graphql_compose_print_types(\Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $manager): void {
  $my_type = new \GraphQL\Type\Definition\ObjectType([
    'name' => 'MyType',
    'fields' => [
      'id' => \GraphQL\Type\Definition\Type::string(),
    ],
  ]);
  $manager->add($my_type);
}

/**
 * Add extensions to the schema.
 *
 * @param Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $manager
 *   The GraphQL Compose Schema Type Manager.
 */
function hook_graphql_compose_print_extensions(\Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $manager): void {
  $my_extension = new \GraphQL\Type\Definition\ObjectType([
    'name' => 'Query',
    'fields' => fn() => [
      'thing' => [
        'type' => $manager->get('MyType'),
        'description' => (string) t('Get my type'),
      ],
    ],
  ]);
  $manager->extend($my_extension);
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
  // Ends in z,s,x. (Eg termTags)
  if (preg_match('/[sxz]$/', $original)) {
    // Eg Query termTagss becomes termTagItems.
    $plural = [
      rtrim($original, 'sxz') . t('Items'),
    ];
  }

}

/**
 * Change enabled state of a field.
 *
 * @param bool $enabled
 *   Field is enabled or not.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
 *   Field definition.
 */
function hook_graphql_compose_field_enabled_alter(bool &$enabled, \Drupal\Core\Field\FieldDefinitionInterface $field_definition) {
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
 *   Context Passed to resolver. Eg $context['value'].
 * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
 *   Context for metadata expansion.
 */
function hook_graphql_compose_field_results_alter(array &$results, array $context, \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata) {
  $field_list = $context['value'] ?? NULL;
  if (!$field_list instanceof \Drupal\Core\Field\FieldItemListInterface) {
    return;
  }

  $entity = $field_list->getEntity();
  $field = $field_list->getFieldDefinition();

  if ($entity->getEntityTypeId() === 'node' && $field->getName() === 'field_potato') {
    $results = ['new node value for field_potato'];
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
function hook_graphql_compose_entity_interfaces_alter(array &$interfaces, \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeInterface $plugin) {
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
function hook_graphql_compose_entity_bundle_enabled_alter(bool &$enabled, \Drupal\Core\Entity\EntityInterface $entity) {
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
 * Note: You should hook alter the config schema if you edit this.
 * Alter config schema using hook_config_schema_info_alter().
 * See graphql_compose_routes.module for an example.
 *
 * @param array $form
 *   Drupal form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Drupal form state.
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   Current entity type.
 * @param string $bundle_id
 *   Current entity bundle id.
 * @param array $settings
 *   Current settings.
 */
function hook_graphql_compose_entity_type_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state, \Drupal\Core\Entity\EntityTypeInterface $entity_type, string $bundle_id, array $settings) {
  $form['my_setting'] = [
    '#default_value' => $settings['my_setting'] ?? NULL,
  ];
}

/**
 * Alter the field type form GraphQL settings.
 *
 * Note: You should hook alter the config schema if you edit this.
 * Alter config schema using hook_config_schema_info_alter().
 * See graphql_compose_views.module for an example.
 *
 * @param array $form
 *   Drupal form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Drupal form state.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field
 *   Current field type.
 * @param array $settings
 *   Current settings.
 */
function hook_graphql_compose_field_type_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state, \Drupal\Core\Field\FieldDefinitionInterface $field, array $settings) {
  $form['my_setting'] = [
    '#default_value' => $settings['my_setting'] ?? NULL,
  ];
}
