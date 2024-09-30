<?php

declare(strict_types=1);

namespace Drupal\Tests\recurring_events_registration\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\recurring_events_registration\Entity\Registrant;
use Drupal\recurring_events_registration\Entity\RegistrantType;
use Drupal\recurring_events_registration\Model\RegistrantTypeNotificationSetting;
use Drupal\Tests\recurring_events\Traits\EventSeriesCreationTrait;

/**
 * Tests the notifications sent during the event registration process.
 *
 * @group recurring_events_registration
 */
class NotificationTest extends KernelTestBase {

  use AssertMailTrait;
  use EventSeriesCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'datetime_range',
    'field',
    'field_inheritance',
    'options',
    'recurring_events',
    'recurring_events_registration',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('eventinstance');
    $this->installEntitySchema('eventseries');
    $this->installEntitySchema('registrant');
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'recurring_events_registration']);
  }

  /**
   * Tests the `registration_notification` notification.
   */
  public function testRegistrationNotification(): void {
    $config = \Drupal::config('recurring_events_registration.registrant.config');
    $send_email = $config->get('email_notifications');
    $this->assertTrue($send_email);
    $send_email_key = $config->get('notifications.registration_notification.enabled');
    $this->assertTrue($send_email_key);
    $event_series = $this->createEventSeries();

    // Create a registrant type 'group' which overrides the registration
    // notification.
    $registrant_type = RegistrantType::create([
      'id' => 'group',
      'label' => 'Group',
    ]);
    $registrant_type->setNotificationSettings([
      'registration_notification' => new RegistrantTypeNotificationSetting([
        'overridden' => TRUE,
        'enabled' => TRUE,
        'subject' => 'Your group has been registered',
        'body' => 'Thank you [registrant:email] for registering your group for the event.',
      ]),
    ]);
    $registrant_type->save();

    // Create a registrant. This should send out a notification.
    $registrant = Registrant::create([
      'bundle' => 'group',
      'eventseries_id' => $event_series->id(),
      'type' => 'series',
      'email' => 'kris@example.com',
      'field_first_name' => 'Kris',
      'status' => TRUE,
    ]);
    $registrant->save();

    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));
    $mail = reset($mails);

    $this->assertMail('to', 'kris@example.com', 'The email was sent to the correct recipient.');
    $this->assertMail('subject', 'Your group has been registered', 'The email has the correct subject.');
    $this->assertMail('body', 'Thank you kris@example.com for registering your group for the event.' . PHP_EOL, 'The email has the correct body.');
  }

}
