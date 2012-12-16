<?php
/**
 * Date: 15.12.12
 * Time: 18:02
 * Author: Ivan Voskoboynyk
 */
namespace Axis\S1\CurlyRouting\Compiler;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\RouteCompilerInterface;

/**
 * This class is almost a copy of default Symfony2 RouteCompiler.
 * It differs only in a pattern the variables names are matched: dot symbol is now allowed.
 * Also computeRegexp() method is now protected to allow extending and overriding and added preg_quoting for variable names.
 */
class CurlyRouteCompiler implements RouteCompilerInterface
{
  const REGEX_DELIMITER = '#';
  const VARIABLE_NAME_PATTERN = '\{([\w\.]+)\}';

  /**
   * {@inheritDoc}
   *
   * @throws \LogicException If a variable is referenced more than once
   */
  public function compile(Route $route)
  {
    $pattern = $route->getPattern();
    $len = strlen($pattern);
    $tokens = array();
    $variables = array();
    $pos = 0;
    preg_match_all('#.'.static::VARIABLE_NAME_PATTERN.'#', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
    foreach ($matches as $match) {
      if ($text = substr($pattern, $pos, $match[0][1] - $pos)) {
        $tokens[] = array('text', $text);
      }

      $pos = $match[0][1] + strlen($match[0][0]);
      $var = $match[1][0];

      if ($req = $route->getRequirement($var)) {
        $regexp = $req;
      } else {
        // Use the character preceding the variable as a separator
        $separators = array($match[0][0][0]);

        if ($pos !== $len) {
          // Use the character following the variable as the separator when available
          $separators[] = $pattern[$pos];
        }
        $regexp = sprintf('[^%s]+', preg_quote(implode('', array_unique($separators)), self::REGEX_DELIMITER));
      }

      $tokens[] = array('variable', $match[0][0][0], $regexp, $var);

      if (in_array($var, $variables)) {
        throw new \LogicException(sprintf('Route pattern "%s" cannot reference variable name "%s" more than once.', $route->getPattern(), $var));
      }

      $variables[] = $var;
    }

    if ($pos < $len) {
      $tokens[] = array('text', substr($pattern, $pos));
    }

    // find the first optional token
    $firstOptional = INF;
    for ($i = count($tokens) - 1; $i >= 0; $i--) {
      $token = $tokens[$i];
      if ('variable' === $token[0] && $route->hasDefault($token[3])) {
        $firstOptional = $i;
      } else {
        break;
      }
    }

    // compute the matching regexp
    $regexp = '';
    for ($i = 0, $nbToken = count($tokens); $i < $nbToken; $i++) {
      $regexp .= $this->computeRegexp($tokens, $i, $firstOptional);
    }

    return new CompiledRoute(
      'text' === $tokens[0][0] ? $tokens[0][1] : '',
      self::REGEX_DELIMITER.'^'.$regexp.'$'.self::REGEX_DELIMITER.'s',
      array_reverse($tokens),
      $variables
    );
  }

  /**
   * Computes the regexp used to match a specific token. It can be static text or a subpattern.
   *
   * @param array   $tokens        The route tokens
   * @param integer $index         The index of the current token
   * @param integer $firstOptional The index of the first optional token
   *
   * @return string The regexp pattern for a single token
   */
  protected function computeRegexp(array $tokens, $index, $firstOptional)
  {
    $token = $tokens[$index];
    if ('text' === $token[0]) {
      // Text tokens
      return preg_quote($token[1], self::REGEX_DELIMITER);
    } else {
      // Variable tokens
      if (0 === $index && 0 === $firstOptional) {
        // When the only token is an optional variable token, the separator is required
        return sprintf('%s(?P<%s>%s)?', preg_quote($token[1], self::REGEX_DELIMITER), str_replace('.', '_', $token[3]), $token[2]);
      } else {
        $regexp = sprintf('%s(?P<%s>%s)', preg_quote($token[1], self::REGEX_DELIMITER), str_replace('.', '_', $token[3]), $token[2]);
        if ($index >= $firstOptional) {
          // Enclose each optional token in a subpattern to make it optional.
          // "?:" means it is non-capturing, i.e. the portion of the subject string that
          // matched the optional subpattern is not passed back.
          $regexp = "(?:$regexp";
          $nbTokens = count($tokens);
          if ($nbTokens - 1 == $index) {
            // Close the optional subpatterns
            $regexp .= str_repeat(")?", $nbTokens - $firstOptional - (0 === $firstOptional ? 1 : 0));
          }
        }

        return $regexp;
      }
    }
  }
}
