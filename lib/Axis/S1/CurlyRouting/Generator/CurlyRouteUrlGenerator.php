<?php
/**
 * Date: 14.12.12
 * Time: 3:37
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Generator;

class CurlyRouteUrlGenerator implements RouteUrlGeneratorInterface
{
  /**
   * @var SymfonyUrlGeneratorCheater
   */
  protected $generator;

  public function __construct()
  {
    // empty constructor
  }

  /**
   * {@inheritdoc}
   */
  public function generate($route, $parameters, $absolute, $context)
  {
    $generator = $this->getSymfonyGenerator();
    $generator->setContext($context);

    $defaults = array_merge($route->getDefaultParameters(), $route->getDefaults());
    return $generator->doGenerate(
      $route->getVariables(),
      $defaults,
      $route->getRequirements(),
      $route->compile()->getTokens(),
      $parameters,
      $route->getPattern(),
      $absolute
    );
  }

  /**
   * @return SymfonyUrlGeneratorCheater
   */
  protected function getSymfonyGenerator()
  {
    if (!$this->generator)
    {
      $this->generator = new SymfonyUrlGeneratorCheater();
    }
    return $this->generator;
  }
}
