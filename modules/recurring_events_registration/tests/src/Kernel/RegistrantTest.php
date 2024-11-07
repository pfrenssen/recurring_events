<?php

declare(strict_types=1);

namespace Drupal\Tests\recurring_events_registration\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
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
    'language',
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

    $this->installEntitySchema('configurable_language');
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

  /**
   * @covers ::getLangcode
   * @covers ::setLangcode
   */
  public function testLangcode(): void {
    // Enable the French language and configure it as the default language for
    // the default registrant bundle.
    ConfigurableLanguage::create([
      'id' => 'fr',
      'name' => 'French',
      'direction' => LanguageInterface::DIRECTION_LTR,
    ])->save();

    $configuration = ContentLanguageSettings::loadByEntityTypeBundle('registrant', 'default');
    $configuration->setDefaultLangcode('fr')->save();

    $registrant = Registrant::create();
    $this->assertEquals('fr', $registrant->getLangcode(), 'On a newly created registrant, the langcode is set to the default language configured in the bundle settings.');
    $this->assertEquals('fr', $registrant->get('langcode')->value, 'The langcode is stored in the langcode field.');

    $registrant->setLangcode('en');
    $this->assertEquals('en', $registrant->getLangcode(), 'The langcode can be set and retrieved.');
    $this->assertEquals('en', $registrant->get('langcode')->value, 'The langcode is stored in the langcode field.');

    $registrant->set('langcode', NULL);
    $this->assertEquals(LanguageInterface::LANGCODE_NOT_SPECIFIED, $registrant->getLangcode(), 'If the langcode field is not populated, the langcode is not specified.');
  }

}
