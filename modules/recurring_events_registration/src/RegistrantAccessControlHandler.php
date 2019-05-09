<?php

namespace Drupal\recurring_events_registration;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Registrant entity.
 *
 * @see \Drupal\recurring_events_registration\Entity\Registrant.
 */
class RegistrantAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\recurring_events_registration\Entity\RegistrantInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view registrant entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit registrant entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete registrant entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $params = \Drupal::request()->attributes->all();
    if (!empty($params['eventinstance'])) {
      $service = \Drupal::service('recurring_events_registration.creation_service');
      $service->setEventInstance($params['eventinstance']);
      if ($service->hasRegistration()) {
        return AccessResult::allowedIfHasPermission($account, 'add registrant entities');
      }
    }

    return AccessResult::neutral();
  }

}
