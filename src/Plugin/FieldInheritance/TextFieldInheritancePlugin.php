<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

/**
 * Text Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "text_inheritance",
 *   name = @Translation("Text Field Inheritance"),
 * )
 */
class TextFieldInheritancePlugin extends FieldInheritancePluginBase {

  /**
   * Concatenation separator.
   *
   * @var string
   */
  const SEPARATOR = ' ';

}
