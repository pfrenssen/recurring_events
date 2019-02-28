<?php

namespace Drupal\recurring_events;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the storage handler class for events.
 *
 * This extends the base storage class, adding required special handling for
 * event entities.
 */
class EventStorage extends SqlContentEntityStorage implements EventStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EventInterface $event) {
    return $this->database->query(
      'SELECT vid FROM {event_revision} WHERE id=:id ORDER BY vid',
      [':id' => $event->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {event_revision} WHERE user_id = :user_id ORDER BY vid',
      [':user_id' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(EventInterface $event) {
    return $this->database->query('SELECT COUNT(*) FROM {event_revision} WHERE id = :id', [':id' => $event->id()])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('event_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
