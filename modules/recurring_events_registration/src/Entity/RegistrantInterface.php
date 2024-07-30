<?php

namespace Drupal\recurring_events_registration\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Registrant entities.
 *
 * @ingroup recurring_events_registration
 */
interface RegistrantInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Registrant creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Registrant.
   */
  public function getCreatedTime();

  /**
   * Sets the Registrant creation timestamp.
   *
   * @param int $timestamp
   *   The Registrant creation timestamp.
   *
   * @return \Drupal\recurring_events_registration\Entity\RegistrantInterface
   *   The called Registrant entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the event instance.
   *
   * @return \Drupal\recurring_events\Entity\EventInstance|null
   *   The eventinstance entity, or NULL if no event instance has been set yet.
   */
  public function getEventInstance(): ?EventInstance;

  /**
   * Returns the event series.
   *
   * @return \Drupal\recurring_events\Entity\EventSeries|null
   *   The event series entity, or NULL if no event series has been set.
   */
  public function getEventSeries(): ?EventSeries;

}
