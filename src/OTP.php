<?php

namespace Drupal\login_otp;

use Drupal\Core\Password\PasswordInterface;

class OTP {
  private $username;
  private $otp;

  public function __construct($username) {
    $this->username = $username;
  }

  public function generateOTP() {
    $getUid = $this->getUid();

    $human_readable_otp = rand(100000, 999999);

    $database = \Drupal::database();
    $database = $database->insert('login_otp')->fields([
      'uid' => $this->uid,
      'otp' => PasswordInterface::hash($human_readable_otp),
      'expiration' => strtotime("+5 minutes",time())
    ]);

    if ($database->execute()) {
      return $human_readable_otp;
    }

    return $getUid;
  }

  private function getUid() {
    $database = \Drupal::database();
    $query = $database->select('users_field_data', 'u')
              ->fields('u', ['uid'])
              ->condition('name', $this->username, '=')
              ->execute()
              ->fetchAssoc();
    return $query['uid'];
  }
}
