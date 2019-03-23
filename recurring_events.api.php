<?php

/**
 * @file
 * Custom hooks exposed by the recurring_events module.
 */

/**
 * Alter the time options available when creating an event series entity.
 *
 * @param array $times
 *   An array of times in the format h:i a.
 */
function hook_recurring_events_times_alter(array &$times = []) {
  // Events cannot occur at midnight.
  unset($times['00:00 am']);
}

/**
 * Alter the duration options available when creating an event series entity.
 *
 * @param array $durations
 *   An array of durations in seconds.
 */
function hook_recurring_events_durations_alter(array &$durations = []) {
  // Events can last for 2 days.
  $durations[172800] = t('2 days');
}

/**
 * Alter the days options available when creating an event series entity.
 *
 * @param array $days
 *   An array of available days.
 */
function hook_recurring_events_days_alter(array &$days = []) {
  // No events can take place on sundays.
  unset($days['sunday']);
}

/**
 * Alter the month days options available when creating an event series entity.
 *
 * @param array $month_days
 *   An array of available days of the month.
 */
function hook_recurring_events_month_days_alter(array &$month_days = []) {
  // No events can take place on the 17th of a month.
  unset($month_days[17]);
}

/**
 * Alter the event instance entity prior to saving it when creating a series.
 *
 * @param array $event_instance
 *   An array of data to be stored against a event instance.
 */
function hook_recurring_events_event_instance_alter(array &$event_instance = []) {
  // Change the series ID.
  $event_instance['event_series_id'] = 12;
}
