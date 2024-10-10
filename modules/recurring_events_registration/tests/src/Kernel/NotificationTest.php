<?php

declare(strict_types=1);

namespace Drupal\Tests\recurring_events_registration\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\recurring_events\Traits\EventSeriesCreationTrait;
use Drupal\Tests\recurring_events_registration\Traits\RegistrantCreationTrait;
use Drupal\recurring_events_registration\Entity\RegistrantType;
use Drupal\recurring_events_registration\Model\RegistrantTypeNotificationSetting;
use Drupal\recurring_events_registration\NotificationService;

/**
 * Tests the notifications sent during the event registration process.
 *
 * @group recurring_events_registration
 */
class NotificationTest extends KernelTestBase {

  use AssertMailTrait;
  use EventSeriesCreationTrait;
  use RegistrantCreationTrait;

  /**
   * The default configuration for notifications when the module is installed.
   */
  protected const DEFAULT_NOTIFICATION_CONFIGURATION = [
    'registration_notification' => [
      'enabled' => TRUE,
      'subject' => 'You\'ve Successfully Registered',
      'body' => "Your registration for the [eventinstance:title] [eventinstance:reg_type] was successful.\r\n\r\nModify your registration: [registrant:edit_url]\r\nDelete your registration: [registrant:delete_url]",
    ],
    'waitlist_notification' => [
      'enabled' => TRUE,
      'subject' => 'You\'ve Been Added To The Waitlist',
      'body' => "You have been added to the waitlist for the [eventinstance:title] [eventinstance:reg_type].\r\n\r\nModify your registration: [registrant:edit_url]\r\nDelete your registration: [registrant:delete_url]",
    ],
    'promotion_notification' => [
      'enabled' => TRUE,
      'subject' => 'You\'ve Been Added To The Registration List',
      'body' => "You have been promoted from the waitlist to the registration list for the [eventinstance:title] [eventinstance:reg_type].\r\n\r\nModify your registration: [registrant:edit_url]\r\nDelete your registration: [registrant:delete_url]",
    ],
    'instance_deletion_notification' => [
      'enabled' => TRUE,
      'subject' => 'An Event Has Been Deleted',
      'body' => 'Unfortunately, the [eventinstance:title] [eventinstance:reg_type] has been deleted. Your registration has been deleted.',
    ],
    'series_deletion_notification' => [
      'enabled' => TRUE,
      'subject' => 'An Event Series Has Been Deleted',
      'body' => 'Unfortunately, the [eventinstance:title] [eventinstance:reg_type] has been deleted. Your registration has been deleted.',
    ],
    'instance_modification_notification' => [
      'enabled' => TRUE,
      'subject' => 'An Event Has Been Modified',
      'body' => "The [eventinstance:title] [eventinstance:reg_type] has been modified, please check back for details.\r\n\r\nModify your registration: [registrant:edit_url]\r\nDelete your registration: [registrant:delete_url]",
    ],
    'series_modification_notification' => [
      'enabled' => TRUE,
      'subject' => 'An Event Series Has Been Modified',
      'body' => 'The [eventinstance:title] [eventinstance:reg_type] has been modified, and all instances have been removed, and your registration has been deleted.',
    ],
  ];

  /**
   * Configuration to test overridden notification settings.
   */
  protected const OVERRIDDEN_NOTIFICATION_CONFIGURATION = [
    // Overridden, enabled. Subject and body are different.
    'registration_notification' => [
      'overridden' => TRUE,
      'enabled' => TRUE,
      'subject' => 'Your group has been registered',
      'body' => 'Thank you [registrant:email] for registering your group for the event.',
    ],

    // Not overridden, disabled. Subject and body are different. The original
    // configuration should be used.
    'waitlist_notification' => [
      'overridden' => FALSE,
      'enabled' => FALSE,
      'subject' => 'Your group has been added to the waitlist',
      'body' => 'Your group has been added to the waitlist for the [eventinstance:title] [eventinstance:reg_type].',
    ],

    // Overridden, disabled. Subject and body are different. Notification should
    // not be sent.
    'promotion_notification' => [
      'overridden' => TRUE,
      'enabled' => FALSE,
      'subject' => 'Your group has been promoted',
      'body' => 'Your group has been promoted from the waitlist to the registration list for the [eventinstance:title] [eventinstance:reg_type].',
    ],

    // Not overridden, enabled. Subject and body are different. The original
    // configuration should be used.
    'instance_deletion_notification' => [
      'overridden' => FALSE,
      'enabled' => TRUE,
      'subject' => 'An Event Has Been Deleted',
      'body' => 'Unfortunately, the [eventinstance:title] [eventinstance:reg_type] has been deleted. Your registration has been deleted.',
    ],

    // Remaining notifications are not specified and should adhere to the fall
    // back behaviour: not overridden, using the default configuration.
  ];

  /**
   * The notification service.
   */
  protected NotificationService $notificationService;

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

    $this->notificationService = $this->container->get('recurring_events_registration.notification_service');
  }

  /**
   * Tests the configuration of notifications.
   *
   * It should be possible for a registrant type to override the default
   * notification settings.
   */
  public function testRegistrationNotificationConfiguration(): void {
    $config = $this->config('recurring_events_registration.registrant.config');
    $send_email = $config->get('email_notifications');
    $this->assertTrue($send_email, 'By default email notifications are enabled.');

    // Create a test registrant and check that the default notification
    // configuration is used.
    $registrant = $this->createRegistrant();
    $this->notificationService->setEntity($registrant);

    foreach (self::DEFAULT_NOTIFICATION_CONFIGURATION as $key => $configuration) {
      $this->notificationService->setKey($key);
      $this->assertEquals($configuration['enabled'], $this->notificationService->isEnabled(), sprintf('The %s notification is %s.', $key, $configuration['enabled'] ? 'enabled' : 'disabled'));
      $this->assertEquals($configuration['subject'], $this->notificationService->getSubject(), sprintf('The %s notification has the correct subject.', $key));
      $this->assertEquals($configuration['body'], $this->notificationService->getMessage(FALSE), sprintf('The %s notification has the correct body.', $key));
    }

    // Now apply the overridden test configuration.
    $overridden_configuration = array_map(fn (array $config) => new RegistrantTypeNotificationSetting($config), self::OVERRIDDEN_NOTIFICATION_CONFIGURATION);
    RegistrantType::load($registrant->bundle())
      ->setNotificationSettings($overridden_configuration)
      ->save();
    $this->notificationService->setEntity($registrant);

    // Check that the overridden notification settings are used.
    foreach (array_keys(self::DEFAULT_NOTIFICATION_CONFIGURATION) as $key) {
      $this->notificationService->setKey($key);
      // If there is no overridden configuration, we should fall back to the
      // default behaviour, which is to not override the notification settings.
      $configuration = new RegistrantTypeNotificationSetting(self::OVERRIDDEN_NOTIFICATION_CONFIGURATION[$key] ?? ['overridden' => FALSE]);

      // The notification should be enabled if it is not overridden or if it is
      // overridden and enabled.
      $expected_enabled = !$configuration->isOverridden() || $configuration->isEnabled();
      $this->assertEquals($expected_enabled, $this->notificationService->isEnabled(), sprintf('The %s notification is %s.', $key, $expected_enabled ? 'enabled' : 'disabled'));

      // The subject and body should be the overridden values if the
      // configuration is overridden, otherwise the default values should be
      // used.
      $expected_subject = $configuration->isOverridden() ? $configuration->getSubject() : self::DEFAULT_NOTIFICATION_CONFIGURATION[$key]['subject'];
      $this->assertEquals($expected_subject, $this->notificationService->getSubject(FALSE), sprintf('The %s notification has the correct subject.', $key));
      $expected_body = $configuration->isOverridden() ? $configuration->getBody() : self::DEFAULT_NOTIFICATION_CONFIGURATION[$key]['body'];
      $this->assertEquals($expected_body, $this->notificationService->getMessage(FALSE), sprintf('The %s notification has the correct body.', $key));
    }
  }

  /**
   * Checks that a notification is sent out when a registrant is created.
   */
  public function testRegistrationNotification(): void {
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
    $registrant = $this->createRegistrant([
      'bundle' => 'group',
      'email' => 'kris@example.com',
      'field_first_name' => 'Kris',
    ]);

    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));
    $mail = reset($mails);

    $this->assertMail('to', 'kris@example.com', 'The email was sent to the correct recipient.');
    $this->assertMail('subject', 'Your group has been registered', 'The email has the correct subject.');
    $this->assertMail('body', 'Thank you kris@example.com for registering your group for the event.' . PHP_EOL, 'The email has the correct body.');

    // Clear out the mail queue.
    $this->container->get('state')->set('system.test_mail_collector', []);

    // Configure the group registrant type to no longer send out a notification.
    $registrant_type->setNotificationSettings([
      'registration_notification' => new RegistrantTypeNotificationSetting([
        'overridden' => TRUE,
        'enabled' => FALSE,
      ]),
    ])->save();

    // Now no mail should be sent if we make a new registration.
    $registrant = $this->createRegistrant([
      'bundle' => 'group',
      'email' => 'kristof@example.com',
    ]);

    $mails = $this->getMails();
    $this->assertEmpty($mails);
  }

}
