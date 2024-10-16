<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for editing event instance types.
 */
class EventInstanceTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $eventinstance_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 32,
      '#default_value' => $eventinstance_type->label(),
      '#description' => $this->t("Label for the Event instance type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $eventinstance_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\recurring_events\Entity\EventInstanceType::load',
      ],
      '#disabled' => !$eventinstance_type->isNew(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $eventinstance_type->getDescription(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $eventinstance_type = $this->entity;
    $status = $eventinstance_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label event instance type.', [
          '%label' => $eventinstance_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label event instance type.', [
          '%label' => $eventinstance_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($eventinstance_type->toUrl('collection'));
  }

}
