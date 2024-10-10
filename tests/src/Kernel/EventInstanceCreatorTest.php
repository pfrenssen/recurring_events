<?php

declare(strict_types=1);

namespace Drupal\Tests\recurring_events\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\recurring_events\Traits\EventSeriesCreationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events\EventInstanceStorageInterface;

/**
 * Tests EventInstanceCreator plugins.
 *
 * @group recurring_events
 */
class EventInstanceCreatorTest extends KernelTestBase {

  use EventSeriesCreationTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'datetime_range',
    'field_inheritance',
    'options',
    'recurring_events',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('eventseries');
    $this->installEntitySchema('eventinstance');
    $this->installEntitySchema('user');
    $this->installConfig([
      'field_inheritance',
      'recurring_events',
      'system',
    ]);

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests the recurring_events_eventinstance_recreator event instance creator.
   *
   * @coversClass \Drupal\recurring_events\Plugin\EventInstanceCreator\RecreateEventInstanceCreator
   */
  public function testRecreateEventInstanceCreator(): void {
    // The plugin to recreate event instances should be the default.
    $config = $this->config('recurring_events.eventseries.config');
    $this->assertEquals('recurring_events_eventinstance_recreator', $config->get('creator_plugin'));

    // Create an event series and check that the expected event instances are
    // created.
    $series = $this->createEventSeries();
    $instances = $this->loadEventInstances($series);
    $this->assertCount(4, $instances, 'The expected number of event instances was created.');

    // Keep track of the event instance IDs and changed timestamps so we can
    // check when they are changed.
    $get_instance_data = static fn (EventInstance $instance): array => [$instance->id(), $instance->getChangedTime()];
    $original_instance_data = array_map($get_instance_data, $instances);

    // Update the event series without changing the date recurrence settings.
    // This should not trigger any change in the event instances.
    $series->save();

    // Check that the event instances remain unchanged.
    $instances = $this->loadEventInstances($series);
    $this->assertCount(4, $instances, 'The expected number of event instances was not changed.');
    $actual_instance_data = array_map($get_instance_data, $instances);
    $this->assertEquals($original_instance_data, $actual_instance_data, 'The event instances were not changed.');

    // Now modify the event series to extend the date range with one week.
    $series->weekly_recurring_date->end_value = (new DrupalDateTime('2024-06-16T23:59:59', 'UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $series->save();

    // Check that the event instances were recreated. There should also be one
    // additional event instance.
    $instances = $this->loadEventInstances($series);
    $this->assertCount(5, $instances, 'The expected number of event instances exist after recreation.');

    // Check that none of the original event instances were reused.
    $actual_instance_data = array_map($get_instance_data, $instances);
    $this->assertEmpty(array_intersect(array_column($original_instance_data, 0), array_column($actual_instance_data, 0)), 'The original event instances were not reused.');

    // Check that the original event instances were deleted.
    $original_instances = $this->getEventInstanceStorage()->loadMultiple(array_column($original_instance_data, 0));
    $this->assertEmpty($original_instances, 'The original event instances were deleted.');
  }

  /**
   * Tests the noop event instance creator.
   *
   * @coversClass \Drupal\recurring_events\Plugin\EventInstanceCreator\NoOperationEventInstanceCreator
   */
  public function testNoopEventInstanceCreator(): void {
    // Change the event instance creator plugin to the noop creator.
    $config = $this->config('recurring_events.eventseries.config');
    $config->set('creator_plugin', 'noop');
    $config->save();

    // Create an event series and check that the expected event instances are
    // created.
    $series = $this->createEventSeries();
    $instances = $this->loadEventInstances($series);
    $this->assertCount(4, $instances, 'The expected number of event instances was created.');

    // Keep track of the event instance IDs and changed timestamps so we can
    // check when they are changed.
    $get_instance_data = static fn (EventInstance $instance): array => [$instance->id(), $instance->getChangedTime()];
    $original_instance_data = array_map($get_instance_data, $instances);

    // Modify the event series to extend the date range with one week.
    $series->weekly_recurring_date->end_value = (new DrupalDateTime('2024-06-16T23:59:59', 'UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $series->save();

    // Check that the event instances were not touched.
    $instances = $this->loadEventInstances($series);
    $this->assertCount(4, $instances, 'The expected number of event instances was not changed.');
    $actual_instance_data = array_map($get_instance_data, $instances);
    $this->assertEquals($original_instance_data, $actual_instance_data, 'The event instances are not changed when using the noop event instance creator.');
  }

  /**
   * Returns the event instances for a given event series.
   *
   * This directly queries the database to avoid any caching issues.
   *
   * @param \Drupal\recurring_events\Entity\EventSeries $series
   *   The event series for which to load the event instances.
   *
   * @return \Drupal\recurring_events\Entity\EventInstance[]
   *   The event instances.
   */
  protected function loadEventInstances(EventSeries $series): array {
    $this->resetEventInstanceStorageCache();
    return $this->getEventInstanceStorage()->loadByProperties(['eventseries_id' => $series->id()]);
  }

  /**
   * Resets the cache for the event instance storage.
   */
  protected function resetEventInstanceStorageCache(): void {
    $this->getEventInstanceStorage()->resetCache();
  }

  /**
   * Returns the event instance storage.
   *
   * @return \Drupal\recurring_events\EventInstanceStorageInterface
   *   The event instance storage.
   */
  protected function getEventInstanceStorage(): EventInstanceStorageInterface {
    return $this->container->get('entity_type.manager')->getStorage('eventinstance');
  }

}
