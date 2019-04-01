<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\recurring_events_registration\RegistrationCreationService;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Form controller for Registrant edit forms.
 *
 * @ingroup recurring_events_registration
 */
class RegistrantForm extends ContentEntityForm {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $creationService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('messenger'),
      $container->get('recurring_events.registration_creation_service'),
      $container->get('current_user')
    );
  }

  /**
   * Construct a EventSeriesForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The registrant creation service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, Messenger $messenger, RegistrationCreationService $creation_service, AccountProxyInterface $current_user) {
    $this->messenger = $messenger;
    $this->creationService = $creation_service;
    $this->currentUser = $current_user;
    parent::__construct($entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\recurring_events_registration\Entity\Registrant */
    $form = parent::buildForm($form, $form_state);

    /* @var $entity \Drupal\recurring_events_registration\Entity\Registrant */
    $entity = $this->entity;

    if (!$entity->isNew()) {
      $event_instance = $entity->getEventInstance();
      $editing = TRUE;
    }
    else {
      $event_id = \Drupal::routeMatch()->getParameter('eventinstance');
      $event_instance = \Drupal::entityTypeManager()->getStorage('eventinstance')->load($event_id);
      $editing = FALSE;
    }

    if (empty($event_instance)) {
      throw new NotFoundHttpException();
    }

    $this->creationService->setEvents($event_instance);

    $event_series = $event_instance->getEventSeries();

    $form_state->setTemporaryValue('series', $event_series);
    $form_state->setTemporaryValue('event', $event_instance);

    $availability = $this->creationService->retrieveAvailability();
    $waitlist = $this->creationService->hasWaitlist();
    $registration_open = $this->creationService->registrationIsOpen();
    $reg_type = $this->creationService->getRegistrationType();

    $form['notifications'] = [
      '#type' => 'container',
      '#weight' => -100,
      '#attributes' => [
        'class' => ['event-register-notifications'],
      ],
      '#printed' => $editing,
    ];

    // If space has run out, but there is a waitlist.
    $form['notifications']['waitlist_notification'] = [
      '#type' => 'container',
      '#access' => ($availability == 0 && $waitlist && $registration_open),
      '#attributes' => [
        'class' => ['event-register-notification-message'],
      ],
      'title' => [
        '#type' => 'markup',
        '#prefix' => '<h3 class="event-register-notice-title">',
        '#markup' => $this->t('We cannot complete your registration'),
        '#suffix' => '</h3>',
      ],
      'message' => [
        '#type' => 'markup',
        '#prefix' => '<p class="event-register-message">',
        '#markup' => $this->t('Unfortunately, there are no spaces left for this @type. However, we can add you to the waitlist. If a space becomes available, you will be notified via email and automatically registered.', [
          '@type' => $reg_type === 'series' ? 'series' : 'event',
        ]),
        '#suffix' => '</p>',
      ],
    ];

    // If space has run out, but there is no waitlist.
    $form['notifications']['availability_notification'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['event-register-notification-message'],
      ],
      '#access' => ($availability == 0 && !$waitlist && $registration_open),
      'title' => [
        '#type' => 'markup',
        '#prefix' => '<h3 class="event-register-notice-title">',
        '#markup' => $this->t('We cannot complete your registration.'),
        '#suffix' => '</h3>',
      ],
      'message' => [
        '#type' => 'markup',
        '#prefix' => '<p class="event-register-message">',
        '#markup' => $this->t('Unfortunately, this @type is at capacity and there are no spaces available.', [
          '@type' => $reg_type === 'series' ? 'series' : 'event',
        ]),
        '#suffix' => '</p>',
      ],
    ];

    // If registration is not open.
    $form['notifications']['registration_closed'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['event-register-notification-message'],
      ],
      '#access' => !$registration_open,
      'title' => [
        '#type' => 'markup',
        '#prefix' => '<h3 class="event-register-notice-title">',
        '#markup' => $this->t('Registration is closed.'),
        '#suffix' => '</h3>',
      ],
      'message' => [
        '#type' => 'markup',
        '#prefix' => '<p class="event-register-message">',
        '#markup' => $this->t('Unfortunately, registration for this @type is closed.', [
          '@type' => $reg_type === 'series' ? 'series' : 'event',
        ]),
        '#suffix' => '</p>',
      ],
    ];

    $form['availability'] = [
      '#type' => 'markup',
      '#prefix' => '<span class="event-register-availability">',
      '#markup' => $this->t('Spaces Available: @availability', ['@availability' => $availability]),
      '#suffix' => '</span>',
    ];

    $add_to_waitlist = '0';

    $form['add_to_waitlist'] = [
      '#type' => 'hidden',
      '#value' => $add_to_waitlist,
      '#weight' => 98,
    ];

    $link = $event_instance->toLink($this->t('Go Back to Event Details'));

    $form['back_link'] = [
      '#type' => 'markup',
      '#prefix' => '<span class="event-register-back-link">',
      '#markup' => $link->toString(),
      '#suffix' => '</span>',
      '#weight' => 100,
    ];

    if ($this->currentUser->hasPermission('modify registrant waitlist')) {
      $form['add_to_waitlist']['#type'] = 'select';
      $form['add_to_waitlist']['#options'] = [
        '1' => $this->t('Yes'),
        '0' => $this->t('No'),
      ];
      $form['add_to_waitlist']['#title'] = $this->t('Add user to waitlist');
      $value = !$entity->isNew() ? $entity->getWaitlist() : $add_to_waitlist;
      $form['add_to_waitlist']['#default_value'] = $value;
      unset($form['add_to_waitlist']['#value']);
    }

    // Because the form gets personalized if you've registered before, we want
    // to prevent caching.
    $form['#cache'] = ['max-age' => 0];
    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Registrant.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Registrant.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.registrant.canonical', ['registrant' => $entity->id()]);
  }

}
