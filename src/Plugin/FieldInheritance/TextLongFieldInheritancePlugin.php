<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

use Drupal\recurring_events\FieldInheritancePluginInterface;

/**
 * Text Long Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "text_long_inheritance",
 *   name = @Translation("Text Long Field Inheritance"),
 *   types = {
 *     "text_long"
 *   }
 * )
 */
class TextLongFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {

  /**
   * Concatenation separator.
   *
   * @var string
   */
  const SEPARATOR = "\r\n\r\n";

}
