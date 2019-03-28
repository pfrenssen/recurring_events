<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
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
   * Constructs a ReusableFormPluginBase object.
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
   * {@inheritdoc}
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceField() {
    return $this->sourceField;
  }

  /**
   * {@inheritdoc}
   */
  public function computeValue() {
    $method = $this->getMethod();
    $field = $this->getSourceField();

    $instance = $this->entity;
    $series = $instance->getEventSeries();

    switch ($method) {
      case 'inherit':
        $text = $series->{$field}->value;
        break;

      case 'prepend':
        $text = $instance->{$field}->value . ' ' . $series->{$field}->value;
        break;

      case 'append':
        $text = $series->{$field}->value . ' ' . $instance->{$field}->value;
        break;

      default:
        throw new \InvalidArgumentException("The definition's 'method' key must be one of: inherit, prepend, or append.");

    }
    return $text;
  }

}
