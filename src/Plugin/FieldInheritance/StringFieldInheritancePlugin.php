<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

use Drupal\recurring_events\FieldInheritancePluginInterface;

/**
 * String Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "string_inheritance",
 *   name = @Translation("String Field Inheritance"),
 *   types = {
 *     "string"
 *   }
 * )
 */
class StringFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {

  /**
   * Concatenation separator.
   *
   * @var string
   */
  const SEPARATOR = ' ';

}
