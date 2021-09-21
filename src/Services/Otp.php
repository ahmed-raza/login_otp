<?php

namespace Drupal\email_login_otp\Services;
use Drupal\Core\Database\Connection;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Otp {
  protected $database;
  protected $mailManager;
  protected $languageManager;

  private $username;
  private $tempStorageFactory;

  public function __construct(Connection $connection, MailManagerInterface $mail_manager, PasswordInterface $hasher, LanguageManagerInterface $language_manager, ContainerInterface $container) {
    $this->tempStorageFactory = $container->get('tempstore.private');
    $this->database = $connection;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->hasher = $hasher;
  }

  public function generate($username) {
    $this->username = $username;
    $uid = $this->getField('uid');
    $this->tempStorageFactory->get('email_login_otp')->set('uid', $uid);

    if ($this->exists($uid)) {
      return $this->update($uid);
    }
    return $this->new($uid);
  }

  public function send($otp) {
    $to = $this->getField('mail');
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $params['message'] = t('Hello, @username <br> This is the OTP you will use to login: @otp',
                        [
                          '@username' => $this->username,
                          '@otp' => $otp
                        ]);
    return $this->mailManager->mail('email_login_otp', 'email_login_otp_mail', $to, $langcode, $params, NULL, TRUE);
  }

  public function check($uid, $otp) {
    if ($this->exists($uid)) {
      $select = $this->database->select('email_login_otp', 'u')
                ->fields('u', ['otp', 'expiration'])
                ->condition('uid', $uid, '=')
                ->execute()
                ->fetchAssoc();
      if ($select['expiration'] >= time() && $this->hasher->check($otp, $select['otp'])) {
        return true;
      }
      return false;
    }
    return false;
  }

  public function expire($uid) {
    $delete = $this->database->delete('email_login_otp')
              ->condition('uid', $uid)
              ->execute();
    return $delete;
  }

  private function getField($field) {
    $query = $this->database->select('users_field_data', 'u')
              ->fields('u', [$field])
              ->condition('name', $this->username, '=')
              ->execute()
              ->fetchAssoc();
    return $query[$field];
  }

  private function exists($uid) {
    $exists = $this->database->select('email_login_otp', 'u')
              ->fields('u')
              ->condition('uid', $uid, '=')
              ->execute()
              ->fetchAssoc();
    return $exists ?? true;
  }

  private function new($uid) {
    $human_readable_otp = rand(100000, 999999);
    $insert_otp_info = $this->database->insert('email_login_otp')->fields([
      'uid' => $uid,
      'otp' => $this->hasher->hash($human_readable_otp),
      'expiration' => strtotime("+5 minutes",time())
    ])->execute();
    return $human_readable_otp;
  }

  private function update($uid) {
    $human_readable_otp = rand(100000, 999999);
    $update_otp_info = $this->database->update('email_login_otp')
              ->fields([
                'otp' => $this->hasher->hash($human_readable_otp),
                'expiration' => strtotime("+5 minutes",time())
              ])
              ->condition('uid', $uid, '=')
              ->execute();
    return $human_readable_otp;
  }
}
