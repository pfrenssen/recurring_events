<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\recurring_events\EventCreationService;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Form controller for the eventseries entity create form.
 *
 * @ingroup recurring_events
 */
class EventSeriesForm extends ContentEntityForm {

  /**
   * The event creation service.
   *
   * @var Drupal\recurring_events\EventCreationService
   */
  protected $creationService;

  /**
   * The entity storage interface.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('recurring_events.event_creation_service'),
      $container->get('entity_type.manager')->getStorage('eventseries'),
      $container->get('entity.manager')
    );
  }

  /**
   * Construct a EventSeriesEditForm.
   *
   * @param \Drupal\recurring_events\EventCreationService $creation_service
   *   The event creation service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage interface.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EventCreationService $creation_service, EntityStorageInterface $storage, EntityManagerInterface $entity_manager) {
    $this->creationService = $creation_service;
    $this->storage = $storage;
    parent::__construct($entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $editing = ($form_state->getBuildInfo()['form_id'] == 'eventseries_edit_form');

    /* @var $entity \Drupal\recurring_events\Entity\EventSeries */
    $entity = $this->entity;

    $form['custom_date']['#states'] = [
      'visible' => [
        ':input[name="recur_type"]' => ['value' => 'custom'],
      ],
    ];

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

    if ($editing) {
      $form['actions']['verify'] = [
        '#type' => 'submit',
        '#value' => $this->t('Verify Changes'),
        '#ajax' => [
          'wrapper' => 'eventseries-edit-form',
          'callback' => [$this, 'ajaxVerifyChanges'],
          'method' => 'replace',
          'effect' => 'fade',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Add validation.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxVerifyChanges(array &$form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\recurring_events\Entity\EventSeries */
    $entity = $this->entity;
    $original = $this->storage->loadUnchanged($entity->id());

    // Determine if there have been changes to the saved eventseries.
    $diff_array = $this->creationService->buildDiffArray($original, $form_state);

    if (!empty($diff_array)) {
      $build = [];
      $build['message'] = [
        '#type' => '#markup',
        '#prefix' => '<p>',
        '#markup' => $this->t('Recurrence configuration has been changed, as a result all instances will be removed and recreated. This action cannot be undone.'),
        '#suffix' => '</p>',
        '#weight' => -9,
      ];

      $build['diff'] = [
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
    else {
      $build = [];
      $build['message'] = [
        '#type' => '#markup',
        '#prefix' => '<p>',
        '#markup' => $this->t('No recurrence configuration has changed. No existing instances will be affected by this update.'),
        '#suffix' => '</p>',
        '#weight' => -9,
      ];
    }

    $response = new AjaxResponse();
    $title = $this->t('Check Recurrence Modifications');
    $response->addCommand(new OpenModalDialogCommand($title, $build, ['width' => '700']));
    return $response;
  }
}
