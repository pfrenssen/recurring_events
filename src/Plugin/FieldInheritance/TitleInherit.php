<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

/**
 * Title Inherit FieldInheritance plugin.
 *
 * @FieldInheritance(
 *   id = "title_inherit",
 *   name = @Translation("Title Inherit"),
 * )
 */
class TitleInherit extends FieldInheritancePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $item = $this->getParent();
    return $item->getEventSeries()->title->value;
  }

}
