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

  /**
   * @var \Axis\S1\CurlyRouting\RequestContext
   */
  protected $context;

  public function __construct()
  {
    $this->context = new \Axis\S1\CurlyRouting\RequestContext();
  }

  /**
   * {@inheritdoc}
   */
  public function generate($route, $parameters, $absolute, $context)
  {
    $generator = $this->getSymfonyGenerator();

    $this->context->fromRequestContext($context);
    $this->context->setBaseUrl('');

    $generator->setContext($this->context);

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
