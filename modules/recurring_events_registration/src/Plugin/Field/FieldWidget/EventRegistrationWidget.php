<?php

namespace Drupal\recurring_events_registration\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'event registration' widget.
 *
 * @FieldWidget (
 *   id = "event_registration",
 *   label = @Translation("Event registration widget"),
 *   field_types = {
 *     "event_registration"
 *   }
 * )
 */
class EventRegistrationWidget extends DateRangeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'container';

    $element['registration'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Registration'),
      '#description' => t('Select this box to enable registrations for this event. By doing so you will be able to specify the capacity of the event, and if applicable enable a waitlist.'),
      '#weight' => 0,
      '#default_value' => $items[$delta]->registration ?: '',
    ];

    $element['registration_type'] = [
      '#type' => 'radios',
      '#title' => t('Registration Type'),
      '#description' => t('Select whether registrations are for the entire series, or for individual instances.'),
      '#weight' => 1,
      '#default_value' => $items[$delta]->registration_type ?: 'instance',
      '#options' => [
        'instance' => t('Individual Event Registration'),
        'series' => t('Entire Series Registration'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['registration_dates'] = [
      '#type' => 'radios',
      '#title' => t('Registration Dates'),
      '#description' => t('Choose between open or scheduled registration.'),
      '#weight' => 2,
      '#default_value' => $items[$delta]->registration_dates ?: 'open',
      '#options' => [
        'open' => t('Open Registration'),
        'scheduled' => t('Scheduled Registration'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['series_registration'] = [
      '#type' => 'container',
      '#title' => t('Series Registration'),
      '#weight' => 3,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
          ':input[name="event_registration[0][registration_type]"]' => ['value' => 'series'],
          ':input[name="event_registration[0][registration_dates]"]' => ['value' => 'scheduled'],
        ],
      ],
    ];

    $element['series_registration']['value'] = $element['value'];
    $element['series_registration']['end_value'] = $element['end_value'];
    unset($element['value']);
    unset($element['end_value']);

    $element['series_registration']['value']['#title'] = t('Registration Opens');
    $element['series_registration']['end_value']['#title'] = t('Registration Closes');

    $element['instance_registration'] = [
      '#type' => 'container',
      '#title' => t('Instance Registration'),
      '#weight' => 3,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
          ':input[name="event_registration[0][registration_type]"]' => ['value' => 'instance'],
          ':input[name="event_registration[0][registration_dates]"]' => ['value' => 'scheduled'],
        ],
      ],
    ];

    $element['instance_registration']['time_amount'] = [
      '#type' => 'number',
      '#title' => t('Registration Time Amount'),
      '#description' => t('Enter the amount of time in days or hours before the event(s) start time(s) that registration should open.'),
      '#weight' => 0,
      '#default_value' => $items[$delta]->time_amount ?: '',
      '#min' => 0,
    ];

    $element['instance_registration']['time_type'] = [
      '#type' => 'select',
      '#title' => t('Registration Time Type'),
      '#description' => t("Select either Days or Hours to choose how long before which an event's registration will open."),
      '#weight' => 1,
      '#default_value' => $items[$delta]->time_type ?: '',
      '#options' => [
        'days' => t('Days'),
        'hours' => t('Hours'),
      ],
    ];

    $element['capacity'] = [
      '#type' => 'number',
      '#title' => t('Total Number of Spaces Available'),
      '#description' => t('Maximum number of attendees available for each series, or individual event.'),
      '#weight' => 4,
      '#default_value' => $items[$delta]->capacity ?: '',
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['waitlist'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Waiting List'),
      '#description' => t('Enable a waiting list if the number of registrations reaches capacity.'),
      '#weight' => 5,
      '#default_value' => $items[$delta]->waitlist ?: '',
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

}
