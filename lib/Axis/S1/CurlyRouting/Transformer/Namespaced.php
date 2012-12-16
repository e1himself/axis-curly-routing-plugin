<?php
/**
 * Date: 16.12.12
 * Time: 1:52
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Transformer;

class Namespaced implements DataTransformerInterface
{
  /**
   * @param array $params
   * @param array $variables
   * @param array $options
   * @return array Converted parameters
   */
  public function transformForUrl($params, $variables, $options = array())
  {
    return is_array($params) ? $this->flattenParameters($params) : $params;
  }

  /**
   * @param array $params
   * @param array $variables
   * @param array $options
   * @return array Converted parameters
   */
  public function transformForController($params, $variables, $options = array())
  {
    return is_array($params) ? $this->explodeParameters($params) : $params;
  }

  /**
   * Converts a nested array structure of parameters to plain dot notated list.
   *
   * Example:
   *   { object: { id: 1, name: 'My Object' } }
   * will be converted to
   *   { object.id: 1, object.name: 'My Object' }
   *
   * @param array $params Array of params
   * @param string $prefix
   * @return array
   */
  protected function flattenParameters($params, $prefix = '')
  {
    $flat = array();
    foreach ($params as $name => $value)
    {
      if (is_array($value))
      {
        $flat = array_merge($flat, self::flattenParameters($value, $prefix.$name.'.'));
      }
      else
      {
        $flat[$prefix.$name] = $value;
      }
    }
    return $flat;
  }

  /**
   * Performs a reverse conversion to flattenParameters() method.
   * @see flattenParameters
   *
   * @param array $params
   * @return array
   */
  protected function explodeParameters($params)
  {
    $exploded = array();
    foreach ($params as $name => $value)
    {
      if (FALSE !== strpos($name, '.'))
      {
        $current = & $exploded;
        foreach (explode('.', $name) as $node)
        {
          if (!isset($current[$node]))
          {
            $current[$node] = null;
          }
          $current = & $current[$node];
        }
        $current = $value;
      }
      else
      {
        $exploded[$name] = $value;
      }
    }
    return $exploded;
  }
}
