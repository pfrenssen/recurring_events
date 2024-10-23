<?php

declare(strict_types=1);

namespace Drupal\Tests\recurring_events\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\recurring_events\Traits\EventSeriesCreationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the translation of event series.
 *
 * @group recurring_events
 * @requires module field_inheritance
 */
class EventSeriesTranslationTest extends KernelTestBase {

  use EventSeriesCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'datetime_range',
    'field_inheritance',
    'language',
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

    $this->installEntitySchema('user');
    $this->installEntitySchema('eventseries');
    $this->installEntitySchema('eventinstance');
    $this->installConfig(['system']);

    ConfigurableLanguage::create(['id' => 'it', 'label' => 'Italian'])->save();
  }

  /**
   * Tests translating an event series with a modified event instance.
   */
  public function testTranslatingEventSeriesWithModifiedInstance() {
    // Create an event series.
    $series = $this->createEventSeries();

    // Modify the first event instance. We have invited a DJ for the grand
    // opening and want to extend the duration of the event the first day only.
    $instances = $series->getInstances();
    $instance = reset($instances);

    /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
    $end_date = $instance->get('date')->first()->get('end_date')->getValue();
    // Extend the end date by 2 hours. It's party time!
    $end_date->modify('+2 hours');
    $instance->get('date')->first()->set('end_value', $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    $instance->save();

    // Translate the event series.
    $translated_series = $series->addTranslation('it');
    $translated_series->save();

    // A translation should have been created for every event instance.
    $translated_instances = $translated_series->getInstances();
    $this->assertCount(4, $translated_instances);
    foreach ($translated_instances as $translated_instance) {
      $this->assertTrue($translated_instance->hasTranslation('it'));
    }
  }

}
