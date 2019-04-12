<?php

/**
 * @file
 * Tokens for the recurring_events_registration module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function recurring_events_registration_token_info() {
  $eventinstance = [];
  $type = [
    'name' => t('Event Instance Registration'),
    'description' => t('Tokens from the Recurring Events Registration module.'),
    'needs-data' => 'eventinstance',
  ];

  $eventinstance['reg_url'] = [
    'name' => t('Register URL'),
    'description' => t('The URL for registering for an event instance.'),
  ];

  $eventinstance['reg_type'] = [
    'name' => t('Event Registration Type'),
    'description' => t('The registration type of the event instance.'),
  ];

  return [
    'types' => [
      'eventinstance' => $type,
    ],
    'tokens' => [
      'eventinstance' => $eventinstance,
    ],
  ];

}

/**
 * Implements hook_tokens().
 */
function recurring_events_registration_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'eventinstance' && !empty($data['eventinstance'])) {
    $event_instance = $data['eventinstance'];
    $event_series = $event_instance->getEventSeries();
    $creation_service = \Drupal::service('recurring_events_registration.creation_service');
    $creation_service->setEvents($event_instance);
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'reg_url':
          $replacements[$original] = $event_instance->toUrl('canonical') . '/registration/add';
          break;

        case 'reg_type':
          $replacements[$original] = $creation_service->getRegistrationType();
          break;
      }
    }
  }
  return $replacements;
}
