<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Messenger\Messenger;

/**
 * Form controller for the eventinstance entity edit forms.
 *
 * @ingroup recurring_events
 */
class EventInstanceForm extends ContentEntityForm {

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('messenger')
    );
  }

  /**
   * Construct a EventSeriesEditForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(EntityManagerInterface $entity_manager, Messenger $messenger) {
    $this->messenger = $messenger;
    parent::__construct($entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.eventinstance.collection');
    parent::save($form, $form_state);

    $entity = $this->getEntity();

    if ($entity->isDefaultTranslation()) {
      $message = t('Event instance of %label has been saved.', [
        '%label' => $entity->getEventSeries()->title->value,
      ]);
    }
    else {
      $message = t('@language translation of the Event Instance %label has been saved.', [
        '@language' => $entity->language()->getName(),
        '%label' => $entity->getUntranslated()->getEventSeries()->title->value,
      ]);
    }
    $this->messenger->addMessage($message);
  }

}
