<?php

namespace Drupal\recurring_events_registration\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Registrant type entities.
 */
interface RegistrantTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this Registrant type.
   */
  public function getDescription();

  /**
   * Returns the list of notification settings.
   *
   * @return \Drupal\recurring_events_registration\Model\RegistrantTypeNotificationSetting[]
   *   An array of notification settings.
   */
  public function getNotificationSettings(): array;

  /**
   * Sets the notification settings.
   *
   * @param \Drupal\recurring_events_registration\Model\RegistrantTypeNotificationSetting[] $notification_settings
   *   An array of notification settings.
   *
   * @return $this
   *   The entity, for chaining.
   */
  public function setNotificationSettings(array $notification_settings): self;

}
