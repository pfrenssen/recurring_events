<?php

namespace Drupal\recurring_events_registration\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events_registration\Entity\RegistrantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RegistrantController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(RendererInterface $renderer, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Check if registration is enabled.
   *
   * @param Drupal\recurring_events\Entity\EventInstance $eventinstance
   *   The eventinstance entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether access is allowed based on whether registration is enabled.
   */
  public static function hasRegistration(EventInstance $eventinstance) {
    if (!empty($eventinstance)) {
      // Static function, so we need to request the service statically, not
      // through dependency injection.
      $service = \Drupal::service('recurring_events_registration.creation_service');
      $service->setEventInstance($eventinstance);
      if ($service->hasRegistration()) {
        return AccessResult::allowed();
      }
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

  /**
   * Check if the user can contact the registrants.
   *
   * @param Drupal\recurring_events\Entity\EventInstance $eventinstance
   *   The eventinstance entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether access is allowed based on whether registration is enabled.
   */
  public function canContactRegistrants(EventInstance $eventinstance) {
    if (!empty($eventinstance)) {
      $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      return AccessResult::allowedIfHasPermission($account, 'contact registrants');
    }
    return AccessResult::forbidden();
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
