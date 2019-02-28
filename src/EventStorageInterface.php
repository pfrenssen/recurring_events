<?php

namespace Drupal\recurring_events;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for event entity storage classes.
 */
interface EventStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of event revision IDs for a specific event.
   *
   * @param \Drupal\recurring_events\EventInterface $event
   *   The event entity.
   *
   * @return int[]
   *   Event revision IDs (in ascending order).
   */
  public function revisionIds(EventInterface $event);

  /**
   * Gets a list of revision IDs having a given user as event author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Event revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\recurring_events\EventInterface $event
   *   The event entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(EventInterface $event);

  /**
   * Unsets the language for all events with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
