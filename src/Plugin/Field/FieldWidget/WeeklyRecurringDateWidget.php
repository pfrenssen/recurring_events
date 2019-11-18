<?php

namespace Drupal\recurring_events\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Plugin implementation of the 'weekly recurring date' widget.
 *
 * @FieldWidget (
 *   id = "weekly_recurring_date",
 *   label = @Translation("Weekly recurring date widget"),
 *   field_types = {
 *     "weekly_recurring_date"
 *   }
 * )
 */
class WeeklyRecurringDateWidget extends DailyRecurringDateWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'container';
    $element['#states'] = [
      'visible' => [
        ':input[name="recur_type"]' => ['value' => 'weekly_recurring_date'],
      ],
    ];

    $days = $this->getDayOptions();
    $element['days'] = [
      '#type' => 'checkboxes',
      '#title' => t('Days of the Week'),
      '#options' => $days,
      '#default_value' => $items[$delta]->days ? explode(',', $items[$delta]->days) : [],
      '#weight' => 5,
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

      $item['days'] = array_filter($item['days']);
      if (!empty($item['days'])) {
        $item['days'] = implode(',', $item['days']);
      }
      else {
        $item['days'] = '';
      }

    }
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

  /**
   * Return day options for events.
   *
   * @return array
   *   An array of days suitable for a checkbox field.
   */
  protected function getDayOptions() {
    $config = \Drupal::config('recurring_events.eventseries.config');
    $days = explode(',', $config->get('days'));
    // All labels should have a capital first letter as they are proper nouns.
    $day_labels = array_map('ucwords', $days);
    $days = array_combine($days, $day_labels);

    \Drupal::moduleHandler()->alter('recurring_events_days', $days);

    return $days;
  }

}
