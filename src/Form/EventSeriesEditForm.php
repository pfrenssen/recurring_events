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
      $save_button_label = $this->t('Next');
    }
    else {
      /* @var $entity \Drupal\recurring_events\Entity\EventSeries */
      $entity = $this->entity;

      // Determine if there have been changes to the saved eventseries.
      $creation_service = \Drupal::service('recurring_events.event_creation_service');
      $diff_array = $creation_service->buildDiffArray($entity, $form_state);

      $create_form = parent::buildForm($form, $form_state);

      $form['actions'] = $create_form['actions'];
      unset($create_form);

      $title = $this->t('Save Changes?');
      $message = $this->t('No recurrence configuration has been changed so no changes will be made to event instances.');
      $save_button_label = $this->t('Save');

      if (!empty($diff_array)) {
        $title = $this->t('Confirm Modifications?');
        $message = $this->t('Recurrence configuration has been changed, as a result all instances will be removed and recreated. This action cannot be undone.');
        $save_button_label = $this->t('Save and Recreate Event Instances');
      }

      $form['title'] = [
        '#type' => '#markup',
        '#prefix' => '<h2>',
        '#markup' => $title,
        '#suffix' => '</h2>',
        '#weight' => -10,
      ];
      $form['message'] = [
        '#type' => '#markup',
        '#prefix' => '<p>',
        '#markup' => $message,
        '#suffix' => '</p>',
        '#weight' => -9,
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
          '#weight' => -8,
        ];
      }

      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
      ];

      $form['actions']['delete']['#printed'] = TRUE;
    }

    $form['actions']['submit']['#value'] = $save_button_label;

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
      $triggering_element = $form_state->getTriggeringElement();

      switch ($triggering_element['#id']) {
        case 'edit-back':
          $this->step--;
          $form_state->setRebuild();
          break;

        case 'edit-submit':
          parent::submitForm($form, $form_state);
          break;
      }
    }
  }

}
