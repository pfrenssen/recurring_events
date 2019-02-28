<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the eventseries entity edit forms.
 *
 * @ingroup recurring_events
 */
class EventSeriesForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /* @var $entity \Drupal\recurring_events\Entity\EventSeries */
    $entity = $this->entity;

    $form['advanced']['#attributes']['class'][] = 'entity-meta';

    $form['meta'] = [
      '#type' => 'details',
      '#group' => 'advanced',
      '#weight' => -10,
      '#title' => $this->t('Status'),
      '#attributes' => ['class' => ['entity-meta__header']],
      '#tree' => TRUE,
      '#access' => \Drupal::currentUser()->hasPermission('administer eventseries'),
    ];
    $form['meta']['published'] = [
      '#type' => 'item',
      '#markup' => $entity->isPublished() ? $this->t('Published') : $this->t('Not published'),
      '#access' => !$entity->isNew(),
      '#wrapper_attributes' => ['class' => ['entity-meta__title']],
    ];
    $form['meta']['changed'] = [
      '#type' => 'item',
      '#title' => $this->t('Last saved'),
      '#markup' => !$entity->isNew() ? format_date($entity->getChangedTime(), 'short') : $this->t('Not saved yet'),
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved']],
    ];
    $form['meta']['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Author'),
      '#markup' => $entity->getOwner()->getUsername(),
      '#wrapper_attributes' => ['class' => ['entity-meta__author']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.eventseries.collection');

    $entity = $this->getEntity();
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Add validation.
    parent::validateForm($form, $form_state);
  }

}
