<?php

declare(strict_types=1);

namespace Drupal\Tests\recurring_events_registration\Traits;

use Drupal\Tests\recurring_events\Traits\EventSeriesCreationTrait;
use Drupal\recurring_events_registration\Entity\Registrant;

/**
 * Helper methods for creating registrant entities.
 *
 * This trait is meant to be used only by test classes.
 */
trait RegistrantCreationTrait {

  use EventSeriesCreationTrait;

  /**
   * Creates a registrant.
   *
   * @param array $values
   *   Optional field values to set on the registrant.
   *
   * @return \Drupal\recurring_events_registration\Entity\Registrant
   *   The registrant entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When the creation of the registrant entity failed.
   */
  protected function createRegistrant(array $values = []): Registrant {
    // Provide default values for required fields.
    $values += [
      'bundle' => 'default',
      'email' => 'user@example.com',
      'type' => 'series',
      'status' => TRUE,
    ];

    // If the registrant is using the series type, create an event series if
    // one is not provided.
    if ($values['type'] === 'series' && !isset($values['eventseries_id'])) {
      $event_series = $this->createEventSeries();
      $values['eventseries_id'] = $event_series->id();
    }

    $registrant = Registrant::create($values);
    $registrant->save();

    return $registrant;
  }

}
