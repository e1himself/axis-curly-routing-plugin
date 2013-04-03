<?php
/**
 * Date: 14.12.12
 * Time: 4:29
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Matcher;

interface RouteUrlMatcherInterface
{
  /**
   *
   * @param \Axis\S1\CurlyRouting\CurlyRouteInterface $route
   * @param string $pathinfo
   * @param \Symfony\Component\Routing\RequestContext $context
   * @return bool|array
   */
  function matches($route, $pathinfo, $context);
}
