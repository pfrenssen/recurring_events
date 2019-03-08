<?php

namespace Drupal\recurring_events\Form;

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
      $button_label = $this->t('Next');
    }
    else {
      /* @var $entity \Drupal\recurring_events\Entity\EventSeries */
      $entity = $this->entity;

      $creation_service = \Drupal::service('recurring_events.event_creation_service');
      $diff_array = $creation_service->buildDiffArray($entity, $form_state);

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

      if (!empty($diff_array)) {
        $form['diff'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Data'),
            $this->t('Stored'),
            $this->t('Overridden'),
          ],
          '#rows' => $diff_array,
        ];
      }

      $form['actions'] = $create_form['actions'];
      unset($create_form);

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
