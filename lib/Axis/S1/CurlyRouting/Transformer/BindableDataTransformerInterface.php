<?php
/**
 * Date: 16.12.12
 * Time: 4:38
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Transformer;

interface BindableDataTransformerInterface extends DataTransformerInterface
{
  public function bind($params, $variables, $options = array());
}
