<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Form\ConfigFormBase;;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class EventSeriesSettingsForm.
 *
 * @ingroup recurring_events
 */
class EventSeriesSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'eventseries_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['recurring_events.eventseries.config'];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('recurring_events.eventseries.config')
      ->set('interval', $form_state->getValue('interval'))
      ->set('min_time', $form_state->getValue('min_time'))
      ->set('max_time', $form_state->getValue('max_time'))
      ->set('time_format', $form_state->getValue('time_format'))
      ->set('days', implode(',', array_filter($form_state->getValue('days'))))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Define the form used for Events settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('recurring_events.eventseries.config');

    $form['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Event Series Time Intervals'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the interval, in minutes, to be used to separate event start times. Default is 15 minutes.'),
      '#default_value' => $config->get('interval'),
    ];

    $form['min_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Series Minimum Time'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the earliest an event can start, in h:ia format. For example 08:00am.'),
      '#default_value' => $config->get('min_time'),
    ];

    $form['max_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Series Maximum Time'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the latest an event can start, in h:ia format. For example 11:45pm.'),
      '#default_value' => $config->get('max_time'),
    ];

    $url = Url::fromUri('https://secure.php.net/manual/en/function.date.php');
    $link = Link::fromTextAndUrl($this->t('PHP time format'), $url);
    $form['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Series Time Format'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the @link used when selecting times. Default is h:i A.', [
        '@link' => $link->toString(),
      ]),
      '#default_value' => $config->get('time_format'),
    ];

    $days = [
      'monday' => t('Monday'),
      'tuesday' => t('Tuesday'),
      'wednesday' => t('Wednesday'),
      'thursday' => t('Thursday'),
      'friday' => t('Friday'),
      'saturday' => t('Saturday'),
      'sunday' => t('Sunday'),
    ];

    $form['days'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Event Series Days'),
      '#required' => TRUE,
      '#options' => $days,
      '#description' => $this->t('Select the days of the week available when creating events.'),
      '#default_value' => explode(',', $config->get('days')),
    ];

    return parent::buildForm($form, $form_state);
  }

}