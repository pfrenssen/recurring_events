<?php

/**
 * @file
 * Tokens for the recurring_events module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function recurring_events_token_info() {
  $eventinstance = [];
  $eventinstance_type = [
    'name' => t('Event Instance'),
    'description' => t('Tokens for the eventinstance entity type.'),
    'needs-data' => 'eventinstance',
  ];

  $eventinstance['title'] = [
    'name' => t('Event Title'),
    'description' => t('The title of the event instance.'),
  ];

  $eventinstance['description'] = [
    'name' => t('Event Description'),
    'description' => t('The description of the event instance.'),
  ];

  $eventinstance['date'] = [
    'name' => t('Event Date'),
    'description' => t('The date of the event instance.'),
  ];

  $eventinstance['url'] = [
    'name' => t('Event URL'),
    'description' => t('The URL of the event instance.'),
  ];

  $eventseries = [];
  $eventseries_type = [
    'name' => t('Event Series'),
    'description' => t('Tokens for the eventseries entity type.'),
    'needs-data' => 'eventseries',
  ];

  $eventseries['title'] = [
    'name' => t('Event Title'),
    'description' => t('The title of the event series.'),
  ];

  $eventseries['description'] = [
    'name' => t('Event Description'),
    'description' => t('The description of the event series.'),
  ];

  return [
    'types' => [
      'eventinstance' => $eventinstance_type,
      'eventseries' => $eventseries_type,
    ],
    'tokens' => [
      'eventinstance' => $eventinstance,
      'eventseries' => $eventseries,
    ],
  ];

}

/**
 * Implements hook_tokens().
 */
function recurring_events_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'eventinstance' && !empty($data['eventinstance'])) {
    $event_instance = $data['eventinstance'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'title':
          // TODO: Should use the inheritance plugin manager.
          $replacements[$original] = $event_instance->getInheritedTitle();
          break;

        case 'description':
          // TODO: Should use the inheritance plugin manager.
          $replacements[$original] = $event_instance->getInheritedDescription();
          break;

        case 'date':
          // TODO: Should format according to what?
          $replacements[$original] = $event_instance->date->start_date->format('F jS, Y h:iA');
          break;

        case 'url':
          $replacements[$original] = $event_instance->toUrl('canonical');
          break;
      }
    }
  }
  if ($type == 'eventseries' && !empty($data['eventseries'])) {
    $event_series = $data['eventseries'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'title':
          $replacements[$original] = $event_series->title->value;
          break;

        case 'description':
          $replacements[$original] = $event_series->body->value;
          break;

      }
    }
  }
  return $replacements;
}
