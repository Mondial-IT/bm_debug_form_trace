<?php

namespace Drupal\bm_debug_form_trace;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Logs requests & controllers for routes (incl. AJAX) — useful for Gavias modals.
 */
class RequestLogger implements EventSubscriberInterface {

  public function __construct(
    private LoggerInterface $logger,
    private FileSystemInterface $fs,
    private CurrentRouteMatch $routeMatch,
    private RequestStack $requestStack,
    private PrivateTempStoreFactory $tempStoreFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 30],
      KernelEvents::CONTROLLER => ['onController', 0],
    ];
  }

  /**
   * Only log when session flag is enabled.
   */
  private function isEnabled(): bool {
    try {
      $store = $this->tempStoreFactory->get('bm_debug_form_trace');
      return (bool) ($store->get('enabled') ?? FALSE);
    } catch (\Throwable $e) {
      return FALSE;
    }
  }

  /**
   * Log incoming requests that match our patterns.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$this->isEnabled()) {
      return;
    }

    $req = $event->getRequest();
    $path = $req->getRequestUri();
    if (!$this->shouldLogPath($path)) {
      return;
    }

    $ajax = $req->isXmlHttpRequest() ? 'yes' : 'no';
    $method = $req->getMethod();
    $route = $this->routeMatch->getRouteName() ?? 'N/A';
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $post = $req->request->all();
    $keys = implode(',', array_keys($post));
    $preview = substr(json_encode(array_slice($post, 0, 1)), 0, 200);

    $msg = "REQ route=$route method=$method path=$path ajax:$ajax lang:$lang keys:$keys preview:$preview";
    $this->logger->notice($msg);
    $this->file($msg);
  }

  /**
   * Log the controller that will handle the request.
   */
  public function onController(ControllerEvent $event): void {
    if (!$this->isEnabled()) {
      return;
    }

    $req = $event->getRequest();
    $path = $req->getRequestUri();
    if (!$this->shouldLogPath($path)) {
      return;
    }

    $controller = $event->getController();
    $desc = $this->describeController($controller);
    $route = $this->routeMatch->getRouteName() ?? 'N/A';
    $msg = "CTRL route=$route controller=$desc path=$path";
    $this->logger->notice($msg);
    $this->file($msg);
  }

  private function describeController($controller): string {
    if (is_array($controller)) {
      $obj = is_object($controller[0]) ? get_class($controller[0]) : (string) $controller[0];
      $meth = (string) $controller[1];
      return "$obj::$meth";
    }
    if (is_object($controller)) {
      return get_class($controller);
    }
    if (is_string($controller)) {
      return $controller;
    }
    return 'unknown';
  }

  /**
   * Append to log file.
   */
  private function file(string $line): void {
    try {
      $uri = 'public://bm_debug_form_trace.log';
      $real = $this->fs->realpath($uri);
      $stamp = date('c');
      file_put_contents($real, "[$stamp] $line\n", FILE_APPEND);
    } catch (\Throwable $e) {
      // swallow
    }
  }

  /**
   * Only log certain paths (reduce noise).
   */
  private function shouldLogPath(string $path): bool {
    return (stripos($path, 'gavias') !== FALSE)
      || (stripos($path, 'pagebuilder') !== FALSE)
      || (stripos($path, 'gpb') !== FALSE);
  }

}
