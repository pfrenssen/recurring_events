<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

use Drupal\recurring_events\FieldInheritancePluginInterface;

/**
 * Image Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "image_inheritance",
 *   name = @Translation("Image Field Inheritance"),
 *   types = {
 *     "image"
 *   }
 * )
 */
class ImageFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
