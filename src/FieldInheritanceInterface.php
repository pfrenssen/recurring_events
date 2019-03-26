<?php

namespace Drupal\recurring_events;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * FieldInheritanceInterface definition.
 */
interface FieldInheritanceInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Get the name of the field inheritance plugin.
   */
  public function getName();

  /**
   * Get the source field to use for field inheritance.
   */
  public function getSourceField();

}
