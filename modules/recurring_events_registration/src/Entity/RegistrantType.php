<?php

declare(strict_types=1);

namespace Drupal\recurring_events_registration\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\recurring_events_registration\Model\RegistrantTypeNotificationSetting;

/**
 * Defines the registrant type entity.
 *
 * @ConfigEntityType(
 *   id = "registrant_type",
 *   label = @Translation("registrant type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\recurring_events_registration\RegistrantTypeListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\recurring_events_registration\Form\RegistrantTypeForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\recurring_events_registration\RegistrantTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "registrant_type",
 *   bundle_of = "registrant",
 *   admin_permission = "administer registrant entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/events/registrants/types/registrant_type/{registrant_type}",
 *     "edit-form" = "/admin/structure/events/registrants/types/registrant_type/{registrant_type}/edit",
 *     "collection" = "/admin/structure/events/registrants/types/registrant_type"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *     "notifications"
 *   }
 * )
 */
class RegistrantType extends ConfigEntityBundleBase implements RegistrantTypeInterface {

  /**
   * The registrant type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The registrant type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Registrant type.
   *
   * @var string
   */
  protected $description;

  /**
   * The notifications settings for this registrant type.
   *
   * @var array
   */
  protected array $notifications = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationSettings(): array {
    $notification_types = [];
    \Drupal::moduleHandler()->alter('recurring_events_registration_notification_types', $notification_types);

    $notification_settings = [];
    foreach (array_keys($notification_types) as $type) {
      $notification_settings[$type] = new RegistrantTypeNotificationSetting($this->notifications[$type] ?? []);
    }

    return $notification_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotificationSettings(array $notification_settings): RegistrantTypeInterface {
    $this->notifications = array_map(fn (RegistrantTypeNotificationSetting $notification_setting) => $notification_setting->toArray(), $notification_settings);

    return $this;
  }

}
