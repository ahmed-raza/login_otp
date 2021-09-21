<?php

namespace Drupal\email_login_otp\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Path\CurrentPathStack;

/**
 * Class OtpRedirectSubscriber.
 */
class OtpRedirectSubscriber implements EventSubscriberInterface {
  protected $currentUser;
  protected $currentPath;

  /**
   * Constructs a new OtpRedirectSubscriber object.
   */
  public function __construct(AccountInterface $current_user, CurrentPathStack $current_path) {
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
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
    if ($this->currentPath == '/login-otp' && $this->currentUser->isAuthenticated()) {
      $redirect = new RedirectResponse('/user');
      return $redirect->send();
    }
  }

}
