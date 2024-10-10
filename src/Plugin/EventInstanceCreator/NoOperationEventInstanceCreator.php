<?php

declare(strict_types=1);

namespace Drupal\recurring_events\Plugin\EventInstanceCreator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events\EventInstanceCreatorBase;

/**
 * Event instance creator plugin that does nothing.
 *
 * Use this plugin if you want to preserve existing event instances and not
 * create new ones.
 *
 * @EventInstanceCreator(
 *   id = "noop",
 *   description = @Translation("Do nothing")
 * )
 */
class NoOperationEventInstanceCreator extends EventInstanceCreatorBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function processInstances(EventSeries $series): void {
  }

}
