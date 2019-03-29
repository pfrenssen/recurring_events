<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Registrant edit forms.
 *
 * @ingroup recurring_events_registration
 */
class RegistrantForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\recurring_events_registration\Entity\Registrant */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Registrant.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Registrant.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.registrant.canonical', ['registrant' => $entity->id()]);
  }

}
