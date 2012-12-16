<?php
/**
 * Date: 14.12.12
 * Time: 4:55
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Matcher;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class CurlyRouteUrlMatcher implements RouteUrlMatcherInterface
{
  /**
   * @param \Axis\S1\CurlyRouting\CurlyRouteInterface $route
   * @param string $pathinfo
   * @param RequestContext $context
   * @return bool
   */
  public function matches($route, $pathinfo, $context)
  {
    $compiledRoute = $route->compile();

    // check the static prefix of the URL first. Only use the more expensive preg_match when it matches
    if ('' !== $compiledRoute->getStaticPrefix() && 0 !== strpos($pathinfo, $compiledRoute->getStaticPrefix())) {
      return false;
    }

    if (!preg_match($compiledRoute->getRegex(), $pathinfo, $matches)) {
      return false;
    }

    // check HTTP method requirement
    if ($req = $route->getRequirement('sf_method')) {
      // HEAD and GET are equivalent as per RFC
      if ('HEAD' === $method = $context->getMethod()) {
        $method = 'GET';
      }

      if (!in_array($method, $req = explode('|', strtoupper($req)))) {
        // TODO: Consider about Method Not Allowed Error reporting
        return false;
      }
    }

    $status = $this->handleRouteRequirements($route, $pathinfo, $context);

    if (UrlMatcher::ROUTE_MATCH === $status[0]) {
      return $status[1];
    }

    if (UrlMatcher::REQUIREMENT_MISMATCH === $status[0]) {
      return false;
    }

    $params =  $this->mergeDefaults($matches, array_merge(
      $route->getDefaultParameters(),
      $route->getDefaults()
    ));

    return $this->fixDottedVariables($params, $route->getVariables());
  }

  /**
   * Handles specific route requirements.
   *
   * @param \Axis\S1\CurlyRouting\CurlyRouteInterface $route The route
   * @param string $pathinfo The path
   * @param RequestContext $context
   *
   * @return array The first element represents the status, the second contains additional information
   */
  protected function handleRouteRequirements($route, $pathinfo, $context)
  {
    // check HTTP scheme requirement
    $scheme = $route->getRequirement('sf_scheme');
    $status = $scheme && $scheme !== $context->getScheme() ? UrlMatcher::REQUIREMENT_MISMATCH : UrlMatcher::REQUIREMENT_MATCH;

    return array($status, null);
  }

  /**
   * Get merged default parameters.
   *
   * @param array $params   The parameters
   * @param array $defaults The defaults
   *
   * @return array Merged default parameters
   */
  protected function mergeDefaults($params, $defaults)
  {
    foreach ($params as $key => $value) {
      if (!is_int($key)) {
        $defaults[$key] = $value;
      }
    }

    return $defaults;
  }

  protected function fixDottedVariables($params, $variables)
  {
    $fixed = array();
    foreach ($variables as $var)
    {
      $var_fixed = str_replace('.', '_', $var);
      if (array_key_exists($var_fixed, $params))
      {
        $fixed[$var] = $params[$var_fixed];
        unset($params[$var_fixed]);
      }
    }
    return array_merge($fixed, $params);
  }
}