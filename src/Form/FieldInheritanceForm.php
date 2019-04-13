<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FieldInheritanceForm.
 */
class FieldInheritanceForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $field_inheritance = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $field_inheritance->label(),
      '#description' => $this->t("Label for the Field inheritance."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $field_inheritance->id(),
      '#machine_name' => [
        'exists' => '\Drupal\recurring_events\Entity\FieldInheritance::load',
      ],
      '#disabled' => !$field_inheritance->isNew(),
    ];

    $help = [
      $this->t('<b>Inherit</b> - Pull field data directly from the series.'),
      $this->t('<b>Prepend</b> - Place instance data above series data.'),
      $this->t('<b>Append</b> - Place instance data below series data.'),
      $this->t('<b>Fallback</b> - Show instance data, if set, otherwise show series data.'),
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Inheritance Strategy'),
      '#description' => $this->t('Select the method/strategy used to inherit data.'),
      '#options' => [
        'inherit' => $this->t('Inherit'),
        'prepend' => $this->t('Prepend'),
        'append' => $this->t('Append'),
        'fallback' => $this->t('Fallback'),
      ],
      '#required' => TRUE,
      '#default_value' => $field_inheritance->type() ?: 'inherit',
    ];
    $form['information'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#markup' => implode('</p><p>', $help),
      '#suffix' => '</p>',
    ];

    $series_fields = array_keys(\Drupal::service('entity_field.manager')->getFieldDefinitions('eventseries', 'eventseries'));
    $series_fields = array_combine($series_fields, $series_fields);

    $form['sourceField'] = [
      '#type' => 'select',
      '#title' => $this->t('Source/Series Field'),
      '#description' => $this->t('Select the field on the series from which to inherit data.'),
      '#options' => $series_fields,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->sourceField(),
    ];

    $instance_fields = array_keys(\Drupal::service('entity_field.manager')->getFieldDefinitions('eventinstance', 'eventinstance'));
    $instance_fields = array_combine($instance_fields, $instance_fields);

    $form['entityField'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity/Instance Field'),
      '#description' => $this->t('Select the field on the instance to use during inheritance.'),
      '#options' => $instance_fields,
      '#states' => [
        'visible' => [
          'select[name="type"]' => ['!value' => 'inherit'],
        ],
        'required' => [
          'select[name="type"]' => ['!value' => 'inherit'],
        ],
      ],
      '#default_value' => $field_inheritance->entityField(),
    ];

    $plugins = array_keys(\Drupal::service('plugin.manager.field_inheritance')->getDefinitions());
    $plugins = array_combine($plugins, $plugins);

    $form['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Inheritance Plugin'),
      '#description' => $this->t('Select the plugin used to perform the inheritance.'),
      '#options' => $plugins,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->plugin(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $field_inheritance = $this->entity;
    $status = $field_inheritance->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Field inheritance.', [
          '%label' => $field_inheritance->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Field inheritance.', [
          '%label' => $field_inheritance->label(),
        ]));
    }
    $form_state->setRedirectUrl($field_inheritance->toUrl('collection'));
  }

}
