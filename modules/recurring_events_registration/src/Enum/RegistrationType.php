<?php

declare(strict_types=1);

namespace Drupal\recurring_events_registration\Enum;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Enumerates the available registration types.
 */
enum RegistrationType: string {

  case INSTANCE = 'instance';
  case SERIES = 'series';

  /**
   * Returns the default registration type.
   *
   * @return self
   *   The default registration type.
   */
  public static function defaultValue(): self {
    return self::INSTANCE;
  }

  /**
   * Returns the label for the registration type.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label for the registration type.
   */
  public function getLabel(): TranslatableMarkup {
    return match ($this) {
      self::INSTANCE => t('Instance'),
      self::SERIES => t('Series'),
    };
  }

}
