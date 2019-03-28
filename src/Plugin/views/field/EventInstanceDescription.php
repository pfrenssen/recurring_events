<?php

namespace Drupal\recurring_events\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show the inherited event instance description.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eventinstance_description")
 */
class EventInstanceDescription extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $event = $values->_entity;
    return $event->getInheritedDescription();
  }

}
