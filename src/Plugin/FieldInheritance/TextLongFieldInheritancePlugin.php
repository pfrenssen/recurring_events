<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

/**
 * Text Long Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "text_long_inheritance",
 *   name = @Translation("Text Long Field Inheritance"),
 * )
 */
class TextLongFieldInheritancePlugin extends FieldInheritancePluginBase {

  /**
   * Concatenation separator.
   *
   * @var string
   */
  const SEPARATOR = "\r\n\r\n";

}
