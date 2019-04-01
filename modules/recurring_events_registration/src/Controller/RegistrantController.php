<?php

namespace Drupal\recurring_events_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\recurring_events\EventInterface;
use Drupal\recurring_events_registration\Entity\RegistrantInterface;

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

  /**
   * Show the registration complete page.
   *
   * @param \Drupal\recurring_events\EventInterface $eventinstance
   *   The event for which the user registered.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function registrationComplete(EventInterface $eventinstance) {
    $build = [];

    $build['success_wrapper'] = [
      '#type' => 'container',
      '#weight' => -100,
      '#attributes' => [
        'class' => ['event-register-success-wrapper'],
      ],
    ];

    $build['success_wrapper']['title'] = [
      '#type' => 'markup',
      '#prefix' => '<h3 class="event-register-success-title">',
      '#markup' => $this->t('Registration Complete!'),
      '#suffix' => '</h3>',
    ];

    $build['success_wrapper']['message'] = [
      '#type' => 'markup',
      '#prefix' => '<p class="event-register-message">',
      '#markup' => $this->t("You're all set for this event. Please check your email for further details on the event and event updates."),
      '#suffix' => '</p>',
    ];

    $link = Link::fromTextAndUrl($this->t('Go Back to Event Details'), new Url('entity.eventinstance.canonical', [
      'eventinstance' => $eventinstance->id(),
    ]));

    $build['back_link'] = [
      '#type' => 'markup',
      '#prefix' => '<span class="event-register-back-link">',
      '#markup' => $link->toString(),
      '#suffix' => '</span>',
    ];

    $button = Link::fromTextAndUrl($this->t('Register Another Attendee'), new Url('entity.registrant.add_form', [
      'eventinstance' => $eventinstance->id(),
    ]));

    $build['register_button'] = [
      '#type' => 'markup',
      '#prefix' => '<span class="event-register-register-button">',
      '#markup' => $button->toString(),
      '#suffix' => '</span>',
    ];

    return $build;
  }

  /**
   * Show the registration waitlist complete page.
   *
   * @param \Drupal\recurring_events\EventInterface $eventinstance
   *   The event for which the user registered.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function registrationWaitlistComplete(EventInterface $eventinstance) {
    $build = $this->registrationComplete($eventinstance);

    $build['success_wrapper']['title']['#markup'] = $this->t('Registration Waitlisting Complete!');
    $build['success_wrapper']['message']['#markup'] = $this->t("You've been added to the waitlist for this event. Please check your email for further details on the event and event updates.");

    return $build;
  }

}
