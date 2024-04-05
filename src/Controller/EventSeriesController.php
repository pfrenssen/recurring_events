<?php

namespace Drupal\recurring_events\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events\Entity\EventSeriesTypeInterface;
use Drupal\recurring_events\EventInterface;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The EventSeriesController class.
 */
class EventSeriesController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * Constructs a EventSeriesController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\system\SystemManager $systemManager
   *   System manager service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, SystemManager $systemManager) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->systemManager = $systemManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('system.manager')
    );
  }

  /**
   * The page callback for the admin overview page.
   */
  public function adminPage() {
    return $this->systemManager->getBlockContents();
  }

  /**
   * The page callback for the admin content page.
   */
  public function contentPage() {
    return $this->systemManager->getBlockContents();
  }

  /**
   * Displays add content links for available event series types.
   *
   * Redirects to events/add/[type] if only one type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   will return a RedirectResponse to the node add page for that one node
   *   type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'eventseries_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('eventseries_type')->getListCacheTags(),
      ],
    ];

    $content = [];

    // Only use eventseries types the user has access to.
    foreach ($this->entityTypeManager()->getStorage('eventseries_type')->loadMultiple() as $type) {
      $access = $this->entityTypeManager()->getAccessControlHandler('eventseries')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('entity.eventseries.add_form', ['eventseries_type' => $type->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Create a new event.
   *
   * @param \Drupal\recurring_events\Entity\EventSeriesTypeInterface $eventseries_type
   *   The eventseries type.
   */
  public function add(EventSeriesTypeInterface $eventseries_type) {
    $eventseries = $this->entityTypeManager()->getStorage('eventseries')->create([
      'type' => $eventseries_type->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($eventseries);

    return $form;
  }

  /**
   * Create a new event instance.
   *
   * @param \Drupal\recurring_events\Entity\EventSeries $eventseries
   *   The eventseries.
   */
  public function addInstance(EventSeries $eventseries) {
    $data = [
      'eventseries_id' => $eventseries->id(),
      'type' => $eventseries->getType(),
    ];
    $this->moduleHandler()->alter('recurring_events_event_instance', $data);

    $entity = $this->entityTypeManager()->getStorage('eventinstance')->create($data);

    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * The _title_callback for the entity.eventseries.add_instance_form route.
   *
   * @param \Drupal\recurring_events\Entity\EventSeries $eventseries
   *   The eventseries.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function addInstanceTitle(EventSeries $eventseries) {
    return $this->t('Add new instance to %name', ['%name' => $eventseries->label()]);
  }

  /**
   * The _title_callback for the entity.eventseries.add_form route.
   *
   * @param \Drupal\recurring_events\Entity\EventSeriesTypeInterface $eventseries_type
   *   The eventseries type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(EventSeriesTypeInterface $eventseries_type) {
    return $this->t('Create %name Event', ['%name' => $eventseries_type->label()]);
  }

  /**
   * The _title_callback for the entity.eventseries.edit_form route.
   *
   * @param \Drupal\recurring_events\EventInterface $eventseries
   *   The eventseries type.
   *
   * @return string
   *   The page title.
   */
  public function editPageTitle(EventInterface $eventseries) {
    return $this->t('Edit %type Event %title', [
      '%type' => $eventseries->bundle(),
      '%title' => $eventseries->label(),
    ]);
  }

  /**
   * The _title_callback for the entity.eventseries.delete_form route.
   *
   * @param \Drupal\recurring_events\EventInterface $eventseries
   *   The eventseries type.
   *
   * @return string
   *   The page title.
   */
  public function deletePageTitle(EventInterface $eventseries) {
    return $this->t('Delete %type Event %title', [
      '%type' => $eventseries->bundle(),
      '%title' => $eventseries->label(),
    ]);
  }

  /**
   * The _title_callback for the entity.eventseries.clone_form route.
   *
   * @param \Drupal\recurring_events\EventInterface $eventseries
   *   The eventseries type.
   *
   * @return string
   *   The page title.
   */
  public function clonePageTitle(EventInterface $eventseries) {
    return $this->t('Clone %type Event %title', [
      '%type' => $eventseries->bundle(),
      '%title' => $eventseries->label(),
    ]);
  }

  /**
   * Displays an eventseries revision.
   *
   * @param int $eventseries_revision
   *   The eventseries revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($eventseries_revision) {
    $eventseries = $this->entityTypeManager()->getStorage('eventseries')->loadRevision($eventseries_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('eventseries');

    return $view_builder->view($eventseries);
  }

  /**
   * Page title callback for an eventseries revision.
   *
   * @param int $eventseries_revision
   *   The eventseries revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($eventseries_revision) {
    $eventseries = $this->entityTypeManager()->getStorage('eventseries')->loadRevision($eventseries_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $eventseries->label(),
      '%date' => $this->dateFormatter->format($eventseries->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of an eventseries.
   *
   * @param \Drupal\recurring_events\EventInterface $eventseries
   *   A eventseries object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(EventInterface $eventseries) {
    $account = $this->currentUser();
    $langcode = $eventseries->language()->getId();
    $langname = $eventseries->language()->getName();
    $languages = $eventseries->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $eventseries_storage = $this->entityTypeManager()->getStorage('eventseries');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $eventseries->label(),
    ]) : $this->t('Revisions for %title', [
      '%title' => $eventseries->label(),
    ]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all eventseries revisions") || $account->hasPermission('administer eventseries entities')));
    $delete_permission = (($account->hasPermission("delete all eventseries revisions") || $account->hasPermission('administer eventseries entities')));

    $rows = [];

    $vids = $eventseries_storage->revisionIds($eventseries);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\recurring_events\EventInterface $revision */
      $revision = $eventseries_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $eventseries->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.eventseries.revision', [
            'eventseries' => $eventseries->id(),
            'eventseries_revision' => $vid,
          ]));
        }
        else {
          $link = $eventseries->toLink($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link->toString(),
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        // @todo Simplify once https://www.drupal.org/node/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.eventseries.revision_revert_translation_confirm', [
                'eventseries' => $eventseries->id(),
                'eventseries_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.eventseries.revision_revert', [
                'eventseries' => $eventseries->id(),
                'eventseries_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.eventseries.revision_delete', [
                'eventseries' => $eventseries->id(),
                'eventseries_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['eventseries_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
