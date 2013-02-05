<?php
/**
 * Date: 14.12.12
 * Time: 2:47
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting;

use Symfony\Component\Routing\Route;

/**
 * Route class that uses Symfony2 routing component internally
 *
 * Extending not strongly needed actually. It's used just to ensure there won't be any
 * problems with type checking and hinting in symfony core and 3rd party libs.
 */
class CurlyRoute extends \sfRoute implements CurlyRouteInterface
{
  /**
   * @var array|object[]
   */
  protected static $classInstances = array();

  /**
   * Constructor.
   *
   * Available options:
   *  * compiler_class:                   A compiler class name that will be used to compile this route
   *  * generator_class:                  An url generator class that will be used to generate url form this route
   *  * matcher_class:                    An url matcher class that will be used to check if pathinfo/context is matching current route
   *
   * @param string $pattern       The pattern to match
   * @param array  $defaults      An array of default parameter values
   * @param array  $requirements  An array of requirements for parameters (regexes)
   * @param array  $options       An array of options
   */
  public function __construct($pattern, array $defaults = array(), array $requirements = array(), array $options = array())
  {
    parent::__construct($pattern, $defaults, $requirements, $options);
  }

  public function initializeOptions()
  {
    $this->options = array_merge(array(
      'compiler_class' => '\Axis\S1\CurlyRouting\Compiler\CurlyRouteCompiler',
      'generator_class' => '\Axis\S1\CurlyRouting\Generator\CurlyRouteUrlGenerator',
      'matcher_class' => '\Axis\S1\CurlyRouting\Matcher\CurlyRouteUrlMatcher'
    ), $this->options);

    $this->fixTransformersConfig($this->options);
  }

  /**
   * @return \Symfony\Component\Routing\CompiledRoute
   */
  public function compile()
  {
    if ($this->compiled)
    {
      if (!is_object($this->compiled))
      {
        $this->compiled = new \Symfony\Component\Routing\CompiledRoute(
          $this->staticPrefix, $this->regex, $this->tokens, $this->variables
        );
      }
      return $this->compiled;
    }

    $this->initializeOptions();
    $this->fixRequirements();
    $this->fixDefaults();

    $proxy = new Route($this->pattern, $this->defaults, $this->requirements, $this->options);

    $this->compiled = $compiledRoute = $proxy->compile();

    $this->regex = $compiledRoute->getRegex();
    $this->staticPrefix = $compiledRoute->getStaticPrefix();
    $this->tokens = $compiledRoute->getTokens();
    $this->variables = $compiledRoute->getVariables();

    return $compiledRoute;
  }

  /**
   * @param array $params
   * @param array|RequestContext $context
   * @param bool $absolute
   * @return string generated url
   */
  public function generate($params, $context = array(), $absolute = false)
  {
    if (!$this->compiled)
    {
      $this->compile();
    }

    $params = $this->transformForUrl($params);

    $generator = $this->getUrlGenerator();
    return $generator->generate($this, $params, $absolute, $this->getContext($context));
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public function getParameter($name, $default = null)
  {
    return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
  }

  /**
   * Tweak variables array to match sfRoute format (var_name => var_pattern)
   *
   * @return array
   */
  public function getVariables()
  {
    $varNames = $this->compile()->getVariables();
    $varPatterns = array_map(function($v) { return "{{$v}}"; }, $varNames);

    $vars = count($varNames) ? array_combine($varNames, $varPatterns) : array();
    return $vars;
  }

  public function bind($context, $parameters)
  {
    foreach ($this->options['transform'] as $config)
    {
      $transformer = $this->getClassInstance($config['class']);
      if ($transformer instanceof \Axis\S1\CurlyRouting\Transformer\BindableDataTransformerInterface)
      {
        $parameters = $transformer->bind($parameters, $this->variables, $config['options']);
      }
    }
    parent::bind($context, $parameters);
  }

  /**
   * @param string $url
   * @param array|RequestContext $context
   * @return array|bool
   */
  function matchesUrl($url, $context = array())
  {
    $params = $this->getUrlMatcher()->matches($this, $url, $this->getContext($context));
    return $params ? $this->transformForController($params) : $params;
  }

//  // Assume that sfRoute::matchesParameters() is able to handle this
//  function matchesParameters($params, $context = array())
//  {
//  }

  /**
   * @param string $name
   * @return string
   */
  public function getRequirement($name)
  {
    return isset($this->requirements[$name]) ? $this->requirements[$name] : null;
  }

  /**
   * @param array $options
   * @throws \LogicException
   */
  protected function fixTransformersConfig(& $options)
  {
    if (isset($options['transform']))
    {
      if (!is_array($options['transform']) || isset($options['transform']['class']))
      {
        $options['transform'] = array($options['transform']);
      }
      foreach($options['transform'] as $key => $transformer)
      {
        if (is_array($transformer))
        {
          if (!isset($transformer['class']))
          {
            throw new \LogicException('You should declare "class" option for data transformer.');
          }
          $class = $transformer['class'];
          unset($transformer['class']);
          $config = $transformer;
        }
        else
        {
          $class = $transformer;
          $config = array();
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
  }

  /**
   * @param array $params
   * @return array
   */
  protected function transformForUrl($params)
  {
    foreach ($this->options['transform'] as $config)
    {
      /** @var $transformer \Axis\S1\CurlyRouting\Transformer\DataTransformerInterface */
      $transformer = $this->getClassInstance($config['class']);
      $params = $transformer->transformForUrl($params, $this->variables, $config['options']);
    }
    return $params;
  }

  /**
   * @param array $params
   * @return array
   */
  protected function transformForController($params)
  {
    foreach ($this->options['transform'] as $config)
    {
      /** @var $transformer \Axis\S1\CurlyRouting\Transformer\DataTransformerInterface */
      $transformer = $this->getClassInstance($config['class']);
      $params = $transformer->transformForController($params, $this->variables, $config['options']);
    }
    return $params;
  }

  /**
   * Ensures that given $context parameter will be an instance of RequestContext
   *
   * @param array|RequestContext $context
   * @return RequestContext
   */
  protected function getContext($context)
  {
    if ($context instanceof RequestContext)
    { // just return it
      return $context;
    }
    // else
    $rc = new RequestContext();
    return $rc->fromContextArray($context); // fetch context array data
  }

  /**
   * @param string $class
   * @return object
   */
  protected function getClassInstance($class)
  {
    if (!isset(self::$classInstances[$class]))
    {
      self::$classInstances[$class] = new $class();
    }
    return self::$classInstances[$class];
  }

  /**
   * @return Generator\RouteUrlGeneratorInterface
   */
  protected function getUrlGenerator()
  {
    return $this->getClassInstance($this->options['generator_class']);
  }

  /**
   * @return Matcher\RouteUrlMatcherInterface
   */
  protected function getUrlMatcher()
  {
    return $this->getClassInstance($this->options['matcher_class']);
  }
}
