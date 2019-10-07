<?php namespace web\unittest;

use unittest\TestCase;
use web\Filter;
use web\Filters;
use web\Request;
use web\Response;
use web\io\TestInput;
use web\io\TestOutput;

class FiltersTest extends TestCase {

  #[@test]
  public function can_create() {
    new Filters([], function($req, $res) { });
  }

  #[@test]
  public function runs_handler() {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $invoked= [];
    $fixture= new Filters([], function($req, $res) use(&$invoked) {
      $invoked[]= $req->method().' '.$req->uri()->path();
    });
    $fixture->handle($req, $res);

    $this->assertEquals(['GET /'], $invoked);
  }

  #[@test]
  public function filter_instance() {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $invoked= [];
    $filter= newinstance(Filter::class, [], [
      'filter' => function($req, $res, $invocation) {
        $req->rewrite($req->uri()->using()->path('/rewritten')->create());
        return $invocation->proceed($req, $res);
      }
    ]);
    $fixture= new Filters([$filter], function($req, $res) use(&$invoked) {
      $invoked[]= $req->method().' '.$req->uri()->path();
    });
    $fixture->handle($req, $res);

    $this->assertEquals(['GET /rewritten'], $invoked);
  }

  #[@test]
  public function filter_function() {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $invoked= [];
    $filter= function($req, $res, $invocation) {
      $req->rewrite($req->uri()->using()->path('/rewritten')->create());
      return $invocation->proceed($req, $res);
    };
    $fixture= new Filters([$filter], function($req, $res) use(&$invoked) {
      $invoked[]= $req->method().' '.$req->uri()->path();
    });
    $fixture->handle($req, $res);

    $this->assertEquals(['GET /rewritten'], $invoked);
  }
}
