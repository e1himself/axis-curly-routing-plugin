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
   * @return bool|array
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

    $hostMatches = array();
    if ($compiledRoute->getHostRegex() && !preg_match($compiledRoute->getHostRegex(), $context->getHost(), $hostMatches)) {
      return false;
    }

    // check HTTP method requirement
    if ($req = $route->getRequirement('_method')) {
      // HEAD and GET are equivalent as per RFC
      if ('HEAD' === $method = $context->getMethod()) {
        $method = 'GET';
      }

      if (!in_array($method, $req = explode('|', strtoupper($req)))) {
        // $this->allow = array_merge($this->allow, $req);
        // TODO: Consider about Method Not Allowed Error reporting
        return false;
      }
    }

    $status = $this->handleRouteRequirements($pathinfo, $context, $route);

    if (UrlMatcher::ROUTE_MATCH === $status[0]) {
      return $status[1];
    }

    if (UrlMatcher::REQUIREMENT_MISMATCH === $status[0]) {
      return false;
    }

    return $this->getAttributes($route, array_replace($matches, $hostMatches));
  }

  /**
   * Returns an array of values to use as request attributes.
   *
   * As this method requires the Route object, it is not available
   * in matchers that do not have access to the matched Route instance
   * (like the PHP and Apache matcher dumpers).
   *
   * @param \Axis\S1\CurlyRouting\CurlyRouteInterface  $route      The route we are matching against
   * @param array  $attributes An array of attributes from the matcher
   *
   * @return array An array of parameters
   */
  protected function getAttributes($route, $attributes)
  {
    $params =  $this->mergeDefaults($attributes, array_merge(
      $route->getDefaultParameters(),
      $route->getDefaults()
    ));

    return $this->fixDottedVariables($params, $route->getVariables());
  }

  /**
   * Handles specific route requirements.
   *
   * @param string $pathinfo The path
   * @param \Axis\S1\CurlyRouting\RequestContext $context
   * @param \Axis\S1\CurlyRouting\CurlyRouteInterface  $route    The route
   *
   * @return array The first element represents the status, the second contains additional information
   */
  protected function handleRouteRequirements($pathinfo, $context, $route)
  {
    // check HTTP scheme requirement
    $scheme = $route->getRequirement('_scheme');
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
    foreach ($variables as $var => $pattern)
    {
      $var_fixed = str_replace('.', '__', $var);
      if (array_key_exists($var_fixed, $params))
      {
        $fixed[$var] = $params[$var_fixed];
        unset($params[$var_fixed]);
      }
    }
    return array_merge($fixed, $params);
  }
}