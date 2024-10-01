<?php

declare(strict_types=1);

namespace Drupal\Tests\recurring_events_registration\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events_registration\Entity\Registrant;
use Drupal\recurring_events_registration\Enum\RegistrationType;

/**
 * Tests the Registrant entity.
 *
 * @coversDefaultClass \Drupal\recurring_events_registration\Entity\Registrant
 * @group recurring_events_registration
 */
class RegistrantTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'datetime_range',
    'field_inheritance',
    'options',
    'recurring_events',
    'recurring_events_registration',
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
    $this->installEntitySchema('registrant');
    $this->installEntitySchema('user');

    $this->installConfig(['field_inheritance', 'recurring_events']);
  }

  /**
   * @covers ::getCacheTagsToInvalidate
   * @dataProvider providerTestGetCacheTagsToInvalidate
   */
  public function testGetCacheTagsToInvalidate(bool $referenceEventInstance, bool $referenceEventSeries, bool $save): void {
    $expected = [];

    $registrant = Registrant::create();

    if ($referenceEventSeries || $referenceEventInstance) {
      // Create an event series if the test requires it. Also needed if we
      // reference an event instance, since every instance belongs to a series.
      $eventSeries = EventSeries::create([
        'title' => $this->randomMachineName(),
        'recur_type' => 'custom',
        'type' => 'default',
      ]);
      $eventSeries->save();

      // Reference an event series if the test requires it.
      if ($referenceEventSeries) {
        $registrant->setRegistrationType(RegistrationType::SERIES);
        $registrant->setEventSeries($eventSeries);

        $expected[] = 'eventseries:' . $eventSeries->id();
      }

      // Reference an event instance if the test requires it.
      if ($referenceEventInstance) {
        $eventInstance = EventInstance::create([
          'title' => $this->randomMachineName(),
          'eventseries_id' => $eventSeries->id(),
          'type' => 'default',
        ]);
        $eventInstance->save();

        $registrant->setRegistrationType(RegistrationType::INSTANCE);
        $registrant->setEventInstance($eventInstance);

        $expected[] = 'eventinstance:' . $eventInstance->id();
      }
    }

    if ($save) {
      $registrant->save();
      $expected[] = 'registrant:' . $registrant->id();
    }

    $actual = $registrant->getCacheTagsToInvalidate();
    sort($actual);
    sort($expected);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testGetCacheTagsToInvalidate().
   */
  public function providerTestGetCacheTagsToInvalidate(): array {
    return [
      'newly created, unsaved registrant' => [
        'referenceEventInstance' => FALSE,
        'referenceEventSeries' => FALSE,
        'save' => FALSE,
      ],
      'newly created, unsaved registrant with event series' => [
        'referenceEventInstance' => FALSE,
        'referenceEventSeries' => TRUE,
        'save' => FALSE,
      ],
      'newly created, unsaved registrant with event instance' => [
        'referenceEventInstance' => TRUE,
        'referenceEventSeries' => FALSE,
        'save' => FALSE,
      ],
      'newly created, unsaved registrant with event series and instance' => [
        'referenceEventInstance' => TRUE,
        'referenceEventSeries' => TRUE,
        'save' => FALSE,
      ],
      'newly created, saved registrant' => [
        'referenceEventInstance' => FALSE,
        'referenceEventSeries' => FALSE,
        'save' => TRUE,
      ],
      'newly created, saved registrant with event series' => [
        'referenceEventInstance' => FALSE,
        'referenceEventSeries' => TRUE,
        'save' => TRUE,
      ],
      'newly created, saved registrant with event instance' => [
        'referenceEventInstance' => TRUE,
        'referenceEventSeries' => FALSE,
        'save' => TRUE,
      ],
      'newly created, saved registrant with event series and instance' => [
        'referenceEventInstance' => TRUE,
        'referenceEventSeries' => TRUE,
        'save' => TRUE,
      ],
    ];
  }

}
