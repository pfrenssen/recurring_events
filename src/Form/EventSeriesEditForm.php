<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the eventseries entity edit form.
 *
 * @ingroup recurring_events
 */
class EventSeriesEditForm extends EventSeriesForm {

  protected $step = 0;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->step === 0) {
      $form = parent::buildForm($form, $form_state);
    }
    else {

      $create_form = parent::buildForm($form, $form_state);

      $form['title'] = [
        '#type' => '#markup',
        '#prefix' => '<h2>',
        '#markup' => $this->t('Confirm Modifications?'),
        '#suffix' => '</h2>',
      ];
      $form['message'] = [
        '#type' => '#markup',
        '#prefix' => '<p>',
        '#markup' => $this->t('As a result of submitting these changes, all event instances related to this event series will be removed and recreated. Tnis action cannot be undone.'),
        '#suffix' => '</p>',
      ];

      $form['actions'] = $create_form['actions'];
      unset($create_form);
    }

    if ($this->step === 0) {
      $button_label = $this->t('Next');
    }
    else {
      $button_label = $this->t('Save');
    }

    $form['actions']['submit']['#value'] = $button_label;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->step === 0) {
      $this->step++;
      $form_state->setRebuild();
    }
    else {
      parent::submitForm($form, $form_state);
    }
  }

}
