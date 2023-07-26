# Patching

Metatag (>=2.0.0) requires no patching.

Metatag 1.x should see https://www.drupal.org/project/metatag/issues/2945817 - Suggest downgrading to 1.23

```json
        "patches": {
            "drupal/metatag": {
                "2945817 Support JSON API, REST, GraphQL and custom normalizations via new computed field": "https://www.drupal.org/files/issues/2023-05-25/metatag-n2945817-176.patch"
            }
        }
```
