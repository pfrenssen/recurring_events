<?php

namespace Drupal\recurring_events;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the event instance entity.
 *
 * @see \Drupal\recurring_events\Entity\EventInstance
 */
class EventInstanceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        $status = $entity->isPublished();
        if (!$status) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished eventinstance entity');
        }
        return AccessResult::allowedIfHasPermission($account, 'view eventinstance entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit eventinstance entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete eventinstance entity');

      case 'clone':
        return AccessResult::allowedIfHasPermission($account, 'clone eventinstance entity');

      case 'contact':
        return AccessResult::allowedIfHasPermission($account, 'contact eventinstance registration entities');
    }
    return AccessResult::allowed();
  }

}
