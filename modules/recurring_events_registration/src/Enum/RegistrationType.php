<?php

declare(strict_types=1);

namespace Drupal\recurring_events_registration\Enum;

/**
 * Enumerates the available registration types.
 */
enum RegistrationType: string {

  case INSTANCE = 'instance';
  case SERIES = 'series';

  /**
   * Returns the default value.
   */
  public static function defaultValue(): self {
    return self::INSTANCE;
  }

}
