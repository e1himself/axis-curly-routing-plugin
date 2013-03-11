<?php
/**
 * Date: 14.12.12
 * Time: 5:46
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Generator;

class SymfonyUrlGeneratorProxy extends \Symfony\Component\Routing\Generator\UrlGenerator
{
  public function __construct()
  {
    // override parent constructor
  }

  /**
   * @param \Axis\S1\CurlyRouting\CurlyRoute $route
   * @param array $parameters
   * @param bool|string $referenceType
   * @return null|string
   */
  public function generate($route, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
  {
    // use route pattern as name (for meaningful exception messages)
    $name = $route->getPattern();
    // the Route has a cache of its own and is not recompiled as long as it does not get modified
    $compiledRoute = $route->compile();
    $defaults = array_merge($route->getDefaultParameters(), $route->getDefaults());

    return $this->doGenerate(array_keys($route->getVariables()), $defaults, $route->getRequirements(), $compiledRoute->getTokens(), $parameters, $name, $referenceType, $compiledRoute->getHostTokens());
  }
}
