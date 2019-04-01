<?php

namespace Drupal\recurring_events_registration;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Registrant entities.
 *
 * @ingroup recurring_events_registration
 */
class RegistrantListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Registrant ID');
    $header['firstname'] = $this->t('First Name');
    $header['lastname'] = $this->t('Last Name');
    $header['email'] = $this->t('Email');
    $header['phone'] = $this->t('Phone');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\recurring_events_registration\Entity\Registrant */
    $row['id'] = $entity->id();
    $row['firstname'] = $entity->get('field_first_name')->value;
    $row['lastname'] = $entity->get('field_last_name')->value;
    $row['email'] = $entity->get('field_email')->value;
    $row['phone'] = $entity->get('field_phone')->value;
    return $row + parent::buildRow($entity);
  }

}
