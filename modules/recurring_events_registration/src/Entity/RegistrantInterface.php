<?php

namespace Drupal\recurring_events_registration\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Registrant entities.
 *
 * @ingroup recurring_events_registration
 */
interface RegistrantInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Registrant name.
   *
   * @return string
   *   Name of the Registrant.
   */
  public function getName();

  /**
   * Sets the Registrant name.
   *
   * @param string $name
   *   The Registrant name.
   *
   * @return \Drupal\recurring_events_registration\Entity\RegistrantInterface
   *   The called Registrant entity.
   */
  public function setName($name);

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
   * Returns the Registrant published status indicator.
   *
   * Unpublished Registrant are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Registrant is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Registrant.
   *
   * @param bool $published
   *   TRUE to set this Registrant to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\recurring_events_registration\Entity\RegistrantInterface
   *   The called Registrant entity.
   */
  public function setPublished($published);

}
