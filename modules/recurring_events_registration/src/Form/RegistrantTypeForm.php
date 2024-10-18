<?php

declare(strict_types=1);

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for editing a registrant type.
 */
class RegistrantTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $registrant_type = $this->entity;
    $status = $registrant_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label registrant type.', [
          '%label' => $registrant_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label registrant type.', [
          '%label' => $registrant_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($registrant_type->toUrl('collection'));
  }

}
