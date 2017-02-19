<?php namespace xp\web;

use lang\ClassLoader;
use lang\Throwable;
use web\Error;
use web\InternalServerError;
use web\Request;
use web\Response;
use web\Status;

/**
 * HTTP protocol implementation
 */
class HttpProtocol implements \peer\server\ServerProtocol {
  public $server= null;

  /**
   * Creates a new protocol instance
   *
   * @param  web.Application $application
   * @param  function(web.Request, web.Response): void $logging
   */
  public function __construct($application, $logging) {
    $this->application= $application;
    $this->logging= $logging;
  }

  /**
   * Sends an error
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  web.Error $error
   * @return void
   */
  private function sendError($request, $response, $error) {
    $loader= ClassLoader::getDefault();
    $message= Status::message($error->status());

    $response->answer($error->status(), $message);
    foreach (['web/error-'.$this->application->environment()->profile().'.html', 'web/error.html'] as $variant) {
      if (!$loader->providesResource($variant)) continue;
      $response->send(
        sprintf(
          $loader->getResource($variant),
          $error->status(),
          $message,
          $error->getMessage(),
          $error->toString()
        ),
        'text/html'
      );
      break;
    }
    $this->logging->__invoke($request, $response, $error->compoundMessage());
  }

  /**
   * Initialize Protocol
   *
   * @return bool
   */
  public function initialize() {
    return true;
  }

  /**
   * Handle client connect
   *
   * @param  peer.Socket $socket
   */
  public function handleConnect($socket) {
    // Intentionally empty
  }

  /**
   * Handle client disconnect
   *
   * @param  peer.Socket $socket
   */
  public function handleDisconnect($socket) {
    $socket->close();
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleData($socket) {
    gc_enable();

    $input= new Input($socket);
    if (null === ($message= $input->readLine())) return;
    sscanf($message, '%s %s HTTP/%d.%d', $method, $uri, $major, $minor);

    $request= new Request($method, 'http://localhost:8080'.$uri, $input);
    $response= new Response(new Output($socket));

    try {
      $this->application->service($request, $response);
      $this->logging->__invoke($request, $response);
    } catch (Error $e) {
      $this->sendError($request, $response, $e);
    } catch (\Throwable $e) {   // PHP7
      $this->sendError($request, $response, new InternalServerError($e));
    } catch (\Exception $e) {   // PHP5
      $this->sendError($request, $response, new InternalServerError($e));
    } finally {
      gc_collect_cycles();
      gc_disable();
      clearstatcache();
      \xp::gc();

      if ('Keep-Alive' === $request->header('Connection')) {
        // TODO: If there is POST data and it hasn't been consumed, close connection
      } else {
        $socket->close();
      }
    }
  }

  /**
   * Handle I/O error
   *
   * @param  peer.Socket $socket
   * @param  lang.XPException $e
   */
  public function handleError($socket, $e) {
    // $e->printStackTrace();
    $socket->close();
  }
}
