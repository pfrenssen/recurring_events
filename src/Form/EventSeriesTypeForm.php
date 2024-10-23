<?php

declare(strict_types=1);

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for creating event series types.
 */
class EventSeriesTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $eventseries_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 32,
      '#default_value' => $eventseries_type->label(),
      '#description' => $this->t("Label for the Event series type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $eventseries_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\recurring_events\Entity\EventSeriesType::load',
      ],
      '#disabled' => !$eventseries_type->isNew(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $eventseries_type->getDescription(),
      '#description' => $this->t('This text will be displayed on the <em>Add event</em> page.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $eventseries_type = $this->entity;
    $status = $eventseries_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label event series type.', [
          '%label' => $eventseries_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label event series type.', [
          '%label' => $eventseries_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($eventseries_type->toUrl('collection'));
  }

}
