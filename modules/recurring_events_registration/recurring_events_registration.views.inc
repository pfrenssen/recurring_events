<?php

/**
 * @file
 * Views functionality for the recurring_events_registration module.
 */

/**
 * Implements hook_views_data_alter().
 */
function recurring_events_registration_views_data_alter(array &$data) {
  // Set the default field for a view based on registrants.
  $data['registrant']['table']['base']['defaults']['field'] = 'email';
}
