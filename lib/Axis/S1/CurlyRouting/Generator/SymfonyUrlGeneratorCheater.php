<?php
/**
 * Date: 14.12.12
 * Time: 5:46
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Generator;

class SymfonyUrlGeneratorCheater extends \Symfony\Component\Routing\Generator\UrlGenerator
{
  public function __construct()
  {
    // override parent constructor
  }

  public function doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $absolute)
  {
    return parent::doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $absolute);
  }
}
