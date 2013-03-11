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
   * @var SymfonyUrlGeneratorProxy
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
  public function generate($route, $parameters, $referenceType, $context)
  {
    $generator = $this->getSymfonyGenerator();

    $this->context->fromRequestContext($context);
    // force empty base url because of sfRouting::fixGeneratedUrl
    $this->context->setBaseUrl('');

    $generator->setContext($this->context);

    $url = $generator->generate($route, $parameters, $referenceType);
    // force using protocol because of sfRouting::fixGeneratedUrl
    if (substr($url,0,2) == '//')
    {
      $url = $this->context->getScheme() . ':' . $url;
    }
    return $url;
  }

  /**
   * @return SymfonyUrlGeneratorProxy
   */
  protected function getSymfonyGenerator()
  {
    if (!$this->generator)
    {
      $this->generator = new SymfonyUrlGeneratorProxy();
    }
    return $this->generator;
  }
}
