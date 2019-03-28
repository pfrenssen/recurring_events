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
   * Concatenation separator.
   *
   * @var string
   */
  const SEPARATOR = '';

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
    $method = $this->getMethod();
    $field = $this->getSourceField();

    $instance = $this->entity;
    $series = $instance->getEventSeries();

    switch ($method) {
      case 'inherit':
        $value = $series->{$field}->value ?? '';
        break;

      case 'prepend':
        if (empty($this->getEntityField())) {
          throw new \InvalidArgumentException("The definition's 'entity field' key must be set to prepend data.");
        }
        $entity_field = $this->getEntityField();

        $fields = [];
        if (!empty($instance->{$entity_field}->value)) {
          $fields[] = $instance->{$entity_field}->value;
        }
        if (!empty($series->{$field}->value)) {
          $fields[] = $series->{$field}->value;
        }
        $value = implode($this::SEPARATOR, $fields);
        break;

      case 'append':
        if (empty($this->getEntityField())) {
          throw new \InvalidArgumentException("The definition's 'entity field' key must be set to append data.");
        }
        $entity_field = $this->getEntityField();

        $fields = [];
        if (!empty($series->{$field}->value)) {
          $fields[] = $series->{$field}->value;
        }
        if (!empty($instance->{$entity_field}->value)) {
          $fields[] = $instance->{$entity_field}->value;
        }
        $value = implode($this::SEPARATOR, $fields);
        break;

      case 'fallback':
        if (empty($this->getEntityField())) {
          throw new \InvalidArgumentException("The definition's 'entity field' key must be set to fallback to series data.");
        }
        $entity_field = $this->getEntityField();

        $value = '';

        if (!empty($instance->{$entity_field}->value)) {
          $value = $instance->{$entity_field}->value;
        }
        elseif (!empty($series->{$field}->value)) {
          $value = $series->{$field}->value;
        }

        break;

      default:
        throw new \InvalidArgumentException("The definition's 'method' key must be one of: inherit, prepend, or append.");

    }

    return $value;
  }

}
