<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\recurring_events_registration\Model\RegistrantTypeNotificationSetting;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing a registrant type.
 */
class RegistrantTypeForm extends EntityForm {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a RegistrantTypeForm object.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(Messenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;

    /** @var \Drupal\recurring_events_registration\Entity\RegistrantTypeInterface $registrant_type */
    $registrant_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 32,
      '#default_value' => $registrant_type->label(),
      '#description' => $this->t("Label for the registrant type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $registrant_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\recurring_events_registration\Entity\RegistrantType::load',
      ],
      '#disabled' => !$registrant_type->isNew(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $registrant_type->getDescription(),
    ];

    $notification_settings = $registrant_type->getNotificationSettings();

    $notification_types = [];
    $this->moduleHandler->alter('recurring_events_registration_notification_types', $notification_types);

    $form['notifications'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Email notifications'),
    ];

    foreach ($notification_types as $type => $notification) {
      $form['notifications'][$type] = [
        '#type' => 'details',
        '#title' => $notification['name'],
        '#open' => TRUE,
        '#group' => 'notifications',
      ];
      $form['notifications'][$type]['overridden'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Override global settings'),
        '#default_value' => $notification_settings[$type]->isOverridden(),
      ];
      $form['notifications'][$type]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $notification_settings[$type]->isEnabled(),
        '#states' => [
          'visible' => [
            ':input[name="notifications[' . $type . '][overridden]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['notifications'][$type]['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $notification_settings[$type]->getSubject(),
        '#states' => [
          'visible' => [
            ':input[name="notifications[' . $type . '][overridden]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['notifications'][$type]['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $notification_settings[$type]->getBody(),
        '#states' => [
          'visible' => [
            ':input[name="notifications[' . $type . '][overridden]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\recurring_events_registration\Entity\RegistrantTypeInterface $registrant_type */
    $registrant_type = $this->entity;

    // Retrieve the submitted notification settings, filtering out the active
    // tab controls.
    $notifications = $form_state->getValue('notifications');
    $notifications = array_filter($notifications, 'is_array');
    $notifications = array_map(fn (array $notification) => new RegistrantTypeNotificationSetting([
      'overridden' => (bool) $notification['overridden'],
      'enabled' => (bool) $notification['enabled'],
      'subject' => (string) $notification['subject'],
      'body' => (string) $notification['body'],
    ]), $notifications);

    $registrant_type->setNotificationSettings($notifications);

    $status = $registrant_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label registrant type.', [
          '%label' => $registrant_type->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label registrant type.', [
          '%label' => $registrant_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($registrant_type->toUrl('collection'));
  }

}
