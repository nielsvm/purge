<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController::buildQueuersQueueProcessors().
 *
 * @group purge
 */
class DashboardQueuersQueueProcessorsTest extends DashboardTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_queuer_test', 'purge_processor_test'];

  /**
   * Test the queuers section of the dashboard.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildQueuersQueueProcessors
   */
  public function testQueuersSection(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Queuers add items to the queue upon certain events, that processors process later on.');
    $this->assertSession()->responseContains('Queuer A');
    $queuer_a_route_params = ['id' => 'a'];
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queuer_detail_form', $queuer_a_route_params)->toString());
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.queuer_config_dialog_form', $queuer_a_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queuer_delete_form', $queuer_a_route_params)->toString());
    $this->assertSession()->responseContains('Queuer B');
    $queuer_b_route_params = ['id' => 'b'];
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queuer_detail_form', $queuer_b_route_params)->toString());
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.queuer_config_dialog_form', $queuer_b_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queuer_delete_form', $queuer_b_route_params)->toString());
    $this->assertSession()->responseNotContains('Queuer C');
    $queuer_c_route_params = ['id' => 'c'];
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.queuer_detail_form', $queuer_c_route_params)->toString());
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.queuer_config_dialog_form', $queuer_c_route_params)->toString());
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.queuer_delete_form', $queuer_c_route_params)->toString());
    $this->initializeQueuersService(['withform']);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Queuer with form');
    $queuer_withform_route_params = ['id' => 'withform'];
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queuer_detail_form', $queuer_withform_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queuer_config_dialog_form', $queuer_withform_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queuer_delete_form', $queuer_withform_route_params)->toString());

    $this->assertSession()->responseContains('Add queuer');
  }

  /**
   * Test the queue section of the dashboard.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildQueuersQueueProcessors
   */
  public function testQueueSection(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains("Memory");
    $this->assertSession()->pageTextContains("Inspect");
    $this->assertSession()->pageTextContains("Change engine");
    $this->assertSession()->pageTextContains("Empty");
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queue_detail_form')->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queue_browser_form')->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queue_change_form')->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.queue_empty_form')->toString());
  }

  /**
   * Test the processors section of the dashboard.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildQueuersQueueProcessors
   */
  public function testProcessorsSection(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Processors are responsible for emptying the queue and putting the purgers to work each time they process. Processors can work the queue constantly or at timed intervals, it is up to you to configure a policy that makes sense for the traffic nature of your website. Multiple processors will not lead to any parallel-processing or conflicts, instead it simply means the queue is checked more often.');
    $this->assertSession()->responseContains('Processor A');
    $processor_a_route_params = ['id' => 'a'];
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.processor_detail_form', $processor_a_route_params)->toString());
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.processor_config_dialog_form', $processor_a_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.processor_delete_form', $processor_a_route_params)->toString());
    $this->assertSession()->responseContains('Processor B');
    $processor_b_route_params = ['id' => 'b'];
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.processor_detail_form', $processor_b_route_params)->toString());
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.processor_config_dialog_form', $processor_b_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.processor_delete_form', $processor_b_route_params)->toString());
    $this->assertSession()->responseNotContains('Processor C');
    $processor_c_route_params = ['id' => 'c'];
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.processor_detail_form', $processor_c_route_params)->toString());
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.processor_config_dialog_form', $processor_c_route_params)->toString());
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.processor_delete_form', $processor_c_route_params)->toString());
    $this->initializeProcessorsService(['withform']);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Processor with form');
    $processor_withform_route_params = ['id' => 'withform'];
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.processor_detail_form', $processor_withform_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.processor_config_dialog_form', $processor_withform_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.processor_delete_form', $processor_withform_route_params)->toString());

    $this->assertSession()->responseContains('Add processor');
  }

}
