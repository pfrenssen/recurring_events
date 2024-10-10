<?php

declare(strict_types=1);

namespace Drupal\recurring_events_registration\Model;

/**
 * Value object containing the settings of a notification on a registrant type.
 */
class RegistrantTypeNotificationSetting {

  /**
   * Whether the global notification settings are being overridden.
   */
  protected bool $overridden = FALSE;

  /**
   * Whether the notification is enabled.
   */
  protected bool $enabled = TRUE;

  /**
   * The subject of the notification.
   */
  protected string $subject = '';

  /**
   * The body of the notification.
   */
  protected string $body = '';

  /**
   * Constructs a new RegistrantTypeNotificationSetting object.
   *
   * @param array $values
   *   An array of values to set on the object.
   */
  public function __construct(array $values = []) {
    foreach ($values as $key => $value) {
      if (!property_exists($this, $key)) {
        throw new \InvalidArgumentException(sprintf('Unknown property: %s', $key));
      }
      $this->$key = $value;
    }
  }

  /**
   * Returns whether the global notification settings are being overridden.
   *
   * @return bool
   *   When FALSE the global settings as defined in the registrant settings form
   *   are used. When TRUE the settings in this object are used.
   */
  public function isOverridden(): bool {
    return $this->overridden;
  }

  /**
   * Returns whether the notification is enabled.
   *
   * @return bool
   *   Whether the notification is enabled.
   */
  public function isEnabled(): bool {
    return $this->enabled;
  }

  /**
   * Returns the subject of the notification.
   *
   * @return string
   *   The subject of the notification.
   */
  public function getSubject(): string {
    return $this->subject;
  }

  /**
   * Returns the body of the notification.
   *
   * @return string
   *   The body of the notification.
   */
  public function getBody(): string {
    return $this->body;
  }

  /**
   * Returns the settings as an array.
   *
   * @return array
   *   An array of settings.
   */
  public function toArray(): array {
    return [
      'overridden' => $this->overridden,
      'enabled' => $this->enabled,
      'subject' => $this->subject,
      'body' => $this->body,
    ];
  }

}
