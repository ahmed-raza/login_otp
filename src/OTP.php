<?php

namespace Drupal\login_otp;

class OTP {
  private $username;
  private $tempStorageFactory;

  public function __construct() {
    $this->tempStorageFactory = \Drupal::service('tempstore.private');
  }

  public function generateOTP($username) {
    $this->username = $username;

    $uid = $this->getField('uid');

    $this->tempStorageFactory->get('login_otp')->set('uid', $uid);
    $this->tempStorageFactory->get('login_otp')->set('otp', $human_readable_otp);


    return FALSE;
  }

  public function sendOTP($otp) {
    $mail_manager = \Drupal::service('plugin.manager.mail');

    $to = $this->getField('mail');
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $params['message'] = $this->t('Hello, @username <br> This is the OTP you will use to login: @otp',
                        [
                          '@username' => $this->username,
                          '@otp' => $otp
                        ]);
    return $mail_manager->mail('login_otp', 'login_otp_mail', $to, $langcode, $params, NULL, TRUE);
  }

  private function getField($field) {
    $database = \Drupal::database();
    $query = $database->select('users_field_data', 'u')
              ->fields('u', [$field])
              ->condition('name', $this->username, '=')
              ->execute()
              ->fetchAssoc();
    return $query['uid'];
  }

  private function exists($uid) {
    $database = \Drupal::database();
    $exists = $database->select('users_field_data', 'u')
              ->fields('u')
              ->condition('uid', $uid, '=')
              ->execute()
              ->fetchAssoc();
    return $exists ?? true;
  }

  private function new($uid) {
    $human_readable_otp = rand(100000, 999999);
    $database = \Drupal::database();
    $database = $database->insert('login_otp')->fields([
      'uid' => $uid,
      'otp' => \Drupal::service('password')->hash($human_readable_otp),
      'expiration' => strtotime("+5 minutes",time())
    ])->execute();
    return $human_readable_otp;
  }
}
