<?php namespace web;

use lang\Throwable;

/**
 * Application is at the heart at every web project.
 *
 * @test  xp://web.unittest.ApplicationTest
 */
abstract class Application implements \lang\Value {
  private $routing;
  protected $environment;

  /**
   * Creates a new web application inside a given environment
   *
   * @param  web.Environment $environment
   */
  public function __construct(Environment $environment) {
    $this->environment= $environment;
  }

  /** @return web.Environment */
  public function environment() { return $this->environment; }

  /**
   * Returns routing, lazily initialized
   *
   * @return web.Routing
   */
  public final function routing() {
    if (null === $this->routing) {
      $this->routing= Routing::cast($this->routes());
    }
    return $this->routing;    
  }

  /**
   * Returns this application's routes, which are either a `Routing`
   * instance or a map of paths to routing targets.
   *
   * _Overwrite this in your implementation!_
   *
   * @return web.Routing|[:var]
   */
  protected abstract function routes();

  /**
   * Service delegates to the routing, calling its `service()` method. Takes care
   * of handling exceptions raised from routing, setting response's error member.
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return void
   */
  public function service($request, $response) {
    try {
      $this->routing()->service($request, $response);
    } catch (Throwable $t) {
      $response->error($t);
    } catch (\Throwable $e) {   // PHP7
      $response->error(Throwable::wrap($e));
    } catch (\Exception $e) {   // PHP5
      $response->error(Throwable::wrap($e));
    }
  }

  /** @return string */
  public function toString() { return nameof($this).'('.$this->environment->docroot().')'; }

  /** @return string */
  public function hashCode() { return spl_object_hash($this); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? strcmp($this->hashCode(), $value->hashCode()) : 1;
  }
}