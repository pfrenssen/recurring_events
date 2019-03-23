<?php

namespace Drupal\recurring_events;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * EventCreationService class.
 */
class EventCreationService {

  /**
   * The translation interface.
   *
   * @var Drupal\Core\StringTranslation\TranslationInterface
   */
  private $translation;

  /**
   * The database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * Logger Factory.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation interface.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(TranslationInterface $translation, Connection $database, LoggerChannelFactoryInterface $logger, Messenger $messenger) {
    $this->translation = $translation;
    $this->database = $database;
    $this->loggerFactory = $logger->get('recurring_events');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('database'),
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Check whether there have been recurring configuration changes.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of an updated event series entity.
   *
   * @return bool
   *   TRUE if recurring config changes, FALSE otherwise.
   */
  public function checkForRecurConfigChanges(EventSeries $event, FormStateInterface $form_state) {
    $entity_config = $this->convertEntityConfigToArray($event);
    $form_config = $this->convertFormConfigToArray($form_state);

    return !($entity_config === $form_config);
  }

  /**
   * Converts an EventSeries entity's recurring configuration to an array.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   *
   * @return array
   *   The recurring configuration as an array.
   */
  public function convertEntityConfigToArray(EventSeries $event) {
    $config = [];
    $config['type'] = $event->getRecurType();

    switch ($event->getRecurType()) {
      case 'weekly':
        $config['start_date'] = $event->getWeeklyStartDate();
        $config['end_date'] = $event->getWeeklyEndDate();
        $config['time'] = $event->getWeeklyStartTime();
        $config['duration'] = $event->getWeeklyDuration();
        $config['days'] = $event->getWeeklyDays();
        break;

      case 'monthly':
        $config['start_date'] = $event->getMonthlyStartDate();
        $config['end_date'] = $event->getMonthlyEndDate();
        $config['time'] = $event->getMonthlyStartTime();
        $config['duration'] = $event->getMonthlyDuration();
        $config['monthly_type'] = $event->getMonthlyType();

        switch ($event->getMonthlyType()) {
          case 'weekday':
            $config['day_occurrence'] = $event->getMonthlyDayOccurrences();
            $config['days'] = $event->getMonthlyDays();
            break;

          case 'monthday':
            $config['day_of_month'] = $event->getMonthlyDayOfMonth();
            break;
        }
        break;

      case 'custom':
        $config['custom_dates'] = $event->getCustomDates();
        break;
    }
    return $config;
  }

  /**
   * Converts a form state object's recurring configuration to an array.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of an updated event series entity.
   *
   * @return array
   *   The recurring configuration as an array.
   */
  public function convertFormConfigToArray(FormStateInterface $form_state) {
    $config = [];

    $user_timezone = new \DateTimeZone(drupal_get_user_timezone());
    $utc_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $user_input = $form_state->getUserInput();

    $config['type'] = $user_input['recur_type'];

    switch ($config['type']) {
      case 'weekly':
        $start_timestamp = $user_input['weekly_recurring_date'][0]['value']['date'] . 'T12:00:00';
        $start_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $start_timestamp, $utc_timezone);
        $start_date->setTimezone($user_timezone);
        $start_date->setTime(0, 0, 0);

        $end_timestamp = $user_input['weekly_recurring_date'][0]['end_value']['date'] . 'T12:00:00';
        $end_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_timestamp, $utc_timezone);
        $start_date->setTimezone($user_timezone);
        $end_date->setTime(0, 0, 0);

        $config['start_date'] = $start_date;
        $config['end_date'] = $end_date;

        $config['time'] = $user_input['weekly_recurring_date'][0]['time'];
        $config['duration'] = $user_input['weekly_recurring_date'][0]['duration'];
        $config['days'] = array_filter(array_values($user_input['weekly_recurring_date'][0]['days']));
        break;

      case 'monthly':
        $start_timestamp = $user_input['monthly_recurring_date'][0]['value']['date'] . 'T12:00:00';
        $start_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $start_timestamp, $utc_timezone);
        $start_date->setTimezone($user_timezone);
        $start_date->setTime(0, 0, 0);

        $end_timestamp = $user_input['monthly_recurring_date'][0]['end_value']['date'] . 'T12:00:00';
        $end_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_timestamp, $utc_timezone);
        $start_date->setTimezone($user_timezone);
        $end_date->setTime(0, 0, 0);

        $config['start_date'] = $start_date;
        $config['end_date'] = $end_date;

        $config['time'] = $user_input['monthly_recurring_date'][0]['time'];
        $config['duration'] = $user_input['monthly_recurring_date'][0]['duration'];
        $config['monthly_type'] = $user_input['monthly_recurring_date'][0]['type'];

        switch ($config['monthly_type']) {
          case 'weekday':
            $config['day_occurrence'] = array_filter(array_values($user_input['monthly_recurring_date'][0]['day_occurrence']));
            $config['days'] = array_filter(array_values($user_input['monthly_recurring_date'][0]['days']));
            break;

          case 'monthday':
            $config['day_of_month'] = array_filter(array_values($user_input['monthly_recurring_date'][0]['day_of_month']));
            break;
        }
        break;

      case 'custom':
        foreach ($user_input['custom_date'] as $custom_date) {
          $start_date = $end_date = NULL;

          if (!empty($custom_date['value']['date'])
            && !empty($custom_date['value']['time'])
            && !empty($custom_date['end_value']['date'])
            && !empty($custom_date['end_value']['time'])) {

            // For some reason, sometimes we do not receive seconds from the
            // date range picker.
            if (strlen($custom_date['value']['time']) == 5) {
              $custom_date['value']['time'] .= ':00';
            }
            if (strlen($custom_date['end_value']['time']) == 5) {
              $custom_date['end_value']['time'] .= ':00';
            }

            $start_timestamp = implode('T', $custom_date['value']);
            $start_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $start_timestamp, $user_timezone);
            // Convert the DateTime object back to UTC timezone.
            $start_date->setTimezone($utc_timezone);

            $end_timestamp = implode('T', $custom_date['end_value']);
            $end_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_timestamp, $user_timezone);
            // Convert the DateTime object back to UTC timezone.
            $end_date->setTimezone($utc_timezone);

            $config['custom_dates'][] = [
              'start_date' => $start_date,
              'end_date' => $end_date,
            ];
          }
        }
        break;
    }

    return $config;
  }

  /**
   * Build diff array between stored entity and form state.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of an updated event series entity.
   *
   * @return array
   *   An array of differences.
   */
  public function buildDiffArray(EventSeries $event, FormStateInterface $form_state) {
    $diff = [];

    $entity_config = $this->convertEntityConfigToArray($event);
    $form_config = $this->convertFormConfigToArray($form_state);

    if ($entity_config['type'] !== $form_config['type']) {
      $diff['type'] = [
        'label' => $this->translation->translate('Recur Type'),
        'stored' => $entity_config['type'],
        'override' => $form_config['type'],
      ];
    }
    else {
      switch ($entity_config['type']) {
        case 'weekly':
        case 'monthly':
          if ($entity_config['start_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) !== $form_config['start_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)) {
            $diff['start_date'] = [
              'label' => $this->translation->translate('Start Date'),
              'stored' => $entity_config['start_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
              'override' => $form_config['start_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
            ];
          }
          if ($entity_config['end_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) !== $form_config['end_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)) {
            $diff['end_date'] = [
              'label' => $this->translation->translate('End Date'),
              'stored' => $entity_config['end_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
              'override' => $form_config['end_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
            ];
          }
          if ($entity_config['time'] !== $form_config['time']) {
            $diff['time'] = [
              'label' => $this->translation->translate('Time'),
              'stored' => $entity_config['time'],
              'override' => $form_config['time'],
            ];
          }
          if ($entity_config['duration'] !== $form_config['duration']) {
            $diff['duration'] = [
              'label' => $this->translation->translate('Duration'),
              'stored' => $entity_config['duration'],
              'override' => $form_config['duration'],
            ];
          }

          if ($entity_config['type'] === 'weekly') {
            if ($entity_config['days'] !== $form_config['days']) {
              $diff['days'] = [
                'label' => $this->translation->translate('Days'),
                'stored' => implode(',', $entity_config['days']),
                'override' => implode(',', $form_config['days']),
              ];
            }
          }

          if ($entity_config['type'] === 'monthly') {
            if ($entity_config['monthly_type'] !== $form_config['monthly_type']) {
              $diff['monthly_type'] = [
                'label' => $this->translation->translate('Monthly Type'),
                'stored' => $entity_config['monthly_type'],
                'override' => $form_config['monthly_type'],
              ];
            }
            if ($entity_config['monthly_type'] === 'weekday') {
              if ($entity_config['day_occurrence'] !== $form_config['day_occurrence']) {
                $diff['day_occurrence'] = [
                  'label' => $this->translation->translate('Day Occurrence'),
                  'stored' => implode(',', $entity_config['day_occurrence']),
                  'override' => implode(',', $form_config['day_occurrence']),
                ];
              }
              if ($entity_config['days'] !== $form_config['days']) {
                $diff['days'] = [
                  'label' => $this->translation->translate('Days'),
                  'stored' => implode(',', $entity_config['days']),
                  'override' => implode(',', $form_config['days']),
                ];
              }
            }
            else {
              if ($entity_config['monthday'] !== $form_config['monthday']) {
                $diff['monthday'] = [
                  'label' => $this->translation->translate('Day of the Month'),
                  'stored' => implode(',', $entity_config['monthday']),
                  'override' => implode(',', $form_config['monthday']),
                ];
              }
            }
          }

          break;

        case 'custom':
          if ($entity_config['custom_dates'] !== $form_config['custom_dates']) {
            $stored_start_ends = $overridden_start_ends = [];

            foreach ($entity_config['custom_dates'] as $date) {
              if (!empty($date['start_date']) && !empty($date['end_date'])) {
                $stored_start_ends[] = $date['start_date']->format('Y-m-d h:ia') . ' - ' . $date['end_date']->format('Y-m-d h:ia');
              }
            }

            foreach ($form_config['custom_dates'] as $dates) {
              if (!empty($date['start_date']) && !empty($date['end_date'])) {
                $overridden_start_ends[] = $date['start_date']->format('Y-m-d h:ia') . ' - ' . $date['end_date']->format('Y-m-d h:ia');
              }
            }

            $diff['custom_dates'] = [
              'label' => $this->translation->translate('Custom Dates'),
              'stored' => implode(', ', $stored_start_ends),
              'override' => implode(', ', $overridden_start_ends),
            ];
          }
          break;
      }
    }

    return $diff;
  }

  /**
   * Create an event based on the form submitted values.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of an updated event series entity.
   */
  public static function saveEvent(EventSeries $event, FormStateInterface $form_state) {
    // We only need a revision if this is an existing entity.
    if ($event->isNew()) {
      $create_instances = TRUE;
      // We have to save the event series first so we can use the ID to store
      // against the event instances we create.
      $event->save();
    }
    else {
      // If there are date differences, we need to clear out the instances.
      $create_instances = $this->checkForRecurConfigChanges($entity, $form_state);
      if ($create_instances) {
        // Find all the instances and delete them.
        $instances = $event->event_instances->referencedEntities();
        if (!empty($instances)) {
          foreach ($instances as $index => $instance) {
            $instance->delete();
          }
          $this->messenger->addStatus($this->translation->translate('Successfully removed %count event instances', [
            '%count' => count($instances),
          ]));
        }
      }
    }

    // Only create instances if date changes have been made or the event is new.
    if ($create_instances) {
      $this->createInstances($event, $form_state);
    }
  }

}
