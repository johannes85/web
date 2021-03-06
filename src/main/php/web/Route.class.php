<?php namespace web;

use web\handler\Call;
use web\routing\Match;

class Route {
  private $match, $hander;

  /**
   * Creates a new route
   *
   * @param  web.routing.Match|function(web.Request): web.Handler $match
   * @param  web.Handler|function(web.Request, web.Response): var $handler
   */
  public function __construct($match, $handler) {
    if ($match instanceof Match) {
      $this->match= $match;
    } else {
      $this->match= newinstance(Match::class, [], ['matches' => $match]);
    }

    if ($handler instanceof Handler) {
      $this->handler= $handler;
    } else {
      $this->handler= new Call($handler);
    }
  }

  /**
   * Routes request and returns handler
   *
   * @param  web.Request $request
   * @return web.Handler
   */
  public function route($request) {
    if ($this->match->matches($request)) {
      return $this->handler;
    } else {
      return null;
    }
  }
}