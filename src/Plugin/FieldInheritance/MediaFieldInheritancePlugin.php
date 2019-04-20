<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

use Drupal\recurring_events\FieldInheritancePluginInterface;

/**
 * Media Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "media_inheritance",
 *   name = @Translation("Media Field Inheritance"),
 *   types = {
 *     "field_ui:entity_reference:media"
 *   }
 * )
 */
class MediaFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
