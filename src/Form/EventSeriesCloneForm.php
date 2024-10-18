<?php

declare(strict_types=1);

namespace Drupal\recurring_events\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the eventseries entity clone form.
 *
 * @ingroup recurring_events
 */
class EventSeriesCloneForm extends EventSeriesForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entity = $this->entity->createDuplicate();
    return parent::buildForm($form, $form_state);
  }

}
