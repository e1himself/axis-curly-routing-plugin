<?php
/**
 * Date: 15.12.12
 * Time: 19:01
 * Author: Ivan Voskoboynyk
 */
namespace Axis\S1\CurlyRouting;

class CurlyObjectRoute extends CurlyRoute implements CurlyObjectRouteInterface
{
  const DEFAULT_DATA_TRANSFORMER = '\Axis\S1\CurlyRouting\Transformer\PropelObject';

  protected $objects = array();

  /**
   * Flag to ensure that data transformers are bound only once
   *
   * @var bool
   */
  protected $areTransformersBound = false;

  /**
   * Bind data transformers
   *
   * @param $parameters
   */
  public function bindDataTransformers($parameters)
  {
    if (!$this->areTransformersBound)
    {
      foreach ($this->options['transform'] as $config)
      {
        $transformer = $this->getClassInstance($config['class']);
        if ($transformer instanceof Transformer\BindableDataTransformerInterface)
        {
          $parameters = $transformer->bind($parameters, array_keys($this->variables), $config['options']);
        }
      }
      $this->areTransformersBound = true;
    }
    $this->parameters = $parameters; //as they might be updated by transformers
  }

  /**
   * @{@inheritDoc}
   *
   * @param array $context
   * @param array $parameters
   */
  public function bind($context, $parameters)
  {
    // reset transformers binding if route was rebound
    $this->areTransformersBound = false;
    parent::bind($context, $parameters);
  }

  public function getObject($namespace = null)
  {
    // bind data transformers if needed
    $this->bindDataTransformers($this->parameters);

    if (!$this->isBound())
    {
      throw new \LogicException('The route is not bound.');
    }

    if (isset($this->objects[$namespace]))
    {
      return $this->objects[$namespace];
    }

    $allow_empty = isset($this->options['allow_empty']) && $this->options['allow_empty'];

    if ($namespace)
    {
      $object = isset($this->parameters[$namespace]['sf_subject']) ? $this->parameters[$namespace]['sf_subject'] : null;
      // check the related object
      if (!$object && !$allow_empty)
      {
        $params = $this->parameters;
        unset($params['module'], $params['action']);

        throw new \sfError404Exception(sprintf(
          'Unable to find requested namespaced "%s" object with the following parameters "%s").',
          $namespace,
          $this->var_export($params)
        ));
      }
    }
    else
    {
      $object = isset($this->parameters['sf_subject']) ? $this->parameters['sf_subject'] : null;
      // check the related object
      if (!$object && !$allow_empty)
      {
        $params = $this->parameters;
        unset($params['module'], $params['action']);
        throw new \sfError404Exception(sprintf(
          'Unable to find the object with the following parameters "%s").',
          $this->var_export($params)
        ));
      }
    }

    $this->objects[$namespace] = $object;

    return $object;
  }

  protected function fixTransformersConfig(& $options)
  {
    if (isset($options['transform']))
    {
      if (!is_array($options['transform']))
      {
        $options['transform'] = array($options['transform']);
      }
      foreach($options['transform'] as $key => $transformer)
      {
        if (is_array($transformer))
        {
          if (!isset($transformer['class']))
          {
            $transformer['class'] = static::DEFAULT_DATA_TRANSFORMER;
          }
          $class = $transformer['class'];
          unset($transformer['class']);
          $config = $transformer;
        }
        else
        {
          $class = static::DEFAULT_DATA_TRANSFORMER;
          $config = array('model' => $transformer);
        }
        if (is_string($key))
        {
          $config['_name'] = $key;
        }
        $options['transform'][$key] = array('class' => $class, 'options' => $config);
      }
    }
    else
    {
      $options['transform'] = array();
    }

    if (isset($options['model']))
    {
      $config = $options;
      unset($config['transformers']);
      $options['transform'][] = array(
        'class' => static::DEFAULT_DATA_TRANSFORMER,
        'options' => $options
      );
    }
  }

  /**
   * @internal
   * @param array $params
   * @return string
   */
  private function var_export_fix($params)
  {
    foreach ($params as $key => $param)
    {
      if ($key == 'sf_subject')
      {
        unset($params[$key]);
      }
      elseif (is_array($param))
      {
        $params[$key] = $this->var_export_fix($param);
      }
      elseif (is_object($param))
      {
        $params[$key] = '['.get_class($param).']';
      }
    }
    return $params;
  }

  /**
   * @internal
   * @param array $params
   * @return string
   */
  private function var_export($params)
  {
    return str_replace("\n", '', var_export($this->var_export_fix($params), true));
  }
}
