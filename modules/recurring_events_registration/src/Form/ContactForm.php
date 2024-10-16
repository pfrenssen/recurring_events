<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Url;
use Drupal\recurring_events_registration\Enum\RegistrationType;
use Drupal\recurring_events_registration\NotificationService;
use Drupal\recurring_events_registration\RegistrationCreationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mail;

  /**
   * The event instance object.
   *
   * @var \Drupal\recurring_events\Entity\EventInstance
   */
  protected $eventInstance;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a ContactForm object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request object.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The registration creation service.
   * @param \Drupal\recurring_events_registration\NotificationService $notification_service
   *   The registration notification service.
   * @param \Drupal\Core\Mail\MailManager $mail
   *   The mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(RequestStack $request, RegistrationCreationService $creation_service, NotificationService $notification_service, MailManager $mail, LanguageManagerInterface $language_manager) {
    $this->request = $request;
    $this->creationService = $creation_service;
    $this->notificationService = $notification_service;
    $this->mail = $mail;
    $this->languageManager = $language_manager;

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
      $container->get('recurring_events_registration.notification_service'),
      $container->get('plugin.manager.mail'),
      $container->get('language_manager')
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
      '#markup' => $this->t('By submitting this form you will be contacting %registered registrants and/or %waitlisted people on the waitlist for the %name @type.', [
        '%registered' => count($registered),
        '%waitlisted' => count($waitlisted),
        '%name' => $this->eventInstance->title->value,
        '@type' => $this->creationService->getRegistrationType() === RegistrationType::SERIES ? $this->t('series') : $this->t('event'),
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

    $form['back_link'] = [
      '#type' => 'markup',
      '#prefix' => '<span class="register-back-link">',
      '#markup' => $link->toString(),
      '#suffix' => '</span>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $params = [
      'subject' => $values['subject'],
      'body' => $values['message'],
    ];

    $registered = $values['type']['registrants'] === 'registrants' ? TRUE : FALSE;
    $waitlisted = $values['type']['waitlist'] === 'waitlist' ? TRUE : FALSE;

    $registrants = $this->creationService->retrieveRegisteredParties($registered, $waitlisted);

    $reg_count = $wait_count = 0;

    if (!empty($registrants)) {
      foreach ($registrants as $registrant) {
        $params['registrant'] = $registrant;

        $to = $registrant->email->value;
        $this->mail->mail('recurring_events_registration', 'custom', $to, $this->languageManager->getDefaultLanguage()->getId(), $params);

        if ($registrant->getWaitlist() == '1') {
          $wait_count++;
        }
        else {
          $reg_count++;
        }
      }

      $this->messenger()->addMessage($this->t('Successfully sent emails to %reg_count registrants and %wait_count waitlisted users.', [
        '%reg_count' => $reg_count,
        '%wait_count' => $wait_count,
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('No emails were sent as there were no registrants or waitlist users to contact.'));
    }
  }

}
