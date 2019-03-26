<?php

namespace Drupal\recurring_events;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\Language\LanguageInterface;

/**
 * The FieldInheritanceFactory class.
 */
class FieldInheritanceFactory extends TypedData implements CacheableDependencyInterface {

  /**
   * Cached value.
   *
   * @var object|null
   */
  protected $value = NULL;

  /**
   * The langcode of the field values held in the object.
   *
   * @var string
   */
  protected $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    if ($definition->getSetting('plugin') === NULL) {
      throw new \InvalidArgumentException("The definition's 'plugin' key has to specify the plugin to use to inherit data.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $manager = $this->getManager();
    $plugin = $manager->createInstance($definition->getSetting('plugin'), $definition->getSettings());
    die('dasdsadsa');
    return $plugin->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode($langcode) {
    $this->langcode = $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->value = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $this->getValue();
    return $this->processed->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $this->getValue();
    return $this->processed->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $this->getValue();
    return $this->processed->getCacheMaxAge();
  }

  /**
   * Returns the renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer service.
   */
  protected function getRenderer() {
    return \Drupal::service('renderer');
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
