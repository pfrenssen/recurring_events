<?php

namespace Drupal\recurring_events\Plugin\FieldInheritance;

use Drupal\recurring_events\FieldInheritancePluginInterface;

/**
 * Taxonomy Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "taxonomy_inheritance",
 *   name = @Translation("Taxonomy Field Inheritance"),
 *   types = {
 *     "field_ui:entity_reference:taxonomy_term"
 *   }
 * )
 */
class TaxonomyFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
