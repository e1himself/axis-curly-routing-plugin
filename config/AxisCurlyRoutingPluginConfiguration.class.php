<?php
/**
 * Date: 14.12.12
 * Time: 2:46
 * Author: Ivan Voskoboynyk
 */
class AxisCurlyRoutingPluginConfiguration extends sfPluginConfiguration
{
  public function configure()
  {
    if (sfConfig::get('sf_curly_routing_register_class_aliases', true))
    {
      $map = array(
        'CurlyRoute' => '\Axis\S1\CurlyRouting\CurlyRoute',
        'CurlyObjectRoute' => '\Axis\S1\CurlyRouting\CurlyObjectRoute'
      );

      foreach ($map as $alias => $fqcn)
      {
        if (!class_exists($alias, false))
        {
          class_alias($fqcn, $alias);
        }
      }
    }

    parent::configure();
  }
}
