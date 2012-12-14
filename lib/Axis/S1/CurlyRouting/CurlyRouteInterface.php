<?php
/**
 * Date: 14.12.12
 * Time: 4:38
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting;

interface CurlyRouteInterface extends sfRouteInterface
{
  /**
   * @return \Symfony\Component\Routing\CompiledRoute
   */
  public function compile();

  /**
   * @param string $name
   * @return string
   */
  public function getRequirement($name);
}
