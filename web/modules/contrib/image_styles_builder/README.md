# Image Styles Builder

The goal of this package is to enable the rapid creation of image styles by declaring them in a custom module file definition (YAML).

The primary aim of Image Styles Builder is to allow Frontend to declare many necessary image-styles and provide to Backend
developers a way to document and define those styles to be generated or flushed at any moment.

Image Styles Builder allows a developer to quickly create image styles in bulk, based on a defined file.

## Use Image Styles Builder if

- You need to generate Drupal Images Styles quickly **without** using the UI
- You **receive** a definition/lists of Images Styles to generate from another co-worker/team
- You have a **lot** of Images Styles to **generate at once**
- You want to be able to **generate** Images Styles and **rollback** them easily
- You want to be able to fetch a collection of Image Styles in Twig (must previously be defined within this module)

## Usage

### Generating & Rollback of Image Styles

1. Create your own custom module that will contain your image styles definition(s).

2. Create the derivative definition file `yourmodule.image_styles_builder_derivatives.yml`:

Example:

```yaml
default:
  id: default
  label: Default
  suffix: dft
  styles:
    9_2_64x14:
      effects:
        -
          type: 'scale_and_crop'
          width: 64
          height: 14
    9_2_640x142:
      effects:
        -
          type: 'scale_and_crop'
          data:
            width: 640
            height: 142
    9_2_640x142_webp:
      effects:
        -
          type: 'scale_and_crop'
          width: 640
          height: 142
        -
          type: 'image_convert'
          data:
            extension: 'webp'
    16_10_64x40:
      effects:
        -
          type: 'scale_and_crop'
          width: 64
          height: 40
    16_10_128x80:
      effects:
        -
          type: 'scale_and_crop'
          width: 128
          height: 80
        -
          type: 'image_scale'
          width: 64
    original_64:
      effects:
        -
          type: 'image_scale'
          width: 64
```

3. Run the generate command to build your image styles in bulk

  ```bash
  drush isb:gen
  ```

5. (optional) Export the new image styles configuration

6. (optional) Rollback previously generated Image styles with the flush command.

  ```bash
  drush isb:flush
  ```

### Fetch Image Styles in Twig

Once generated, you may want to fetch your image styles by definition. This is possible throughout the `isb_image_styles` Twig function.

Twig Example code

  ```bash
  {% styles = isb_image_styles('default') %}
  {{ styles|json_encode }}
  ```

## Exposed Drush Commands

This module is shipped with drush commands to assist you in your workflow.

### Generate Command

The Generate command will create new image styles (and skip existing ones) by discovering all the derivatives files (`*.image_styles_builder_derivatives.yml`)

  ```bash
  drush isb:gen
  ```

### Flush Command

The Flush command will delete every image styles that have been created during a previous `isb:gen`.
The discovery operation will be based on all the derivatives files (`*.image_styles_builder_derivatives.yml`):

  ```bash
  drush isb:flush
  ```

## Getting Started

We highly recommend you to install the module using `composer` as a dev dependency.

```bash
$ composer require --dev drupal/image_styles_builder
```

## Versions

Image Styles Builder is available for Drupal 9!

### Which version should I use?

| Drupal Core | Image Styles Builder |
|:-----------:|:--------------------:|
|     8.x     |          -           |
|     9.x     |        1.0.x         |
|    10.x     |        1.1.x         |

## Dependencies

The Drupal 9 & Drupal 10 version of Image Styles Builder requires nothing !
Feel free to use it.

Image Styles Builder requires PHP 7.4+ to works properly.

## Similar modules

* [Image Style Generate](https://www.drupal.org/project/image_style_generate) Image Style Generate allows a site administrator to quickly create image styles in bulk, based on a defined set of rules and patterns.

## Supporting organizations

This project is sponsored by [Antistatique](https://www.antistatique.net), a Swiss Web Agency.
Visit us at [www.antistatique.net](https://www.antistatique.net) or
[Contact us](mailto:info@antistatique.net).

## Credits

Image Styles Builder is currently maintained by [Kevin Wenger](https://github.com/wengerk). Thank you to all our wonderful [contributors](https://github.com/antistatique/drupal-image-styles-builder/contributors) too.

