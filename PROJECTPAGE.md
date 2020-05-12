[//]: # ( clear&&curl -s -F input_files[]=@PROJECTPAGE.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2 )

_The plug-and-play recurring events and registration system for Drupal._

The Recurring Events module is a Drupal 8 plug-and-play recurring events and
registration system designed to be site agnostic and extensible. Detailed
information about the module is available on the module's [``external documentation``](https://www.drupal.org/docs/8/modules/recurring-events) page .

# Module Basics
The module allows site editors to create and manage events which recur in a
variety of ways - including consecutively, daily, weekly, monthly, and custom
recurrence configurations. An `event series` comprises of multiple `event
instances`, each of which inherits data from the series, but can also be managed
and viewed indepedently from the series itself, this offers great flexibility
when it comes to modifying event instance dates and times, or removing them
altogether.

The module also has two submodules:

* recurring_events_registration enables registration for events
* recurring_events_views which swaps entity lists for flexible views

## Dependencies
The core recurring_events module has 2 core dependencies and 1 contrib
dependency:

* datetime_range (core)
* options (core)
* [field_inheritance](http://www.drupal.org/project/field_inheritance)

## Similar Modules
The closest comparison would be the `date_recur` module, which adds a field type
which allows RRule compliant date recurrence configuration to be added. While
that module does a really great job, this module approaches things differently.
With `date_recur`, a content editor would have a single entity with a recurrence
field that builds instances of that event at display time. With
`recurring_events`, `eventinstances` are separate entities completely, and
therefore can be overridden or extended, without affecting the rest of the
series. This module also comes with a registration submodule, including the
ability to register either for an entire series, or individual events. Using
`date_recur` that would not be possible as there is only one entity.

## Release expectations
This module does not yet have a stable 1.0 release, however we have published a
[roadmap](https://www.drupal.org/project/recurring_events/issues/3131291) for
getting there.
