<?php

namespace Drupal\email_login_otp\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OTPRedirectSubscriber.
 */
class OtpRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new OTPRedirectSubscriber object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['loginRedirect'];
    return $events;
  }

  /**
   * This method is called when the login_redirect is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function loginRedirect(GetResponseEvent $event) {
    if (\Drupal::service('path.current')->getPath() == '/login-otp' && \Drupal::currentUser()->isAuthenticated()) {
      $redirect = new RedirectResponse('/user');
      return $redirect->send();
    }
  }

}
