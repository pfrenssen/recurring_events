<?php

declare(strict_types=1);

namespace Drupal\recurring_events_registration\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events_registration\RegistrationCreationService;

/**
 * Cache context that allows to vary elements by whether registration is open.
 *
 * Cache context ID: 'recurring_events_registration_is_open:%event_instance_id'.
 *
 * @todo To make this work for anonymous users, the Internal Page Cache module
 *   needs to be disabled. Revisit this once the below issue is solved to add a
 *   cache max-age lifetime until the next change in the registration open
 *   status takes place.
 * @see https://www.drupal.org/project/drupal/issues/2352009
 */
final class RegistrationIsOpenCacheContext implements CalculatedCacheContextInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly RegistrationCreationService $creationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): string {
    return (string) t('Event registration is open for the event instance.');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($event_instance_id = NULL): string {
    if (!is_numeric($event_instance_id)) {
      throw new \LogicException('Missing event instance ID.');
    }

    $event_instance = $this->getEventInstance((int) $event_instance_id);
    if (!$event_instance) {
      return 'null';
    }

    $this->creationService->setEventInstance($event_instance);
    $this->creationService->setEventSeries($event_instance->getEventSeries());
    $result = $this->creationService->registrationIsOpen() ? 'true' : 'false';
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($event_instance_id = NULL): CacheableMetadata {
    if (!is_numeric($event_instance_id)) {
      throw new \LogicException('Missing event instance ID.');
    }

    $metadata = new CacheableMetadata();
    if ($event_instance = $this->getEventInstance((int) $event_instance_id)) {
      // If the event series changes it is possible this affects the
      // registration period, so we need to add its cache tag.
      $event_series = $event_instance->getEventSeries();
      $metadata->addCacheableDependency($event_series);
    }

    return $metadata;
  }

  /**
   * Returns the event instance with the given ID.
   *
   * @param int $event_instance_id
   *   The event instance ID.
   *
   * @return \Drupal\recurring_events\Entity\EventInstance|null
   *   The event instance, or NULL if it does not exist.
   */
  protected function getEventInstance(int $event_instance_id): ?EventInstance {
    $storage = $this->entityTypeManager->getStorage('eventinstance');
    $event_instance = $storage->load($event_instance_id);
    return $event_instance instanceof EventInstance ? $event_instance : NULL;
  }

}
