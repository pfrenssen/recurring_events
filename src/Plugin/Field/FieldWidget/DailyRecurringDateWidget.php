<?php

namespace Drupal\recurring_events\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\recurring_events\Plugin\RecurringEventsFieldTrait;

/**
 * Plugin implementation of the 'daily recurring date' widget.
 *
 * @FieldWidget (
 *   id = "daily_recurring_date",
 *   label = @Translation("Daily recurring date widget"),
 *   field_types = {
 *     "daily_recurring_date"
 *   }
 * )
 */
class DailyRecurringDateWidget extends DateRangeDefaultWidget {

  use RecurringEventsFieldTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'container';
    $element['#states'] = [
      'visible' => [
        ':input[name="recur_type"]' => ['value' => 'daily_recurring_date'],
      ],
    ];

    $element['value']['#title'] = t('Create Events Between');
    $element['value']['#weight'] = 1;
    $element['value']['#date_date_format'] = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    $element['value']['#date_date_element'] = 'date';
    $element['value']['#date_time_format'] = '';
    $element['value']['#date_time_element'] = 'none';

    $element['end_value']['#title'] = t('And');
    $element['end_value']['#weight'] = 2;
    $element['end_value']['#date_date_format'] = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    $element['end_value']['#date_date_element'] = 'date';
    $element['end_value']['#date_time_format'] = '';
    $element['end_value']['#date_time_element'] = 'none';

    $times = $this->getTimeOptions();
    $element['time'] = [
      '#type' => 'select',
      '#title' => t('Event Start Time'),
      '#options' => $times,
      '#default_value' => $items[$delta]->time ?: '',
      '#weight' => 3,
    ];

    $durations = $this->getDurationOptions();
    $element['duration'] = [
      '#type' => 'select',
      '#title' => t('Event Duration'),
      '#options' => $durations,
      '#default_value' => $items[$delta]->duration ?: '',
      '#weight' => 4,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (empty($item['value'])) {
        $item['value'] = '';
      }
      elseif (!$item['value'] instanceof DrupalDateTime) {
        $item['value'] = substr($item['value'], 0, 10);
      }
      else {
        $item['value'];
      }
      if (empty($item['end_value'])) {
        $item['end_value'] = '';
      }
      elseif (!$item['end_value'] instanceof DrupalDateTime) {
        $item['end_value'] = substr($item['end_value'], 0, 10);
      }
      else {
        $item['end_value'];
      }

    }
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

}
