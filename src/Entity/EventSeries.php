<?php

namespace Drupal\recurring_events\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\recurring_events\EventInterface;
use Drupal\recurring_events\EventUserTrait;

/**
 * Defines the Event Series entity.
 *
 * @ingroup recurring_events
 *
 * This is the main definition of the entity type. From it, an entityType is
 * derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "eventseries",
 *   label = @Translation("Event series entity"),
 *   handlers = {
 *     "storage" = "Drupal\recurring_events\EventSeriesStorage",
 *     "list_builder" = "Drupal\recurring_events\EventSeriesListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "translation" = "Drupal\recurring_events\EventSeriesTranslationHandler",
 *     "form" = {
 *       "add" = "Drupal\recurring_events\Form\EventSeriesForm",
 *       "edit" = "Drupal\recurring_events\Form\EventSeriesForm",
 *       "delete" = "Drupal\recurring_events\Form\EventSeriesDeleteForm",
 *       "clone" = "Drupal\recurring_events\Form\EventSeriesCloneForm",
 *     },
 *     "access" = "Drupal\recurring_events\EventSeriesAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\recurring_events\EventSeriesHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "eventseries",
 *   data_table = "eventseries_field_data",
 *   revision_table = "eventseries_revision",
 *   revision_data_table = "eventseries_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer eventseries entity",
 *   fieldable = TRUE,
 *   bundle_entity_type = "eventseries_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "published" = "status",
 *     "langcode" = "langcode",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "bundle" = "type",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/events/series/{eventseries}",
 *     "add-page" = "/events/add",
 *     "add-form" = "/events/add/{eventseries_type}",
 *     "add-instance-form" = "/events/series/{eventseries}/add",
 *     "edit-form" = "/events/series/{eventseries}/edit",
 *     "delete-form" = "/events/series/{eventseries}/delete",
 *     "collection" = "/events/series",
 *     "admin_collection" = "/admin/content/events/series",
 *     "clone-form" = "/events/series/{eventseries}/clone",
 *     "version-history" = "/events/series/{eventseries}/revisions",
 *     "revision" = "/events/series/{eventseries}/revisions/{eventseries_revision}/view",
 *     "revision_revert" = "/events/series/{eventseries}/revisions/{eventseries_revision}/revert",
 *     "revision_delete" = "/events/series/{eventseries}/revisions/{eventseries_revision}/delete",
 *     "translation_revert" = "/events/series/{eventseries}/revisions/{eventseries_revision}/revert/{langcode}",
 *   },
 *   field_ui_base_route = "entity.eventseries_type.edit_form",
 * )
 *
 * The 'links' above are defined by their path. For core to find the
 * corresponding route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.event.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * The 'EventSeries' class defines the eventseries entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see EventInterface) also exposes the
 * EntityOwnerInterface. This allows us to provide methods for setting
 * and providing ownership information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 */
class EventSeries extends EditorialContentEntityBase implements EventInterface {

  use EventUserTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the uid entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the node owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->revision_timestamp->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->revision_timestamp->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behavior of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the eventseries entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the event entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The eventseries type.'))
      ->setSetting('target_type', 'eventseries_type')
      ->setReadOnly(TRUE);

    // Title field for the event.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the event entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE);

    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Body'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['recur_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Recur Type'))
      ->setDescription('The way that the event recurs.')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setSetting('allowed_values_function', 'recurring_events_allowed_values_function')
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => 2,
      ]);

    $fields['consecutive_recurring_date'] = BaseFieldDefinition::create('consecutive_recurring_date')
      ->setLabel(t('Consecutive Event'))
      ->setDescription('The consecutive recurring date configuration.')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'consecutive_recurring_date',
        'weight' => 3,
      ]);

    $fields['daily_recurring_date'] = BaseFieldDefinition::create('daily_recurring_date')
      ->setLabel(t('Daily Event'))
      ->setDescription('The daily recurring date configuration.')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'daily_recurring_date',
        'weight' => 4,
      ]);

    $fields['weekly_recurring_date'] = BaseFieldDefinition::create('weekly_recurring_date')
      ->setLabel(t('Weekly Event'))
      ->setDescription('The weekly recurring date configuration.')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'weekly_recurring_date',
        'weight' => 5,
      ]);

    $fields['monthly_recurring_date'] = BaseFieldDefinition::create('monthly_recurring_date')
      ->setLabel(t('Monthly Event'))
      ->setDescription('The monthly recurring date configuration.')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'monthly_recurring_date',
        'weight' => 6,
      ]);

    $fields['yearly_recurring_date'] = BaseFieldDefinition::create('yearly_recurring_date')
      ->setLabel(t('Yearly Event'))
      ->setDescription('The yearly recurring date configuration.')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'yearly_recurring_date',
        'weight' => 7,
      ]);

    $fields['custom_date'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Custom Date(s) and Time(s)'))
      ->setDescription('The custom date configuration.')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'daterange_default',
        'weight' => 8,
      ]);

    $fields['excluded_dates'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Excluded Dates'))
      ->setDescription('Dates on which to not create any events.')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'daterange_default',
        'weight' => 9,
        'settings' => [
          'format_type' => 'html_date',
          'datetime_type' => 'date',
        ],
      ]);

    $fields['included_dates'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Included Dates'))
      ->setDescription('Only create events if they occur on these dates.')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'daterange_default',
        'weight' => 10,
        'settings' => [
          'format_type' => 'html_date',
          'datetime_type' => 'date',
        ],
      ]);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of event entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Get series start.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object for the series start date.
   */
  public function getSeriesStart() {
    $date = NULL;
    $instances = $this->get('event_instances')->referencedEntities();
    if (!empty($instances)) {
      $date = NULL;
      foreach ($instances as $instance) {
        if (!empty($instance)) {
          if (is_null($date)) {
            $date = $instance->date->start_date;
          }
          else {
            if ($instance->date->start_date->getTimestamp() < $date->getTimestamp()) {
              $date = $instance->date->start_date;
            }
          }
        }
      }
    }
    return $date;
  }

  /**
   * Get the number of instances.
   *
   * @return int
   *   A count of instances.
   */
  public function getInstanceCount() {
    return count($this->get('event_instances')->getValue());
  }

  /**
   * Get EventSeries recur type.
   *
   * @return string
   *   The type of recurrence for this event: weekly|monthly|custom.
   */
  public function getRecurType() {
    return $this->get('recur_type')->value;
  }

  /**
   * Get consecutive recurring start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object for the consecutive start date.
   */
  public function getConsecutiveStartDate() {
    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('consecutive_recurring_date')->start_date)) {
      return $this->get('consecutive_recurring_date')->start_date->setTimezone($user_timezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get consecutive recurring end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object for the consecutive end date.
   */
  public function getConsecutiveEndDate() {
    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('consecutive_recurring_date')->end_date)) {
      return $this->get('consecutive_recurring_date')->end_date->setTimezone($user_timezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get consecutive recurring start time.
   *
   * @return string
   *   The string for the consecutive start time.
   */
  public function getConsecutiveStartTime() {
    return $this->get('consecutive_recurring_date')->time;
  }

  /**
   * Get consecutive recurring end time.
   *
   * @return string
   *   The string for the consecutive end time.
   */
  public function getConsecutiveEndTime() {
    return $this->get('consecutive_recurring_date')->end_time;
  }

  /**
   * Get consecutive recurring duration.
   *
   * @return int
   *   The integer for the consecutive duration.
   */
  public function getConsecutiveDuration() {
    return $this->get('consecutive_recurring_date')->duration;
  }

  /**
   * Get consecutive recurring duration units.
   *
   * @return int
   *   The value for the consecutive duration units.
   */
  public function getConsecutiveDurationUnits() {
    return $this->get('consecutive_recurring_date')->duration_units;
  }

  /**
   * Get consecutive recurring buffer.
   *
   * @return int
   *   The integer for the consecutive buffer.
   */
  public function getConsecutiveBuffer() {
    return $this->get('consecutive_recurring_date')->buffer;
  }

  /**
   * Get consecutive recurring duration units.
   *
   * @return int
   *   The value for the consecutive duration units.
   */
  public function getConsecutiveBufferUnits() {
    return $this->get('consecutive_recurring_date')->buffer_units;
  }

  /**
   * Get daily recurring start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object for the daily start date.
   */
  public function getDailyStartDate() {
    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('daily_recurring_date')->start_date)) {
      return $this->get('daily_recurring_date')->start_date->setTimezone($user_timezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get daily recurring end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object for the daily end date.
   */
  public function getDailyEndDate() {
    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('daily_recurring_date')->end_date)) {
      return $this->get('daily_recurring_date')->end_date->setTimezone($user_timezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get daily recurring start time.
   *
   * @return string
   *   The string for the daily start time.
   */
  public function getDailyStartTime() {
    return $this->get('daily_recurring_date')->time;
  }

  /**
   * Get daily recurring duration.
   *
   * @return int
   *   The integer for the daily duration.
   */
  public function getDailyDuration() {
    return $this->get('daily_recurring_date')->duration;
  }

  /**
   * Get daily recurring end time.
   *
   * @return string
   *   The string for the daily end time.
   */
  public function getDailyEndTime() {
    return $this->get('daily_recurring_date')->end_time;
  }

  /**
   * Get daily recurring duration or end time.
   *
   * @return string
   *   The string for the daily duration or end time.
   */
  public function getDailyDurationOrEndTime() {
    return $this->get('daily_recurring_date')->duration_or_end_time;
  }

  /**
   * Get weekly recurring start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object for the weekly start date.
   */
  public function getWeeklyStartDate() {
    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('weekly_recurring_date')->start_date)) {
      return $this->get('weekly_recurring_date')->start_date->setTimezone($user_timezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get weekly recurring end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object for the weekly end date.
   */
  public function getWeeklyEndDate() {
    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('weekly_recurring_date')->end_date)) {
      return $this->get('weekly_recurring_date')->end_date->setTimezone($user_timezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get weekly recurring start time.
   *
   * @return string
   *   The string for the weekly start time.
   */
  public function getWeeklyStartTime() {
    return $this->get('weekly_recurring_date')->time;
  }

  /**
   * Get weekly recurring duration.
   *
   * @return int
   *   The integer for the weekly duration.
   */
  public function getWeeklyDuration() {
    return $this->get('weekly_recurring_date')->duration;
  }

  /**
   * Get weekly recurring end time.
   *
   * @return string
   *   The string for the weekly end time.
   */
  public function getWeeklyEndTime() {
    return $this->get('weekly_recurring_date')->end_time;
  }

  /**
   * Get weekly recurring duration or end time.
   *
   * @return string
   *   The string for the weekly duration or end time.
   */
  public function getWeeklyDurationOrEndTime() {
    return $this->get('weekly_recurring_date')->duration_or_end_time;
  }

  /**
   * Get weekly recurring days.
   *
   * @return array
   *   The array of days for the weekly event.
   */
  public function getWeeklyDays() {
    $days = $this->get('weekly_recurring_date')->days;
    if (!empty($days)) {
      $days = explode(',', $days);
    }
    return $days;
  }

  /**
   * Get excluded dates.
   *
   * @return array
   *   The array of excluded dates.
   */
  public function getExcludedDates() {
    $dates = [];
    $excluded_dates = $this->get('excluded_dates')->getValue();
    if (!empty($excluded_dates)) {
      foreach ($excluded_dates as $date) {
        $dates[] = $date;
      }
    }
    return $dates;
  }

  /**
   * Get included dates.
   *
   * @return array
   *   The array of included dates.
   */
  public function getIncludedDates() {
    $dates = [];
    $included_dates = $this->get('included_dates')->getValue();
    if (!empty($included_dates)) {
      foreach ($included_dates as $date) {
        $dates[] = $date;
      }
    }
    return $dates;
  }

  /**
   * Get monthly recurring start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object for the monthly start date.
   */
  public function getMonthlyStartDate() {
    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('monthly_recurring_date')->start_date)) {
      return $this->get('monthly_recurring_date')->start_date->setTimezone($user_timezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get monthly recurring end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object for the monthly end date.
   */
  public function getMonthlyEndDate() {
    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('monthly_recurring_date')->end_date)) {
      return $this->get('monthly_recurring_date')->end_date->setTimezone($user_timezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get monthly recurring start time.
   *
   * @return string
   *   The string for the monthly start time.
   */
  public function getMonthlyStartTime() {
    return $this->get('monthly_recurring_date')->time;
  }

  /**
   * Get monthly recurring duration.
   *
   * @return int
   *   The integer for the monthly duration.
   */
  public function getMonthlyDuration() {
    return $this->get('monthly_recurring_date')->duration;
  }

  /**
   * Get monthly recurring end time.
   *
   * @return string
   *   The string for the monthly end time.
   */
  public function getMonthlyEndTime() {
    return $this->get('monthly_recurring_date')->end_time;
  }

  /**
   * Get monthly recurring duration or end time.
   *
   * @return string
   *   The string for the monthly duration or end time.
   */
  public function getMonthlyDurationOrEndTime() {
    return $this->get('monthly_recurring_date')->duration_or_end_time;
  }

  /**
   * Get monthly recurring days.
   *
   * @return array
   *   The array of days for the monthly event.
   */
  public function getMonthlyDays() {
    $days = $this->get('monthly_recurring_date')->days;
    if (!empty($days)) {
      $days = explode(',', $days);
    }
    return $days;
  }

  /**
   * Get monthly recurring type.
   *
   * @return string
   *   The type of monthly recurrence.
   */
  public function getMonthlyType() {
    return $this->get('monthly_recurring_date')->type;
  }

  /**
   * Get monthly recurring day occurrences.
   *
   * @return array
   *   The day occurrences of the monthly recurrence.
   */
  public function getMonthlyDayOccurrences() {
    $occurrences = $this->get('monthly_recurring_date')->day_occurrence;
    if (!empty($occurrences)) {
      $occurrences = explode(',', $occurrences);
    }
    return $occurrences;
  }

  /**
   * Get monthly recurring day of month.
   *
   * @return int
   *   The day of month of monthly recurrence.
   */
  public function getMonthlyDayOfMonth() {
    $days = $this->get('monthly_recurring_date')->day_of_month;
    if (!empty($days)) {
      $days = explode(',', $days);
    }
    return $days;
  }

  /**
   * Get yearly recurring start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The date object for the yearly start date.
   */
  public function getYearlyStartDate() {
    $userTimezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('yearly_recurring_date')->start_date)) {
      return $this->get('yearly_recurring_date')->start_date->setTimezone($userTimezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get yearly recurring end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The date object for the yearly end date.
   */
  public function getYearlyEndDate() {
    $userTimezone = new \DateTimeZone(date_default_timezone_get());
    if (!empty($this->get('yearly_recurring_date')->end_date)) {
      return $this->get('yearly_recurring_date')->end_date->setTimezone($userTimezone)->setTime(0, 0, 0);
    }
    return NULL;
  }

  /**
   * Get yearly recurring start time.
   *
   * @return string
   *   The string for the yearly start time.
   */
  public function getYearlyStartTime() {
    return $this->get('yearly_recurring_date')->time;
  }

  /**
   * Get yearly recurring duration.
   *
   * @return int
   *   The integer for the yearly duration.
   */
  public function getYearlyDuration() {
    return $this->get('yearly_recurring_date')->duration;
  }

  /**
   * Get yearly recurring end time.
   *
   * @return string
   *   The string for the yearly end time.
   */
  public function getYearlyEndTime() {
    return $this->get('yearly_recurring_date')->end_time;
  }

  /**
   * Get yearly recurring duration or end time.
   *
   * @return string
   *   The string for the yearly duration or end time.
   */
  public function getYearlyDurationOrEndTime() {
    return $this->get('yearly_recurring_date')->duration_or_end_time;
  }

  /**
   * Get yearly recurring days.
   *
   * @return array
   *   The array of days for the yearly event.
   */
  public function getYearlyDays() {
    $days = $this->get('yearly_recurring_date')->days;
    if (!empty($days)) {
      $days = explode(',', $days);
    }
    return $days;
  }

  /**
   * Get yearly recurring type.
   *
   * @return string
   *   The type of yearly recurrence.
   */
  public function getYearlyType() {
    return $this->get('yearly_recurring_date')->type;
  }

  /**
   * Get yearly recurring day occurrences.
   *
   * @return array
   *   The day occurrences of the yearly recurrence.
   */
  public function getYearlyDayOccurrences() {
    $occurrences = $this->get('yearly_recurring_date')->day_occurrence;
    if (!empty($occurrences)) {
      $occurrences = explode(',', $occurrences);
    }
    return $occurrences;
  }

  /**
   * Get yearly recurring day of month.
   *
   * @return array
   *   The days of month of yearly recurrence.
   */
  public function getYearlyDayOfMonth() {
    $days = $this->get('yearly_recurring_date')->day_of_month;
    if (!empty($days)) {
      $days = explode(',', $days);
    }
    return $days;
  }

  /**
   * Get yearly recurring interval.
   *
   * @return int
   *   The number of years between occurrences.
   */
  public function getYearlyInterval() {
    return $this->get('yearly_recurring_date')->year_interval;
  }

  /**
   * Get yearly recurring months.
   *
   * @return array
   *   The months in which the event occurs for yearly recurrence.
   */
  public function getYearlyMonths() {
    $months = $this->get('yearly_recurring_date')->months;
    if (!empty($months)) {
      $months = explode(',', $months);
    }
    return $months;
  }

  /**
   * Get custom event dates.
   *
   * @return array
   *   An array of custom dates.
   */
  public function getCustomDates() {
    $custom_dates = [];

    $dates = $this->get('custom_date')->getIterator();
    if (!empty($dates)) {
      foreach ($dates as $date) {
        $custom_dates[] = [
          'start_date' => $date->start_date,
          'end_date' => $date->end_date,
        ];
      }
    }

    return $custom_dates;
  }

}
