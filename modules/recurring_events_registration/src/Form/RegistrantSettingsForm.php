<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class RegistrantSettingsForm.
 *
 * @ingroup recurring_events_registration
 */
class RegistrantSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'registrant_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['recurring_events_registration.registrant.config'];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('recurring_events_registration.registrant.config')
      ->set('show_capacity', $form_state->getValue('show_capacity'))
      ->set('limit', $form_state->getValue('limit'))
      ->set('date_format', $form_state->getValue('date_format'))
      ->set('email_notifications', $form_state->getValue('email_notifications'))
      ->set('registration_notification_enabled', $form_state->getValue('registration_notification'))
      ->set('registration_notification_subject', $form_state->getValue('registration_notification_subject'))
      ->set('registration_notification_body', $form_state->getValue('registration_notification_body'))
      ->set('waitlist_notification_enabled', $form_state->getValue('waitlist_notification'))
      ->set('waitlist_notification_subject', $form_state->getValue('waitlist_notification_subject'))
      ->set('waitlist_notification_body', $form_state->getValue('waitlist_notification_body'))
      ->set('promotion_notification_enabled', $form_state->getValue('promotion_notification'))
      ->set('promotion_notification_subject', $form_state->getValue('promotion_notification_subject'))
      ->set('promotion_notification_body', $form_state->getValue('promotion_notification_body'))
      ->set('cancellation_notification_enabled', $form_state->getValue('cancellation_notification'))
      ->set('cancellation_notification_subject', $form_state->getValue('cancellation_notification_subject'))
      ->set('cancellation_notification_body', $form_state->getValue('cancellation_notification_body'))
      ->set('modification_notification_enabled', $form_state->getValue('modification_notification'))
      ->set('modification_notification_subject', $form_state->getValue('modification_notification_subject'))
      ->set('modification_notification_body', $form_state->getValue('modification_notification_body'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Defines the settings form for Registrant entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('recurring_events_registration.registrant.config');
    $form['process'] = [
      '#type' => 'details',
      '#title' => $this->t('Registration Form'),
      '#open' => TRUE,
    ];

    $form['process']['show_capacity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Capacity?'),
      '#description' => $this->t('When users are registering for events, show the available capacity?'),
      '#default_value' => $config->get('show_capacity'),
    ];

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Registrant Display'),
      '#open' => TRUE,
    ];

    $form['display']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Registrant Items'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the number of items to show per page in the default registrant listing table.'),
      '#default_value' => $config->get('limit'),
    ];

    $php_date_url = Url::fromUri('https://secure.php.net/manual/en/function.date.php');
    $php_date_link = Link::fromTextAndUrl($this->t('PHP date/time format'), $php_date_url);

    $form['display']['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registrant Date Format'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the @link used when listing registrants. Default is F jS, Y h:iA.', [
        '@link' => $php_date_link->toString(),
      ]),
      '#default_value' => $config->get('date_format'),
    ];

    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Notifications'),
      '#open' => TRUE,
    ];

    $form['notifications']['email_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Email Notifications?'),
      '#description' => $this->t('Send email notifications during registration or event updates?'),
      '#default_value' => $config->get('email_notifications'),
    ];

    $form['notifications']['emails'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
      '#states' => [
        'visible' => [
          'input[name="email_notifications"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $token_help = '';
    $token_service = \Drupal::token();
    $all_tokens = $token_service->getInfo();
    $tokens = [];
    $relevant_tokens = [
      'eventseries',
      'eventinstance',
      'registrant',
    ];
    foreach ($relevant_tokens as $token_prefix) {
      if (!empty($all_tokens['tokens'][$token_prefix])) {
        foreach ($all_tokens['tokens'][$token_prefix] as $token_key => $value) {
          $tokens[] = '[' . $token_prefix . ':' . $token_key . ']';
        }
      }
    }

    $token_help = $this->t('Available variables are: @tokens.', [
      '@tokens' => implode(', ', $tokens),
    ]);

    // Registration notifications.
    $form['notifications']['registration'] = [
      '#type' => 'details',
      '#title' => $this->t('Registration Notification'),
      '#open' => TRUE,
      '#description' => $this->t('Enable and configure registration notifications') . ' ' . $token_help,
      '#group' => 'emails',
    ];
    $form['notifications']['registration']['registration_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Registration Notification'),
      '#description' => $this->t('Send an email to a registrant to confirm they were registered for an event?'),
      '#default_value' => $config->get('registration_notification_enabled'),
    ];
    $form['notifications']['registration']['registration_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('registration_notification_subject'),
      '#maxlength' => 180,
      '#states' => [
        'visible' => [
          'input[name="registration_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['notifications']['registration']['registration_notification_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('registration_notification_body'),
      '#rows' => 15,
      '#states' => [
        'visible' => [
          'input[name="registration_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Waitlist notifications.
    $form['notifications']['waitlist'] = [
      '#type' => 'details',
      '#title' => $this->t('Waitlist Notification'),
      '#description' => $this->t('Enable and configure waitlist notifications') . ' ' . $token_help,
      '#group' => 'emails',
    ];
    $form['notifications']['waitlist']['waitlist_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Waitlist Notification'),
      '#description' => $this->t('Send an email to a registrant to confirm they were added to the waitlist?'),
      '#default_value' => $config->get('waitlist_notification_enabled'),
    ];
    $form['notifications']['waitlist']['waitlist_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('waitlist_notification_subject'),
      '#maxlength' => 180,
      '#states' => [
        'visible' => [
          'input[name="waitlist_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['notifications']['waitlist']['waitlist_notification_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('waitlist_notification_body'),
      '#rows' => 15,
      '#states' => [
        'visible' => [
          'input[name="waitlist_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Promotion notifications.
    $form['notifications']['promotion'] = [
      '#type' => 'details',
      '#title' => $this->t('Promotion Notification'),
      '#description' => $this->t('Enable and configure promotion notifications') . ' ' . $token_help,
      '#group' => 'emails',
    ];
    $form['notifications']['promotion']['promotion_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Promotion Notification'),
      '#description' => $this->t('Send an email to a registrant to confirm they were promoted from the wailist?'),
      '#default_value' => $config->get('promotion_notification_enabled'),
    ];
    $form['notifications']['promotion']['promotion_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('promotion_notification_subject'),
      '#maxlength' => 180,
      '#states' => [
        'visible' => [
          'input[name="promotion_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['notifications']['promotion']['promotion_notification_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('promotion_notification_body'),
      '#rows' => 15,
      '#states' => [
        'visible' => [
          'input[name="promotion_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Cancellation notifications.
    $form['notifications']['cancellation'] = [
      '#type' => 'details',
      '#title' => $this->t('Cancellation Notification'),
      '#description' => $this->t('Enable and configure cancellation notifications') . ' ' . $token_help,
      '#group' => 'emails',
    ];
    $form['notifications']['cancellation']['cancellation_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cancellation Notification'),
      '#description' => $this->t('Send an email to a registrant to confirm an event cancellation?'),
      '#default_value' => $config->get('cancellation_notification_enabled'),
    ];
    $form['notifications']['cancellation']['cancellation_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('cancellation_notification_subject'),
      '#maxlength' => 180,
      '#states' => [
        'visible' => [
          'input[name="cancellation_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['notifications']['cancellation']['cancellation_notification_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('cancellation_notification_body'),
      '#rows' => 15,
      '#states' => [
        'visible' => [
          'input[name="cancellation_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Modification notifications.
    $form['notifications']['modification'] = [
      '#type' => 'details',
      '#title' => $this->t('Modification Notification'),
      '#description' => $this->t('Enable and configure modification notifications') . ' ' . $token_help,
      '#group' => 'emails',
    ];
    $form['notifications']['modification']['modification_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Modification Notification'),
      '#description' => $this->t('Send an email to a registrant to confirm an event modification?'),
      '#default_value' => $config->get('modification_notification_enabled'),
    ];
    $form['notifications']['modification']['modification_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('modification_notification_subject'),
      '#maxlength' => 180,
      '#states' => [
        'visible' => [
          'input[name="modification_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['notifications']['modification']['modification_notification_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('modification_notification_body'),
      '#rows' => 15,
      '#states' => [
        'visible' => [
          'input[name="modification_notification"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

}
