<?php

declare(strict_types=1);

namespace Drupal\Tests\recurring_events_registration\Kernel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events_registration\Controller\RegistrantController;
use Drupal\recurring_events_registration\Entity\Registrant;

/**
 * Tests the registrant controller.
 *
 * @coversDefaultClass \Drupal\recurring_events_registration\Controller\RegistrantController
 * @group recurring_events_registration
 */
class RegistrantControllerTest extends KernelTestBase {

  protected const TEST_LAST_NAME = 'Last name';
  protected const TEST_EMAIL = 'user@example.com';

  /**
   * The registrant controller. This is the system under test.
   */
  protected RegistrantController $registrantController;

  /**
   * The configuration factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime_range',
    'field',
    'field_inheritance',
    'options',
    'recurring_events',
    'recurring_events_registration',
    'system',
    'text',
    'token',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('eventinstance');
    $this->installEntitySchema('registrant');

    $this->installConfig(['field_inheritance', 'recurring_events_registration', 'system']);

    $this->registrantController = RegistrantController::create($this->container);
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Tests retrieving the page title.
   *
   * @covers ::getTitle
   */
  public function testGetTitle(): void {
    // Remove the optional first name field from the registrant entity type.
    // This is a regression test for a bug where retrieving the page title would
    // emit a PHP warning if the registrant did not have a first name field.
    $field_storage = FieldStorageConfig::loadByName('registrant', 'field_first_name');
    $field_storage->delete();

    // Create an event instance.
    $instance = EventInstance::create(['type' => 'event_instance']);
    $instance->save();

    // Create a registrant entity.
    $registrant = Registrant::create([
      'field_last_name' => self::TEST_LAST_NAME,
      'email' => self::TEST_EMAIL,
      'eventinstance_id' => $instance->id(),
    ]);

    // By default, the email address should be used as the title. We are testing
    // a clone of the registrant so we can do subsequent tests without having
    // the title cached on the entity.
    $this->assertEquals(self::TEST_EMAIL, $this->registrantController->getTitle(clone $registrant));

    // Change the configuration of the registrant title and check that it is
    // honored.
    $config = $this->configFactory->getEditable('recurring_events_registration.registrant.config');
    $config->set('title', '[registrant:field_last_name:value] ([registrant:email])');
    $config->save();

    $this->assertEquals(self::TEST_LAST_NAME . ' (' . self::TEST_EMAIL . ')', $this->registrantController->getTitle($registrant));
  }

}
