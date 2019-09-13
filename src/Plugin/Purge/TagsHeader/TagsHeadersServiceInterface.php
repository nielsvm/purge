<?php

namespace Drupal\purge\Plugin\Purge\TagsHeader;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\purge\ServiceInterface;

/**
 * Describes a service that provides access to available tags headers.
 */
interface TagsHeadersServiceInterface extends ServiceInterface, ContainerAwareInterface, \Countable, \Iterator {}
