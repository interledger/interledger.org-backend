<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "address",
 *   type_sdl = "Address",
 * )
 */
class AddressItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface, ContainerFactoryPluginInterface {

  use FieldProducerTrait;

  /**
   * Address country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected CountryRepositoryInterface $countryRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->countryRepository = $container->get('address.country_repository');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata) {

    $countries = $this->countryRepository->getList();

    return [
      'langcode' => $item->langcode ?: NULL,
      'country'  => [
        'name' => $countries[$item->country_code] ?? $item->country_code ?: NULL,
        'code' => $item->country_code ?: NULL,
      ],
      'givenName' => $item->given_name ?: NULL,
      'additionalName' => $item->additional_name ?: NULL,
      'familyName' => $item->family_name ?: NULL,
      'organization' => $item->organization ?: NULL,
      'addressLine1' => $item->address_line1 ?: NULL,
      'addressLine2' => $item->address_line2 ?: NULL,
      'postalCode' => $item->postal_code ?: NULL,
      'sortingCode' => $item->sorting_code ?: NULL,
      'dependentLocality' => $item->dependent_locality ?: NULL,
      'locality' => $item->locality ?: NULL,
      'administrativeArea' => $item->administrative_area ?: NULL,
    ];
  }

}
