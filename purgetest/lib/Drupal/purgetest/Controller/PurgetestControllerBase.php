<?php

/**
 * @file
 * Contains \Drupal\purgetest\Controller\PurgetestControllerBase.
 */

namespace Drupal\purgetest\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\purge\Queue\QueueServiceInterface;
use Drupal\purge\Purgeable\PurgeablesServiceInterface;

/**
 * Contains callbacks with simple API tests.
 */
class PurgetestControllerBase extends ControllerBase {

  /**
   * Constructs a controller.
   *
   * @param \Drupal\purge\Queue\QueueServiceInterface $purge_queue;
   *   Purge Queue Service.
   * @param Drupal\purge\Purgeable\PurgeablesServiceInterface $purge_purgeables;
   *   Purge Purgeables Service.
   */
  public function __construct(QueueServiceInterface $purge_queue, PurgeablesServiceInterface $purge_purgeables) {
    $this->purgeQueue = $purge_queue;
    $this->purgePurgeables = $purge_purgeables;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('purge.queue'),
      $container->get('purge.purgeables')
    );
  }

  /**
   * Theme API examples.
   *
   * @param $method_name
   *   The name of the function
   */
  protected function reflectionResponse($data = NULL) {
    $render = array();

    // Retrieve the doc comment from our caller.
    $db = debug_backtrace();
    $rc = new \ReflectionMethod($db[1]['class'], $db[1]['function']);
    $doc = $rc->getDocComment();
    $doc = str_replace('/**', '', $doc);
    $doc = str_replace('   *', '', $doc);
    $doc = str_replace('   * ', '', $doc);
    $doc = str_replace('   */', '', $doc);
    $doc = str_replace("\n/", '', $doc);
    $render['doc'] = array(
      '#markup' => "<pre>$doc</pre>",
    );

    // Add the given return data.
    $render['response'] = array(
      '#markup' => "<p><pre>". var_export($data, TRUE) ."</pre></p>",
    );

    return $render;
  }


  public function home() {
    return __METHOD__;
  }
}