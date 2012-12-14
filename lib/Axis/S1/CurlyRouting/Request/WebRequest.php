<?php
/**
 * Date: 14.12.12
 * Time: 5:51
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Request;

class WebRequest extends \sfWebRequest
{
  public function getRequestContext()
  {
    $context = new \Axis\S1\CurlyRouting\RequestContext();
    $context->fromContextArray(parent::getRequestContext());
    return $context;
  }
}
