<?php

namespace Drupal\recurring_events_registration;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

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
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\recurring_events_registration\Entity\Registrant */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.registrant.edit_form',
      ['registrant' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
