<?php
/**
 * Date: 15.12.12
 * Time: 19:19
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Transformer;

interface DataTransformerInterface
{
  /**
   * @param array $params
   * @param array $variables
   * @param array $options
   * @return array Converted parameters
   */
  public function transformForUrl($params, $variables, $options = array());

  /**
   * @param array $params
   * @param array $variables
   * @param array $options
   * @return array Converted parameters
   */
  public function transformForController($params, $variables, $options = array());
}
