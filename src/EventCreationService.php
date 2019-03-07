<?php

namespace Drupal\recurring_events;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\Core\Form\FormStateInterface;

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
   * Class constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation interface.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory.
   */
  public function __construct(TranslationInterface $translation, Connection $database, LoggerChannelFactoryInterface $logger) {
    $this->translation = $translation;
    $this->database = $database;
    $this->loggerFactory = $logger->get('recurring_events');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('database'),
      $container->get('logger.factory')
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

    $date_timezone = new \DateTimeZone(drupal_get_user_timezone());
    $utc_timezone = new \DateTimeZone(DATETIME_STORAGE_TIMEZONE);
    $user_input = $form_state->getUserInput();

    $config['type'] = $user_input['recur_type'];

    switch ($config['type']) {
      case 'weekly':
        $start_timestamp = $user_input['weekly_recurring_date'][0]['value']['date'] . 'T00:00:00';
        $start_date = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $start_timestamp, $utc_timezone);

        $end_timestamp = $user_input['weekly_recurring_date'][0]['end_value']['date'] . 'T00:00:00';
        $end_date = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $end_timestamp, $utc_timezone);

        $config['start_date'] = $start_date;
        $config['end_date'] = $end_date;

        $config['time'] = $user_input['weekly_recurring_date'][0]['time'];
        $config['duration'] = $user_input['weekly_recurring_date'][0]['duration'];
        $config['days'] = array_filter(array_values($user_input['weekly_recurring_date'][0]['days']));
        break;

      case 'monthly':
        $start_timestamp = $user_input['weekly_recurring_date'][0]['value']['date'] . 'T00:00:00';
        $start_date = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $start_timestamp, $utc_timezone);

        $end_timestamp = $user_input['weekly_recurring_date'][0]['end_value']['date'] . 'T00:00:00';
        $end_date = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $end_timestamp, $utc_timezone);

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

          $start_timestamp = implode('T', $custom_date['value']);
          $start_date = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $start_timestamp, $date_timezone);
          // Convert the DateTime object back to UTC timezone.
          $start_date->setTimezone($utc_timezone);

          $end_timestamp = implode('T', $custom_date['end_value']);
          $end_date = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $end_timestamp, $date_timezone);
          // Convert the DateTime object back to UTC timezone.
          $end_date->setTimezone($utc_timezone);

          $config['custom_dates'][] = [
            'start_date' => $start_date,
            'end_date' => $end_date,
          ];
        }
        break;
    }

    return $config;
  }

}
