<?php

namespace Drupal\recurring_events;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * The FieldInheritanceFactory class.
 */
class FieldInheritanceFactory extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Constructs a TypedData object given its definition and context.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $definition
   *   The data definition.
   * @param string $name
   *   (optional) The name of the created property, or NULL if it is the root
   *   of a typed data tree. Defaults to NULL.
   * @param \Drupal\Core\TypedData\TypedDataInterface $parent
   *   (optional) The parent object of the data property, or NULL if it is the
   *   root of a typed data tree. Defaults to NULL.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   */
  public function __construct(BaseFieldDefinition $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    if ($this->getSetting('plugin') === NULL) {
      throw new \InvalidArgumentException("The definition's 'plugin' key has to specify the plugin to use to inherit data.");
    }

    if ($this->getSetting('method') === NULL) {
      throw new \InvalidArgumentException("The definition's 'method' key has to specify the method to use to inherit data. Valid options are inherit, prepend, replace, and append.");
    }

    if ($this->getSetting('source field') === NULL) {
      throw new \InvalidArgumentException("The definition's 'source field' key has to specify the field from which to inherit data.");
    }
  }

  /**
   * Compute the list property from state.
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    $manager = $this->getManager();
    $configuration = $this->getSettings() + ['entity' => $entity];
    $plugin = $manager->createInstance($this->getSetting('plugin'), $configuration);
    $value = $plugin->computeValue();
    $this->list[0] = $this->createItem(0, $value);
  }

  /**
   * Returns the FieldInheritanceManager plugin manager.
   *
   * @return \Drupal\recurring_events\FieldInheritanceManager
   *   The FieldInheritanceManager plugin manager.
   */
  protected function getManager() {
    return \Drupal::service('plugin.manager.field_inheritance');
  }

}