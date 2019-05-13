<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\recurring_events_registration\RegistrationCreationService;
use Drupal\recurring_events_registration\NotificationService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Registrant contact form.
 */
class ContactForm extends FormBase {

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The registration creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $creationService;

  /**
   * The registration notification service.
   *
   * @var \Drupal\recurring_events_registration\NotificationService
   */
  protected $notificationService;

  /**
   * The event instance object.
   *
   * @var \Drupal\recurring_events\Entity\EventInstance
   */
  protected $eventInstance;

  /**
   * Constructs a ContactForm object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request object.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The registration creation service.
   * @param \Drupal\recurring_events_registration\NotificationService $notification_service
   *   The registration notification service.
   */
  public function __construct(RequestStack $request, RegistrationCreationService $creation_service, NotificationService $notification_service) {
    $this->request = $request;
    $this->creationService = $creation_service;
    $this->notificationService = $notification_service;

    $request = $this->request->getCurrentRequest();
    $params = $request->attributes->all();
    if (!empty($params['eventinstance'])) {
      $event_instance = $params['eventinstance'];
      $this->eventInstance = $event_instance;
      $this->creationService->setEventInstance($event_instance);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('recurring_events_registration.creation_service'),
      $container->get('recurring_events_registration.notification_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurring_events_registration_contact_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $registered = $this->creationService->retrieveRegisteredParties(TRUE, FALSE, FALSE);
    $waitlisted = $this->creationService->retrieveWaitlistedParties();

    $form['header'] = [
      '#type' => 'markup',
      '#markup' => $this->t('By submitting this form you will be contacting %registered registrants and/or %waitlisted people on the waitlist.', [
        '%registered' => count($registered),
        '%waitlisted' => count($waitlisted),
      ]),
    ];

    $form['type'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Who would you like to contact?'),
      '#options' => [
        'registrants' => $this->t('Registrants'),
        'waitlist' => $this->t('Waitlisted Users'),
      ],
      '#default_value' => ['registrants'],
      '#required' => TRUE,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email Subject'),
      '#description' => $this->t('Enter the subject of the email to send to the registrants.'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Message'),
      '#description' => $this->t('Enter the message of the email to send to the registrants.'),
      '#required' => TRUE,
    ];

    $form['tokens'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form-item'],
      ],
      'tokens' => $this->notificationService->getAvailableTokens(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Email(s)'),
    ];

    $link = Link::fromTextAndUrl($this->t('Go Back to Registration List'), new Url('entity.registrant.instance_listing', [
      'eventinstance' => $this->eventInstance->id(),
    ]));

    $build['back_link'] = [
      '#type' => 'markup',
      '#prefix' => '<span class="event-register-back-link">',
      '#markup' => $link->toString(),
      '#suffix' => '</span>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();

    $params = [
      'subject' => $values['subject'],
      'body' => $values['message'],
    ];

    $registered = $values['type']['registrants'] === 'registrants' ? TRUE : FALSE;
    $waitlisted = $values['type']['waitlist'] === 'waitlist' ? TRUE : FALSE;

    $registrants = $this->creationService->retrieveRegisteredParties($registered, $waitlisted);
    $mail = \Drupal::service('plugin.manager.mail');
    if (!empty($registrants)) {
      foreach ($registrants as $registrant) {
        $params['registrant'] = $registrant;

        $to = $registrant->mail->value;
        $mail->mail('recurring_events_registration', 'custom', $to, \Drupal::languageManager()->getDefaultLanguage()->getId(), $params);
      }
      // TODO: Add success message.
    }
  }

}
