<?php

declare(strict_types=1);

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Event series type entities.
 */
class EventSeriesTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $descriptions = [];
    $instance_type = \Drupal::entityTypeManager()->getStorage('eventinstance_type')->load($this->entity->id());

    if (!empty($instance_type)) {
      $descriptions[] = $this->t('Deleting this event series type will also delete the corresponding event instance type.');
    }

    if (\Drupal::moduleHandler()->moduleExists('recurring_events_registration')) {
      $registrant_types = \Drupal::entityTypeManager()->getStorage('registrant_type')->load($this->entity->id());
      if (!empty($registrant_types)) {
        $descriptions[] = $this->t('Deleting this event series type will also delete the corresponding registrant type.');
      }
    }

    return implode("\r\n", $descriptions);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.eventseries_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addMessage(
      $this->t('Successfully deleted @type: @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
      )
    );

    \Drupal::cache('menu')->invalidateAll();
    \Drupal::service('plugin.manager.menu.link')->rebuild();

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
