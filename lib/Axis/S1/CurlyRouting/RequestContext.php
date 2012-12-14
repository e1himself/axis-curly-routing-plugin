<?php
/**
 * Date: 14.12.12
 * Time: 5:26
 * Author: Ivan Voskoboynyk
 */

namespace Axis\S1\CurlyRouting;

class RequestContext extends \Symfony\Component\Routing\RequestContext implements \ArrayAccess, \IteratorAggregate
{
  protected static $supported = array('prefix', 'method', 'host', 'is_secure', 'http_port', 'https_port');
  protected $fallback = array();

  /**
   * @param array $context
   * @return RequestContext
   */
  public function fromContextArray($context)
  {
    $this->fallback = array();
    foreach ($context as $key => $value)
    {
      $this->offsetSet($key, $value); // used offsetSet
    }
    return $this;
  }

  /**
   * @param mixed $offset
   * @return boolean true on success or false on failure.
   */
  public function offsetExists($offset)
  {
    return in_array($offset, self::$supported) || array_key_exists($offset, $this->fallback);
  }

  /**
   * @param mixed $offset
   * @return mixed Can return all value types.
   */
  public function offsetGet($offset)
  {
    if ($offset == 'prefix') return $this->getBaseUrl();
    elseif ($offset == 'method') return $this->getMethod();
    elseif ($offset == 'host') return $this->getHost();
    elseif ($offset == 'is_secure') return $this->getScheme() == 'https';
    elseif ($offset == 'http_port') return $this->getHttpPort();
    elseif ($offset == 'https_port') return $this->getHttpsPort();
    else
      return $this->fallback[$offset];
  }

  /**
   * @param mixed $offset The offset to assign the value to.
   * @param mixed $value The value to set.
   * @return void
   */
  public function offsetSet($offset, $value)
  {
    if ($offset == 'prefix') $this->setBaseUrl($value);
    elseif ($offset == 'method') $this->setMethod($value);
    elseif ($offset == 'host') $this->setHost($value);
    elseif ($offset == 'is_secure') $this->setScheme($value ? 'https' : 'http');
    elseif ($offset == 'http_port') $this->setHttpPort($value);
    elseif ($offset == 'https_port') $this->setHttpsPort($value);
    else
      $this->fallback[$offset] = $value;
  }

  /**
   * Offset to unset
   * @param mixed $offset The offset to unset.
   * @return void
   */
  public function offsetUnset($offset)
  {
    if (array_key_exists($offset, $this->fallback[$offset]))
    {
      unset($this->fallback[$offset]);
    }
  }

  /**
   * Retrieve an external iterator
   * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
   * @return \Traversable An instance of an object implementing Iterator or Traversable
   */
  public function getIterator()
  {
    return new \ArrayObject(self::$supported + array_keys($this->fallback));
  }
}
