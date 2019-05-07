<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Registrant entities.
 *
 * @ingroup recurring_events_registration
 */
class RegistrantDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Cancel Your Registration');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /* @var $entity \Drupal\recurring_events_registration\Entity\Registrant */
    $entity = $this->entity;

    $build['cancel'] = [
      '#type' => 'container',
      '#weight' => -99,
      'title' => [
        '#type' => 'markup',
        '#prefix' => '<h2 class="registration-register-title">',
        '#markup' => $this->t('Cancel Event Registration'),
        '#suffix' => '</h2>',
      ],
      'intro' => [
        '#type' => 'markup',
        '#prefix' => '<p class=registration-register-intro">',
        '#markup' => $this->t('You are cancelling your registration for %email for %event. Once you do this, there may no longer be any spaces left for this event and you may not be able to register again.', [
          '%email' => $entity->email->value,
          '%event' => $entity->getEventSeries()->title->value,
        ]),
        '#suffix' => '</p>',
      ],
    ];

    return \Drupal::service('renderer')->render($build);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the eventinstance list.
   */
  public function getCancelUrl() {
    return new Url('entity.eventinstance.canonical', ['eventinstance' => $this->getEntity()->getEventInstance()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Go Back - Keep Registration');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm Cancellation');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\recurring_events_registration\Entity\Registrant $entity */
    $entity = $this->entity;
    $entity->delete();
    $eventinstance = $entity->getEventInstance();

    $form_state->setRedirectUrl($eventinstance->toUrl('canonical'));

    $service = \Drupal::service('recurring_events_registration.creation_service');
    $service->setEvents($eventinstance);
    if ($service->hasWaitlist() && $entity->waitlist->value == '0') {
      $service->promoteFromWaitlist();
    }

    drupal_set_message($this->getDeletionMessage());
    $this->logDeletionMessage();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\omega_events\EventInterface $entity */
    $entity = $this->getEntity();

    return $this->t('Your registration for %email for %event has been cancelled.', [
      '%email' => $entity->email->value,
      '%event' => $entity->getEventSeries()->name->value,
    ]);
  }

}
