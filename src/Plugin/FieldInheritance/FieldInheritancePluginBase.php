<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\recurring_events\FieldInheritancePluginInterface;

/**
 * Abstract class FieldInheritancePluginBase.
 */
abstract class FieldInheritancePluginBase extends PluginBase implements FieldInheritancePluginInterface {

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The method used to inherit.
   *
   * @var string
   */
  protected $method;

  /**
   * The source field used to inherit.
   *
   * @var string
   */
  protected $sourceField;

  /**
   * The entity field used to inherit.
   *
   * @var string
   */
  protected $entityField;

  /**
   * Constructs a FieldInheritancePluginBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity = $configuration['entity'];
    $this->method = $configuration['method'];
    $this->sourceField = $configuration['source field'];
    if (!empty($configuration['entity field'])) {
      $this->entityField = $configuration['entity field'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Get the configuration method.
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * Get the configuration source field.
   */
  public function getSourceField() {
    return $this->sourceField;
  }

  /**
   * Get the configuration entity field.
   */
  public function getEntityField() {
    return $this->entityField;
  }

  /**
   * {@inheritdoc}
   */
  public function computeValue() {
    $this->validateArguments();
    $method = $this->getMethod();

    $value = '';
    switch ($method) {
      case 'inherit':
        $value = $this->inheritData();
        break;

      case 'prepend':
        $value = $this->prependData();
        break;

      case 'append':
        $value = $this->appendData();
        break;

      case 'fallback':
        $value = $this->fallbackData();
        break;
    }
    return $value;
  }

  /**
   * Retrieve inherited data.
   *
   * @return string
   *   The inherited data.
   */
  protected function inheritData() {
    $series = $this->entity->getEventSeries();
    return $series->{$this->getSourceField()}->getValue() ?? '';
  }

  /**
   * Retrieve prepended data.
   *
   * @return string
   *   The prepended data.
   */
  protected function prependData() {
    $series = $this->entity->getEventSeries();
    $instance = $this->entity;

    $values = [];
    if (!empty($instance->{$this->getEntityField()}->getValue())) {
      $values = array_merge($values, $instance->{$this->getEntityField()}->getValue());
    }
    if (!empty($series->{$this->getSourceField()}->getValue())) {
      $values = array_merge($values, $series->{$this->getSourceField()}->getValue());
    }
    return $values;
  }

  /**
   * Retrieve appended data.
   *
   * @return string
   *   The appended data.
   */
  protected function appendData() {
    $series = $this->entity->getEventSeries();
    $instance = $this->entity;

    $values = [];
    if (!empty($series->{$this->getSourceField()}->getValue())) {
      $values = array_merge($values, $series->{$this->getSourceField()}->getValue());
    }
    if (!empty($instance->{$this->getEntityField()}->getValue())) {
      $values = array_merge($values, $instance->{$this->getEntityField()}->getValue());
    }
    return $values;
  }

  /**
   * Retrieve fallback data.
   *
   * @return string
   *   The fallback data.
   */
  protected function fallbackData() {
    $series = $this->entity->getEventSeries();
    $instance = $this->entity;

    if (!empty($instance->{$this->getEntityField()}->getValue())) {
      $values = $instance->{$this->getEntityField()}->getValue();
    }
    elseif (!empty($series->{$this->getSourceField()}->getValue())) {
      $values = $series->{$this->getSourceField()}->getValue();
    }
    return $values;
  }

  /**
   * Validate the configuration arguments of the plugin.
   */
  protected function validateArguments() {
    if (empty($this->getMethod())) {
      throw new \InvalidArgumentException("The definition's 'method' key must be set to inherit data.");
    }

    if (empty($this->getSourceField())) {
      throw new \InvalidArgumentException("The definition's 'source field' key must be set to inherit data.");
    }

    $method = $this->getMethod();
    $entity_field_methods = [
      'prepend',
      'append',
      'fallback',
    ];

    if (array_search($method, $entity_field_methods)) {
      if (empty($this->getEntityField())) {
        throw new \InvalidArgumentException("The definition's 'entity field' key must be set to prepend, append, or fallback to series data.");
      }
    }

    return TRUE;
  }

}
