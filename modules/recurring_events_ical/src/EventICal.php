<?php

namespace Drupal\recurring_events_ical;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events\EventInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a service to get the iCalendar data for an event.
 */
class EventICal implements EventICalInterface {

  const VERSION = '2.0';
  const PRODID = '-//Drupal//recurring_events_ical//2.0//EN';
  const DATETIMEFORMAT = 'Ymd\THis\Z';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs an EventICalService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Utility\Token $token
   *   The tokens service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack, Token $token) {
    $this->entityTypeManager = $entityTypeManager;
    $this->request = $requestStack->getCurrentRequest();
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function render(EventInterface $event): string {
    /** @var \Drupal\recurring_events_ical\Entity\EventICalMapping|null $mapping */
    $mapping = $this->entityTypeManager->getStorage('event_ical_mapping')->load($event->bundle());

    /** @var \Drupal\recurring_events\Entity\EventInstance[] $instances */
    $instances = $event instanceof EventSeries
      ? $event->get('event_instances')->referencedEntities()
      : [$event];

    $output = [];
    $output[] = 'BEGIN:VCALENDAR';
    $output[] = 'VERSION:' . static::VERSION;
    $output[] = 'PRODID:' . static::PRODID;

    foreach ($instances as $instance) {
      $output[] = 'BEGIN:VEVENT';
      $output[] = 'UID:' . $instance->uuid() . '@' . $this->request->getHost();
      $output[] = 'DTSTAMP:' . date(static::DATETIMEFORMAT, $instance->getChangedTime());
      $output[] = 'DTSTART:' . $instance->date->start_date->format(static::DATETIMEFORMAT);
      $output[] = 'DTEND:' . $instance->date->end_date->format(static::DATETIMEFORMAT);
      if ($mapping) {
        foreach ($mapping->getAllProperties() as $property => $value) {
          $value = $this->prepareValue($property, $value, $instance);
          if (!empty($value)) {
            $output[] = $value;
          }
        }
      }
      else {
        // The summary is required, so if there's no mapping in place, default
        // to the event label.
        $output[] = 'SUMMARY:' . $instance->label();
      }
      $output[] = 'END:VEVENT';
    }

    $output[] = 'END:VCALENDAR';
    return implode("\n", $output);
  }

  /**
   * Prepares a property value on an event for output as iCalendar data.
   *
   * @param string $property
   *   The property name.
   * @param string $value
   *   The raw value.
   * @param \Drupal\recurring_events\Entity\EventInstance $instance
   *   The event instance.
   *
   * @return string
   *   The sanitized value.
   */
  protected function prepareValue(string $property, string $value, EventInstance $instance): string {
    // Process any tokens, removing any that have no replacement.
    $value = $this->token->replace($value, ['eventinstance' => $instance], [
      'clear' => TRUE,
    ]);

    // Sanitize twice to ensure that all tags and HTML codes are removed. For
    // example, if the value came in as "&lt;p&gt;Hello,&#039;World!&lt;/p&gt;",
    // the first pass would return "<p>Hello,&nbsp;World!</p>" and the second
    // pass would return "Hello, World!", which is what we need.
    $value = trim(PlainTextOutput::renderFromHtml(PlainTextOutput::renderFromHtml($value)));

    // If there's nothing left after processing, return an empty string so that
    // the property is omitted.
    if (empty($value)) {
      return '';
    }

    // RFC 5545 3.1 requires lines longer than 75 characters (including the
    // property name and line break characters) to be wrapped with CRLF followed
    // by a single space, which will be ignored on import. We include an
    // additional space so that the words before and after the break don't run
    // together when recomposed.
    return wordwrap(strtoupper($property) . ':' . $value, 71, "\r\n  ", TRUE);
  }

}
