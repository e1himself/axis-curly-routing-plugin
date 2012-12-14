<?php
/**
 * Date: 14.12.12
 * Time: 3:35
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Generator;

use \Symfony\Component\Routing\RequestContextAwareInterface;
use Axis\S1\CurlyRouting\CurlyRouteInterface;

interface RouteUrlGeneratorInterface
{
  /**
   * Generates an URL for given parameters
   *
   * @param CurlyRouteInterface $route
   * @param array $parameters an array of request parameters
   * @param bool $absolute flag to generate absolute URL
   * @param $context
   * @return string generated url
   */
  public function generate($route, $parameters, $absolute, $context);
}
