<?php

namespace Drupal\recurring_events_registration;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Defines a class to build a listing of Registrant entities.
 *
 * @ingroup recurring_events_registration
 */
class RegistrantListBuilder extends EntityListBuilder {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Constructs a new EventInstanceListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ConfigFactory $config) {
    parent::__construct($entity_type, $storage);
    $this->config = $config;

    $config = $this->config->get('recurring_events_registration.registrant.config');
    $this->limit = $config->get('limit');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Registrant ID');
    foreach ($this->getCustomFields() as $machine_name => $field) {
      $header[$machine_name] = $field;
    }
    $header['email'] = $this->t('Email');
    $header['waitlist'] = $this->t('Waitlist');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\recurring_events_registration\Entity\Registrant */
    $row['id'] = $entity->id();
    foreach ($this->getCustomFields() as $machine_name => $field) {
      $row[$machine_name] = $entity->get($machine_name)->value;
    }
    $row['email'] = $entity->get('email')->value;
    $row['waitlist'] = $entity->get('waitlist')->value ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

  /**
   * Get custom fields.
   *
   * @return array
   *   An array of custom fields.
   */
  protected function getCustomFields() {
    $custom_fields = [];
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('registrant', 'registrant');
    foreach ($fields as $machine_name => $field) {
      if (strpos($machine_name, 'field_') === 0) {
        $custom_fields[$machine_name] = $field->label();
      }
    }
    return $custom_fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('changed', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
