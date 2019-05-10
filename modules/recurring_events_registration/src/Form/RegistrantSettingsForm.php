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
    $config = $this->config('recurring_events_registration.registrant.config')
      ->set('show_capacity', $form_state->getValue('show_capacity'))
      ->set('limit', $form_state->getValue('limit'))
      ->set('date_format', $form_state->getValue('date_format'))
      ->set('email_notifications', $form_state->getValue('email_notifications'));

    $notification_types = [];
    \Drupal::moduleHandler()->alter('recurring_events_registration_notification_types', $notification_types);

    foreach ($notification_types as $type => $notification) {
      $config
        ->set($type . '_notification_enabled', $form_state->getValue($type . '_notification'))
        ->set($type . '_notification_subject', $form_state->getValue($type . '_notification_subject'))
        ->set($type . '_notification_body', $form_state->getValue($type . '_notification_body'));
    }
    $config->save();

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

    $notification_types = [];
    \Drupal::moduleHandler()->alter('recurring_events_registration_notification_types', $notification_types);

    foreach ($notification_types as $type => $notification) {
      $form['notifications'][$type] = [
        '#type' => 'details',
        '#title' => $notification['name'],
        '#open' => TRUE,
        '#description' => $token_help,
        '#group' => 'emails',
      ];
      $form['notifications'][$type][$type . '_notification'] = [
        '#type' => 'checkbox',
        '#title' => $notification['name'],
        '#description' => $notification['description'],
        '#default_value' => $config->get($type . '_notification_enabled'),
      ];
      $form['notifications'][$type][$type . '_notification_subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $config->get($type . '_notification_subject'),
        '#maxlength' => 180,
        '#states' => [
          'visible' => [
            'input[name="' . $type . '_notification"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['notifications'][$type][$type . '_notification_body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $config->get($type . '_notification_body'),
        '#rows' => 15,
        '#states' => [
          'visible' => [
            'input[name="' . $type . '_notification"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

}
