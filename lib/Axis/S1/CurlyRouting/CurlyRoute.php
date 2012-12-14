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
   * @var array|Generator\RouteUrlGeneratorInterface[]
   */
  protected static $generators = array();
  /**
   * @var array|Matcher\RouteUrlMatcherInterface[]
   */
  protected static $matchers = array();

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
      'compiler_class' => '\Symfony\Component\Routing\RouteCompiler',
      'generator_class' => '\Axis\S1\CurlyRouting\Generator\CurlyRouteUrlGenerator',
      'matcher_class' => '\Axis\S1\CurlyRouting\Matcher\CurlyRouteUrlMatcher'
    ), $this->options);
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
          $this->staticPrefix,
          $this->regex,
          $this->tokens,
          $this->variables
        );
      }
      return $this->compiled;
    }

    $this->initializeOptions();
    $this->fixRequirements();
    $this->fixDefaults();

    $proxy = new Route(
      $this->pattern,
      $this->defaults,
      $this->requirements,
      $this->options
    );

    $this->compiled = $compiledRoute = $proxy->compile();

    $this->regex = $compiledRoute->getRegex();
    $this->staticPrefix = $compiledRoute->getStaticPrefix();
    $this->tokens = $compiledRoute->getTokens();
    $this->variables =  $compiledRoute->getVariables();

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

    $generator = $this->getUrlGenerator();
    return $generator->generate($this, $params, $absolute, $this->getContext($context));
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
   * @return Generator\RouteUrlGeneratorInterface
   */
  protected function getUrlGenerator()
  {
    $class = $this->options['generator_class'];
    if (!isset(self::$generators[$class]))
    {
      self::$generators[$class] = new $class();
    }
    return self::$generators[$class];
  }

  /**
   * @return Matcher\RouteUrlMatcherInterface
   */
  protected function getUrlMatcher()
  {
    $class = $this->options['matcher_class'];
    if (!isset(self::$matchers[$class]))
    {
      self::$matchers[$class] = new $class();
    }
    return self::$matchers[$class];
  }

  /**
   * @param string $url
   * @param array|RequestContext $context
   * @return array|bool
   */
  function matchesUrl($url, $context = array())
  {
    return $this->getUrlMatcher()->matches($this, $url, $this->getContext($context));
  }

//  // hope sfRoute::matchesParameters is able to handle this
//  function matchesParameters($params, $context = array())
//  {
//    // TODO: Implement matchesParameters() method.
//  }

  /**
   * @param string $name
   * @return string
   */
  public function getRequirement($name)
  {
    return isset($this->requirements[$name]) ? $this->requirements[$name] : null;
  }
}
