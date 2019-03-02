<?php

namespace Drupal\recurring_events\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\recurring_events\Plugin\Field\FieldWidget\WeeklyRecurringDateWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Plugin implementation of the 'monthly recurring date' widget.
 *
 * @FieldWidget (
 *   id = "monthly_recurring_date",
 *   label = @Translation("Monthly recurring date widget"),
 *   field_types = {
 *     "monthly_recurring_date"
 *   }
 * )
 */
class MonthlyRecurringDateWidget extends WeeklyRecurringDateWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#states'] = [
      'visible' => [
        ':input[name="recur_type"]' => ['value' => 'monthly'],
      ],
    ];

    $element['type'] = [
      '#type' => 'radios',
      '#title' => t('Event Recurrence Schedule'),
      '#options' => [
        'weekday' => t('Recur On Day of Week'),
        'monthday' => t('Recur On Day of Month'),
      ],
      '#default_value' => $items[$delta]->type ?: '',
      '#weight' => 5,
    ];

    $element['day_occurrence'] = [
      '#type' => 'checkboxes',
      '#title' => t('Day Occurrence'),
      '#options' => [
        'first' => t('First'),
        'second' => t('Second'),
        'third' => t('Third'),
        'fourth' => t('Fourth'),
        'last' => t('Last'),
      ],
      '#default_value' => $items[$delta]->day_occurrence ?: '',
      '#states' => [
        'visible' => [
          ':input[name="monthly_recurring_date[0][type]"]' => ['value' => 'weekday'],
        ],
      ],
      '#weight' => 6,
    ];

    $days = $this->getDayOptions();
    $element['days'] = [
      '#type' => 'checkboxes',
      '#title' => t('Days of the Week'),
      '#options' => $days,
      '#default_value' => $items[$delta]->days ?: '',
      '#states' => [
        'visible' => [
          ':input[name="monthly_recurring_date[0][type]"]' => ['value' => 'weekday'],
        ],
      ],
      '#weight' => 7,
    ];

    $month_days = $this->getMonthDayOptions();
    $element['day_of_month'] = [
      '#type' => 'select',
      '#title' => t('Day of Month'),
      '#options' => $month_days,
      '#default_value' => $items[$delta]->day_of_month ?: '',
      '#states' => [
        'visible' => [
          ':input[name="monthly_recurring_date[0][type]"]' => ['value' => 'monthday'],
        ],
      ],
      '#weight' => 8,
    ];

    return $element;
  }

  /**
   * Return day of month options for events.
   *
   * @return array
   *   An array of days of month suitable for a checkbox field.
   */
  protected function getMonthDayOptions() {
    $days = [];
    $start = date('Y') . '-01-01';
    $date = DrupalDateTime::createFromFormat('Y-m-d', $start);

    for ($x = 1; $x <= 31; $x++) {
      $days[$x] = $date->format('jS');
      $date->modify('+1 day');
    }

    $days[-1] = t('Last');

    // TODO: Add hook ability to modify these days.

    return $days;
  }

}
