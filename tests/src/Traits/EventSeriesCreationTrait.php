<?php

declare(strict_types=1);

namespace Drupal\Tests\recurring_events\Traits;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\TestTools\Random;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\recurring_events\Entity\EventSeries;

/**
 * Helper methods for creating event series entities.
 *
 * This trait is meant to be used only by test classes.
 */
trait EventSeriesCreationTrait {

  /**
   * Creates an event series.
   *
   * @param array $values
   *   Optional field values to set on the event series.
   *
   * @return \Drupal\recurring_events\Entity\EventSeries
   *   The event series entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When the creation of the event series entity failed.
   */
  protected function createEventSeries(array $values = []): EventSeries {
    // Provide default values for required fields.
    $values += [
      'name' => Random::string(),
      'status' => TRUE,
      'type' => 'default',
      'recur_type' => 'weekly_recurring_date',
    ];

    if ($values['recur_type'] === 'weekly_recurring_date') {
      $values += [
        'weekly_recurring_date' => [
          'value' => (new DrupalDateTime('2024-05-19T00:00:00', 'UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
          'end_value' => (new DrupalDateTime('2024-06-09T23:59:59', 'UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
          'time' => '03:00 pm',
          'duration' => '3600',
          'end_time' => '04:00 pm',
          'duration_or_end_time' => 'duration',
          'days' => 'sunday',
        ],
      ];
    }

    $series = EventSeries::create($values);
    $series->save();

    return $series;
  }

}
