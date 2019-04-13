<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

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
class StringFieldInheritancePlugin extends FieldInheritancePluginBase {

  /**
   * Concatenation separator.
   *
   * @var string
   */
  const SEPARATOR = ' ';

}
