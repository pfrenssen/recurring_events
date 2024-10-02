<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\recurring_events_registration\NotificationService;
use Drupal\recurring_events_registration\RegistrationCreationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $fieldManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The registration notification service.
   *
   * @var \Drupal\recurring_events_registration\NotificationService
   */
  protected $notificationService;

  /**
   * The private tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationInformation;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('messenger'),
      $container->get('recurring_events_registration.creation_service'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('recurring_events_registration.notification_service'),
      $container->get('recurring_events_registration.tempstore.private'),
      $container->has('content_moderation.moderation_information') ? $container->get('content_moderation.moderation_information') : NULL
    );
  }

  /**
   * Construct an RegistrantForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The registrant creation service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityFieldManager $field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\recurring_events_registration\NotificationService $notification_service
   *   The registration notification service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store
   *   The private tempstore factory.
   * @param \Drupal\content_moderation\ModerationInformation $moderation_information
   *   The moderation information service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    Messenger $messenger,
    RegistrationCreationService $creation_service,
    AccountProxyInterface $current_user,
    ConfigFactory $config,
    EntityFieldManager $field_manager,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    NotificationService $notification_service,
    PrivateTempStoreFactory $temp_store,
    ModerationInformation $moderation_information = NULL) {
    $this->messenger = $messenger;
    $this->creationService = $creation_service;
    $this->currentUser = $current_user;
    $this->config = $config;
    $this->fieldManager = $field_manager;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->notificationService = $notification_service;
    $this->moderationInformation = $moderation_information;
    $this->tempStoreFactory = $temp_store;
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\recurring_events_registration\Entity\Registrant $entity */
    $entity = $this->entity;

    $event_instance = $this->routeMatch->getParameter('eventinstance');
    $event_series = $event_instance->getEventSeries();
    $editing = !$entity->isNew();

    if (empty($event_instance)) {
      return;
    }

    // Use the registration creation service to grab relevant data.
    $this->creationService->setEventInstance($event_instance);
    $availability = $event_instance->availability_count->getValue()[0]['value'];

    $waitlist = $this->creationService->hasWaitlist();
    $is_waitlisted = $entity->getWaitlist() != 0;

    // If the registrant is being edited, add the current number of seats to the
    // availability. This is to ensure that the user can change the number of
    // seats they are registering for as if they were registering for the first
    // time.
    if ($editing && $availability !== -1 && !$is_waitlisted) {
      $availability += $entity->getSeats();
    }

    // Since the availability might change between the time the form was loaded
    // and the time it was submitted, keep track of the original availability
    // as was shown to the user, so we can show helpful messages if needed.
    $form['#cache']['contexts'][] = 'session';
    $temp_store = $this->tempStoreFactory->get('recurring_events_registration_form');
    if (!$temp_store->get($event_instance->uuid())) {
      $temp_store->set($event_instance->uuid(), $availability);
    }

    // Determine the maximum number of seats that can be registered, taking into
    // account the configured maximum seats per registrant and the remaining
    // availability. Also, if we are out of space, but there is a waitlist, we
    // can still register the maximum number of seats.
    $out_of_space_with_waitlist = $availability === 0 && $waitlist;
    $max_seats = (int) $event_series->event_registration->max_seats;
    if ($availability !== -1 && !$out_of_space_with_waitlist && !$is_waitlisted) {
      $max_seats = min($max_seats, $availability);
    }

    $registration_open = $this->creationService->registrationIsOpen();
    $reg_type = $this->creationService->getRegistrationType();

    $form['notifications'] = [
      '#type' => 'container',
      '#weight' => -100,
      '#attributes' => [
        'class' => ['registration-notifications'],
      ],
      // Do not show notifications if we are in edit mode.
      '#printed' => $editing,
    ];

    // If space has run out, but there is a waitlist.
    $form['notifications']['waitlist_notification'] = [
      '#type' => 'container',
      '#access' => ($availability == 0 && $waitlist && $registration_open),
      '#attributes' => [
        'class' => ['registration-notification-message'],
      ],
      'title' => [
        '#type' => 'markup',
        '#prefix' => '<h3 class="registration-notice-title">',
        '#markup' => $this->t('Registration full.'),
        '#suffix' => '</h3>',
      ],
      'message' => [
        '#type' => 'markup',
        '#prefix' => '<p class="registration-message">',
        '#markup' => $this->t('Unfortunately, there are no spaces left for this @type. However, we can add you to the waitlist. If a space becomes available, the first registrant on the waitlist will be automatically registered.', [
          '@type' => $reg_type === 'series' ? 'series' : 'event',
        ]),
        '#suffix' => '</p>',
      ],
    ];

    // If space has run out, but there is no waitlist.
    $form['notifications']['availability_notification'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['registration-notification-message'],
      ],
      '#access' => ($availability == 0 && !$waitlist && $registration_open),
      'title' => [
        '#type' => 'markup',
        '#prefix' => '<h3 class="registration-notice-title">',
        '#markup' => $this->t('Registration full.'),
        '#suffix' => '</h3>',
      ],
      'message' => [
        '#type' => 'markup',
        '#prefix' => '<p class="registration-message">',
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
        'class' => ['registration-notification-message'],
      ],
      '#access' => !$registration_open,
      'title' => [
        '#type' => 'markup',
        '#prefix' => '<h3 class="registration-notice-title">',
        '#markup' => $this->t('Registration is closed.'),
        '#suffix' => '</h3>',
      ],
      'message' => [
        '#type' => 'markup',
        '#prefix' => '<p class="registration-message">',
        '#markup' => $this->t('Unfortunately, registration for this @type is closed.', [
          '@type' => $reg_type === 'series' ? 'series' : 'event',
        ]),
        '#suffix' => '</p>',
      ],
    ];

    if ($this->config('recurring_events_registration.registrant.config')->get('show_capacity')) {
      $form['availability'] = [
        '#type' => 'markup',
        '#prefix' => '<span class="registration-availability">',
        '#markup' => $this->t('Spaces Available: @availability', [
          '@availability' => ($availability == -1) ? $this->t('Unlimited') : $availability,
        ]),
        '#suffix' => '</span>',
        '#weight' => -99,
      ];
    }

    // If only a single seat can be registered, it is pointless to show the
    // option.
    if ($max_seats < 2) {
      $form['seats']['#access'] = FALSE;
    }
    else {
      $form['seats']['widget'][0]['value']['#min'] = 1;
      $form['seats']['widget'][0]['value']['#max'] = $max_seats;
      // We are dynamically setting the maximum number of seats based on
      // availability. However this might have changed in between the time the
      // form was loaded and the time it was submitted, since somebody might
      // have snatched some seats while the user was filling out the form.
      // Replace the standard validation to avoid unhelpful errors in this case.
      $form['seats']['widget'][0]['value']['#element_validate'] = [[$this, 'validateSeats']];
    }

    $form['add_to_waitlist'] = [
      '#type' => 'hidden',
      '#value' => 2,
      '#weight' => 98,
    ];

    $link = $event_instance->toLink($this->t('Go Back to Event Details'));

    $form['back_link'] = [
      '#type' => 'markup',
      '#prefix' => '<span class="registration-back-link">',
      '#markup' => $link->toString(),
      '#suffix' => '</span>',
      '#weight' => 100,
    ];

    if ($this->currentUser->hasPermission('modify registrant waitlist') && $waitlist) {
      $form['add_to_waitlist']['#type'] = 'select';
      $form['add_to_waitlist']['#options'] = [
        2 => $this->t('If there is no room'),
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ];
      $form['add_to_waitlist']['#title'] = $this->t('Add user to waitlist');
      $value = !$entity->isNew() ? $entity->getWaitlist() : 2;
      $form['add_to_waitlist']['#default_value'] = $value;
      unset($form['add_to_waitlist']['#value']);
    }

    $this->hideFormFields($form, $form_state);

    // Because the form gets modified depending on the number of registrations
    // we need to invalidate it when the list of registrants changes.
    $form['#cache']['tags'][] = 'registrant_list';

    $save_label = $this->t('Register');
    if ($editing) {
      $save_label = $this->t('Update Registration');
    }
    $form['actions']['submit']['#value'] = $save_label;

    // Hide the form if user is not allowed to register for this series.
    $permitted_roles = $this->creationService->registrationPermittedRoles();
    $role_permitted = empty($permitted_roles);
    if (!$role_permitted) {
      $user_roles = $this->currentUser->getRoles();
      if (in_array('administrator', $user_roles)) {
        $role_permitted = TRUE;
      }
      else {
        foreach ($user_roles as $user_role) {
          if (in_array($user_role, $permitted_roles)) {
            $role_permitted = TRUE;
            break;
          }
        }
      }
    }
    if (!$role_permitted) {
      $this->messenger->addMessage('You are not allowed to register for events in this series.', $this->messenger::TYPE_WARNING);
      $form['#disabled'] = TRUE;
    }
    return $form;
  }

  /**
   * Hide form fields depending on registration status.
   *
   * @var array $form
   *   The form configuration array.
   * @var Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   */
  protected function hideFormFields(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\recurring_events_registration\Entity\Registrant $entity */
    $entity = $this->entity;
    $new = $entity->isNew();
    if ($new) {
      $event_instance = $this->routeMatch->getParameter('eventinstance');
    }
    else {
      $event_instance = $entity->getEventInstance();
    }

    $form_fields = $this->fieldManager->getFieldDefinitions('registrant', $this->entity->getBundle());

    $availability = $event_instance?->availability_count->getValue()[0]['value'] ?? 0;
    $waitlist = $this->creationService->hasWaitlist();
    $registration_open = $this->creationService->registrationIsOpen();

    // Prevent the form being displayed if registration is closed, or there are
    // no spaces left, and no waitlist.
    if ((($availability === 0 && !$waitlist) || !$registration_open) && $new) {
      foreach ($form_fields as $field_name => $field) {
        if (isset($form[$field_name]) && $new) {
          $form[$field_name]['#printed'] = TRUE;
        }
      }
      $form['actions']['#printed'] = TRUE;
      if (isset($form['availability'])) {
        $form['availability']['#printed'] = TRUE;
      }
      if (isset($form['add_to_waitlist'])) {
        $form['add_to_waitlist']['#printed'] = TRUE;
      }
    }

    if (!$this->currentUser->hasPermission('modify registrant author')) {
      $form['user_id']['#access'] = FALSE;
    }

    if (!$this->currentUser->hasPermission('administer registrant entity')) {
      $form['revision_information']['#access'] = FALSE;
      $form['status']['#access'] = FALSE;
    }
  }

  /**
   * Form element validation handler for the number of seats.
   */
  public function validateSeats(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    if ($value === '') {
      return;
    }

    $name = empty($element['#title']) ? $element['#parents'][0] : $element['#title'];

    // Ensure the input is numeric.
    if (!is_numeric($value)) {
      $form_state->setError($element, t('%name must be a number.', ['%name' => $name]));
      return;
    }

    if ($value < 1) {
      $form_state->setError($element, t('Please select at least one seat.'));
      return;
    }
    $event_instance = $this->routeMatch->getParameter('eventinstance');
    $event_series = $event_instance->getEventSeries();
    $max_seats = (int) $event_series->event_registration->max_seats;
    if ($value > $max_seats) {
      $form_state->setError($element, t('You cannot register more than @max_seats seats.', ['@max_seats' => $max_seats]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\recurring_events\Entity\Registrant $entity */
    $entity = $this->entity;
    $event_instance = $this->routeMatch->getParameter('eventinstance');
    $event_series = $event_instance->getEventSeries();
    // Use the registration creation service to grab relevant data.
    $this->creationService->setEventInstance($event_instance);
    // Just to be sure we have a fresh copy of the event series.
    $this->creationService->setEventSeries($event_series);
    $temp_store = $this->tempStoreFactory->get('recurring_events_registration_form');

    $availability = $event_instance->availability_count->getValue()[0]['value'];
    // If the registrant is being edited, add the current number of seats to the
    // availability. This is to ensure that the user can change the number of
    // seats they are registering for as if they were registering for the first
    // time.
    if (!$entity->isNew() && $availability !== -1 && $entity->getWaitlist() == 0) {
      $availability += $entity->getSeats();
    }

    $requested_seats = (int) $form_state->getValue('seats')[0]['value'];
    $out_of_capacity = $availability !== -1 && $requested_seats > $availability;

    $waitlist = $this->creationService->hasWaitlist();
    $add_to_waitlist = $form_state->getValue('add_to_waitlist');
    // Handle the automatic waitlist addition.
    if ($add_to_waitlist == 2) {
      $add_to_waitlist = 0;
      // If there was originally enough space the user will not expect to be put
      // on the waitlist. In case someone else registered in the meantime and
      // now there is no longer enough space, we need to inform the user.
      $original_availability = $temp_store->get($event_instance->uuid());
      $ran_out_of_space = !empty($original_availability) && $out_of_capacity && $requested_seats <= $original_availability;
      if ($out_of_capacity && $waitlist && !$ran_out_of_space) {
        $add_to_waitlist = 1;
      }
      $form_state->setValue('add_to_waitlist', $add_to_waitlist);
    }
    $temp_store->delete($event_instance->uuid());

    // Only perform the checks if the entity is new.
    if ($entity->isNew()) {
      $registration_open = $this->creationService->registrationIsOpen();

      // Registration has closed.
      if (!$registration_open) {
        $form_state->setError($form, $this->t('Unfortunately, registration has closed.'));
      }
      // Capacity is full, there is a waitlist, but user was not being added to
      // the waitlist.
      elseif (!$add_to_waitlist && $out_of_capacity && $waitlist) {
        $form_state->setError($form, $this->t('Unfortunately, this event is now full and you must join the waitlist.'));
      }
      // There are no spaces left, and there is no waitlist.
      elseif ($out_of_capacity && !$waitlist) {
        $form_state->setError($form, $this->t('Unfortunately, this event is now full.'));
      }
    }
    else {
      // @todo This seems to be unnecessary. The waitlist is set in ::save().
      if ($this->currentUser->hasPermission('modify registrant waitlist')) {
        // Update the user's waitlist value.
        $entity->setWaitlist($form_state->getValue('add_to_waitlist'));
      }

      if ($out_of_capacity && !$add_to_waitlist) {
        $form_state->setError($form['seats'], $this->t('Unfortunately the event is now full and additional seats are no longer available.'));
      }
    }

    $unique_email_address = $this->creationService->registrationUniqueEmailAddress();
    if ($unique_email_address) {
      $email_address = $form_state->getValue('email');
      $ignored_registrant_id = ($entity->isNew() ? NULL : (int) $entity->id());
      $existing_registration_id = $this->creationService->hasUserRegisteredByEmail($email_address[0]['value'], $ignored_registrant_id);
      if ($existing_registration_id) {
        // If a registration already exists for the email display an error.
        $form_state->setErrorByName('email', $this->t("You've already registered for this event."));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $event_instance = $this->routeMatch->getParameter('eventinstance');
    $event_series = $event_instance->getEventSeries();

    /** @var \Drupal\recurring_events\Entity\RegistrantInterface $entity */
    $entity = $this->entity;

    // Use the registration creation service to grab relevant data.
    $this->creationService->setEventInstance($event_instance);
    // Just to be sure we have a fresh copy of the event series.
    $this->creationService->setEventSeries($event_series);

    $availability = $event_instance->availability_count->getValue()[0]['value'];
    $waitlist = $this->creationService->hasWaitlist();
    $registration_open = $this->creationService->registrationIsOpen();
    $reg_type = $this->creationService->getRegistrationType();
    $registration = $this->creationService->hasRegistration();

    if (isset($this->notificationService) && isset($this->entity)) {
      $this->notificationService->setEntity($this->entity);
    }
    if ($registration && $registration_open && ($availability > 0 || $availability == -1 || $waitlist)) {
      $add_to_waitlist = (int) $form_state->getValue('add_to_waitlist');
      $this->entity->setEventSeries($event_series);
      $this->entity->setEventInstance($event_instance);
      $this->entity->setWaitlist($add_to_waitlist);
      $this->entity->setRegistrationType($reg_type);
      $status = parent::save($form, $form_state);

      switch ($status) {
        case SAVED_NEW:
          $message = $this->config('recurring_events_registration.registrant.config')->get('successfully_registered');
          if ($add_to_waitlist) {
            $message = $this->config('recurring_events_registration.registrant.config')->get('successfully_registered_waitlist');
          }
          break;

        case SAVED_UPDATED:
          $message = $this->t('Registrant successfully updated');
          break;

        default:
          $message = $this->config('recurring_events_registration.registrant.config')->get('successfully_registered');
          if ($add_to_waitlist) {
            $message = $this->config('recurring_events_registration.registrant.config')->get('successfully_registered_waitlist');
          }
          break;
      }

      $this->messenger->addMessage(new FormattableMarkup($this->notificationService->parseTokenizedString($message), []));
    }
    else {
      if ($this->entity->isNew()) {
        $message = $this->config('recurring_events_registration.registrant.config')->get('registration_closed');
      }
      else {
        $message = $this->t('Registrant successfully updated');
      }
      $this->messenger->addMessage(new FormattableMarkup($this->notificationService->parseTokenizedString($message), []));
    }

    $redirect_choice = $this->config('recurring_events_registration.registrant.config')->get('insert_redirect_choice');
    switch ($redirect_choice) {

      case 'instance':
        $form_state->setRedirect('entity.eventinstance.canonical', ['eventinstance' => $event_instance->id()]);
        break;

      case 'series':
        $form_state->setRedirect('entity.eventseries.canonical', ['eventseries' => $event_series->id()]);
        break;

      case 'other':
        $url = $this->config('recurring_events_registration.registrant.config')->get('insert_redirect_other');
        $response = new TrustedRedirectResponse(Url::fromUri($url)->toString());
        $form_state->setResponse($response);
        break;

      default:
        $form_state->setRedirectUrl(Url::fromRoute('<current>'));
        break;
    }

    // @todo Remove when https://www.drupal.org/node/3173241 drops.
    if ($this->moderationInformation) {
      if ($this->moderationInformation->hasPendingRevision($entity) && $entity->hasLinkTemplate('latest-version')) {
        $form_state->setRedirect('entity.registrant.latest_version', [
          'eventinstance' => $entity->getEventInstance()?->id() ?? 0,
          'registrant' => $entity->id(),
        ]);
      }
    }
  }

}
