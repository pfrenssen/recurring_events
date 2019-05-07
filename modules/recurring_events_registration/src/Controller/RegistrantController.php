<?php

namespace Drupal\recurring_events_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\recurring_events_registration\Entity\RegistrantInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\recurring_events\Entity\EventInstance;

/**
 * The RegistrantController class.
 */
class RegistrantController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a RegistrantController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Check if registration is enabled.
   *
   * @param Drupal\recurring_events\Entity\EventInstance $eventinstance
   *   The eventinstance entity.
   *
   * @return Drupal\Core\Access\AccessResultInterface
   *   Whether access is allowed based on whether registration is enabled.
   */
  public static function hasRegistration(EventInstance $eventinstance) {
    if (!empty($eventinstance)) {
      $service = \Drupal::service('recurring_events_registration.creation_service');
      $service->setEvents($eventinstance);
      if ($service->hasRegistration()) {
        return AccessResult::allowed();
      }
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

  /**
   * Return a dynamic page title for a Registrant.
   *
   * @param Drupal\recurring_events_registration\Entity\RegistrantInterface $registrant
   *   The entity for which to generate a page title.
   *
   * @return string
   *   The page title.
   */
  public function getTitle(RegistrantInterface $registrant) {
    return $registrant->field_first_name->value . ' ' . $registrant->field_last_name->value;
  }

}
