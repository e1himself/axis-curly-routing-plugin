<?php
/**
 * Date: 15.12.12
 * Time: 19:21
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting\Transformer;

class PropelObject extends Namespaced implements BindableDataTransformerInterface
{
  protected function fixOptions($options)
  {
    $options = array_merge($options, array(
      // variable namespace
      'namespace' => array_key_exists('namespace', $options) ? $options['namespace'] : @$options['_name'],
      // object method to be called to retrieve values for variables
      'convert' => @$options['convert'],
      // a list of query methods to be called when retrieving object
      'query_methods' => (array)@$options['query_methods']
    ));

    if (!isset($options['model']))
    {
      throw new \LogicException(sprintf(
        'You should specify "model" option for PropelObject data converter (namespace:%s)',
        $options['namespace']
      ));
    }

    return $options;
  }

  /**
   * @param array|\BaseObject $params
   * @param array $variables
   * @param array $options
   * @return array Converted parameters
   */
  public function transformForUrl($params, $variables, $options = array())
  {
    $options = $this->fixOptions($options);
    // check if namespace is specified
    if (!empty($options['namespace']))
    {
      $namespace = $options['namespace'];
      // fetch needed parameters
      $object = isset($params[$namespace]) ? $params[$namespace] : array();

      // quickly return unchanged $params array if there is no our parameters
      if (empty($object))
      {
        return $params;
      }
      // filter only needed variables
      $variables = $this->filterNamespacedVariables($variables, $namespace);
      // fill parameters like there is no namespaces
      $additional = $this->convertObjectToArray($object, $variables, $options);

      // merge result back into $params array
      if (isset($params[$namespace]) && is_array($params[$namespace]))
      {
        $params[$namespace] = array_merge($params[$namespace], $additional);
      }
      else
      {
        $params[$namespace] = $additional;
      }
      // return
      return $this->flattenParameters($params);
    }
    else
    {
      return $this->convertObjectToArray($params, $variables, $options);
    }
  }

  /**
   * Explodes route parameters
   * @param array $params
   * @param array $variables
   * @param array $options
   * @return array
   */
  public function transformForController($params, $variables, $options = array())
  {
    $options = $this->fixOptions($options);
    if (!empty($options['namespace']))
    {
      $namespace = $options['namespace'];
      if (isset($params[$namespace]) && is_array($params[$namespace]))
      {
        $params[$namespace] = $this->explodeParameters($params[$namespace]);
      }
      return $params;
    }
    else
    {
      return $this->explodeParameters($params);
    }
  }

  /**
   * @param array $params
   * @param array $variables
   * @param array $options
   * @return array Converted parameters
   */
  public function bind($params, $variables, $options = array())
  {
    // check if namespace is specified
    $options = $this->fixOptions($options);
    if (!empty($options['namespace']))
    {
      $namespace = $options['namespace'];
      $params = $this->explodeParameters($params);
      if (isset($params[$namespace]) && is_array($params[$namespace]))
      {
        $variables = $this->filterNamespacedVariables($variables, $namespace);
        $object = $this->getQuery($params[$namespace], $variables, $options)->findOne();
        $params[$namespace]['sf_subject'] = $object;
        return $params;
      }
    }
    else
    {
      $object = $this->getQuery($params, $variables, $options)->findOne();
      $params['sf_subject'] = $object;
      return $params;
    }
  }

  /**
   * @param array|\BaseObject $object
   * @param array $variables
   * @param array $options
   * @return array
   */
  protected function convertObjectToArray($object, $variables, $options = array())
  {
    if (is_array($object))
    {
      if (!isset($object['sf_subject']))
      {
        return $object;
      }

      $parameters = $object;
      $object = $parameters['sf_subject'];
      unset($parameters['sf_subject']);
    }
    else
    {
      $parameters = array();
    }

    return array_merge($parameters, $this->doConvertObjectToArray($object, $variables, $options));
  }

  /**
   * @param \BaseObject $object
   * @param array $variables
   * @param array $options
   * @return array
   */
  protected function doConvertObjectToArray($object, $variables, $options = array())
  {
    if ($options['convert'] || method_exists($object, 'toParams'))
    {
      $method = $options['convert'] ?: 'toParams';
      return call_user_func(array($object, $method), $variables);
    }

    $peerName = constant($options['model'] . '::PEER');

    $parameters = array();
    foreach ($variables as $variable)
    {
      try
      {
        $method = 'get'.call_user_func(array($peerName, 'translateFieldName'), $variable, \BasePeer::TYPE_FIELDNAME, \BasePeer::TYPE_PHPNAME);
      }
      catch (\Exception $e)
      {
        $method = 'get'.\sfInflector::camelize($variable);
      }

      $parameters[$variable] = $object->$method();
    }

    return $parameters;
  }

  protected function getQuery($params, $variables, $options)
  {
    /** @var $query \ModelCriteria */
    $query = \PropelQuery::from($options['model']);
    foreach ($options['query_methods'] as $methodName => $methodParams)
    {
      if(is_string($methodName))
      {
        call_user_func_array(array($query, $methodName), (array)$methodParams);
      }
      else
      {
        $query->$methodParams();
      }
    }
    $query->filterByArray($this->getModelParameters($params, $variables, $options));
    return $query;
  }

  protected function getModelParameters($params, $variables, $options)
  {
    if (!is_array($params))
    {
      return $params;
    }
    $params = $this->filterParameters($params, $variables, $options);
    $peerName = constant($options['model'] . '::PEER');
    $modelParams = array();
    foreach ($variables as $variable)
    {
      try
      {
        $column = call_user_func(array($peerName, 'translateFieldName'), $variable, \BasePeer::TYPE_FIELDNAME, \BasePeer::TYPE_PHPNAME);
      }
      catch (\Exception $e)
      {
        $column = \sfInflector::camelize($variable);
      }
      $modelParams[$column] = $params[$variable];
    }

    return $modelParams;
  }

  protected function filterParameters($params, $variables, $options)
  {
    if (!is_array($params))
    {
      return $params;
    }

    $filtered = array();
    foreach ($variables as $variable)
    {
      $filtered[$variable] = $params[$variable];
    }

    return $filtered;
  }

  /**
   * Filters only variables which start with "$namespace." substring
   *  and removes this namespace
   *
   * @param array $variables variables names
   * @param string $namespace
   * @return array variables names with namespace removed
   */
  protected function filterNamespacedVariables($variables, $namespace)
  {
    $len = strlen($namespace) + 1;
    $check = $namespace . '.';
    $filtered = array();
    foreach ($variables as $var)
    {
      if (substr($var, 0, $len) == $check)
      {
        $filtered[] = substr($var, $len);
      }
    }
    return $filtered;
  }
}
