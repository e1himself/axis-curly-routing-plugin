<?php
/**
 * Date: 14.12.12
 * Time: 4:48
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting;

interface sfRouteInterface
{
  function bind($context, $parameters);
  function isBound();
  function matchesUrl($url, $context = array());
  function matchesParameters($params, $context = array());
  function generate($params, $context = array(), $absolute = false);
  function getParameters();
  function getPattern();
  function getRegex();
  function getTokens();
  function getOptions();
  function getVariables();
  function getDefaults();
  function getRequirements();
  function compile();
  function getDefaultParameters();
  function setDefaultParameters($parameters);
  function getDefaultOptions();
  function setDefaultOptions($options);
}
