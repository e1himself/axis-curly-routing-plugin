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
//    $loader = new \Symfony\Component\ClassLoader\DebugUniversalClassLoader();
//    $loader->registerNamespace('Axis\\S1\\CurlyRouting', __DIR__.'/../lib');
//    $loader->register();

    if (sfConfig::get('sf_curly_routing_register_class_aliases', true))
    {
      class_alias('\Axis\S1\CurlyRouting\CurlyRoute', 'CurlyRoute');
      class_alias('\Axis\S1\CurlyRouting\CurlyObjectRoute', 'CurlyObjectRoute');
    }

    parent::configure();
  }
}
