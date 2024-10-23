<?php

declare(strict_types=1);

namespace Drupal\recurring_events\Plugin\ComputedField;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field referencing the instances of an event series.
 */
class EventInstances extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if (!empty($entity->id())) {
      $instances = \Drupal::entityTypeManager()->getStorage('eventinstance')->loadByProperties([
        'eventseries_id' => $entity->id(),
      ]);

      // Sort by instance start date and reindex by field item delta, as
      // expected by EntityReferenceFieldItemListInterface::referencedEntities.
      usort($instances, function ($instanceA, $instanceB) {
        return $instanceA->date->value <=> $instanceB->date->value;
      });

      foreach ($instances as $key => $instance) {
        // Return the instances in the current language if available.
        $langcode = $this->getLangcode();
        $translation = $instance->hasTranslation($langcode) ? $instance->getTranslation($langcode) : $instance;
        $this->list[$key] = $this->createItem($key, $translation);
      }
    }
  }

}
