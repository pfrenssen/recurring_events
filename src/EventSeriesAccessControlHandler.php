<?php

namespace Drupal\recurring_events;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the eventseries entity.
 *
 * @see \Drupal\recurring_events\Entity\EventSeries.
 */
class EventSeriesAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view eventseries entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit eventseries entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete eventseries entity');

      case 'clone':
        return AccessResult::allowedIfHasPermission($account, 'clone eventseries entity');
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add eventseries entity');
  }

}