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
   * {@inheritdoc}
   */
  public function computeValue() {
    $method = $this->getMethod();
    $field = $this->getSourceField();

    $instance = $this->entity;
    $series = $instance->getEventSeries();

    ksm($series->{$field});

    switch ($method) {
      case 'inherit':
        $text = $series->{$field}->value;
        break;

      case 'prepend':
        if (empty($this->getEntityField())) {
          throw new \InvalidArgumentException("The definition's 'entity field' key must be set to prepend data.");
        }
        $entity_field = $this->getEntityField();
        $text = $instance->{$entity_field}->value . ' ' . $series->{$field}->value;
        break;

      case 'append':
        if (empty($this->getEntityField())) {
          throw new \InvalidArgumentException("The definition's 'entity field' key must be set to append data.");
        }
        $entity_field = $this->getEntityField();
        $text = $series->{$field}->value . ' ' . $instance->{$entity_field}->value;
        break;

      default:
        throw new \InvalidArgumentException("The definition's 'method' key must be one of: inherit, prepend, or append.");

    }

    return $text;
  }
}
