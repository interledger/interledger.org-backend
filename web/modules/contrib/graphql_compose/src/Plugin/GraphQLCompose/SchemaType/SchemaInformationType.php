<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "SchemaInformation",
 * )
 */
class SchemaInformationType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Schema information provided by the system.'),
      'fields' => function () {
        $settings = $this->configFactory->get('graphql_compose.settings');

        // Add schema information.
        $fields = [
          'version' => [
            'type' => Type::string(),
            'description' => (string) $this->t('The schema version.'),
          ],
          'description' => [
            'type' => Type::string(),
            'description' => (string) $this->t('The schema description.'),
          ],
        ];

        // Add site information.
        if ($settings->get('settings.site_name')) {
          $fields['name'] = [
            'type' => Type::string(),
            'description' => (string) $this->t('The site name.'),
          ];
        }

        if ($settings->get('settings.site_slogan')) {
          $fields['slogan'] = [
            'type' => Type::string(),
            'description' => (string) $this->t('The site slogan.'),
          ];
        }

        if ($settings->get('settings.site_front')) {
          $fields['home'] = [
            'type' => Type::string(),
            'description' => (string) $this->t('The internal path to the front page.'),
          ];
        }

        // Add user defined settings.
        $custom_types = [];
        $custom_settings = $settings->get('settings.custom') ?: [];

        foreach ($custom_settings as $setting) {
          if (array_key_exists($setting['name'], $custom_types)) {
            $custom_types[$setting['name']]['multiple'] = TRUE;
            continue;
          }
          $custom_types[$setting['name']] = $setting;
        }

        foreach ($custom_types as $setting) {
          $fields[$setting['name']] = [
            'type' => static::type(
              $setting['type'],
              $setting['multiple'] ?? FALSE,
              TRUE
            ),
            'description' => $setting['description'],
          ];
        }

        ksort($fields);

        return $fields;
      },
    ]);

    return $types;
  }

}
