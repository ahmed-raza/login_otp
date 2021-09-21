<?php

namespace Drupal\email_login_otp\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\user\Entity\User;
/**
 * Class OtpForm.
 */
class OtpForm extends FormBase {
  private $tempStoreFactory;
  private $otp_service;

  public function __construct(PrivateTempStoreFactory $tempStoreFactory) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->otp_service = \Drupal::service('email_login_otp.otp');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['otp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OTP'),
      '#description' => $this->t('Enter the OTP you received in email.'),
      '#weight' => '0',
      '#required' => TRUE
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxOTPCallback',
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  public function ajaxOTPCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $tempstore = $this->tempStoreFactory->get('email_login_otp');
    $uid = $tempstore->get('uid');
    $value = $form_state->getValue('otp');
    if ($this->otp_service->check($uid, $value) == false) {
      $form_state->setErrorByName('otp', $this->t('Invalid or expired OTP.'));
    }
    if ($form_state->getErrors()) {
      unset($form['#prefix']);
      unset($form['#suffix']);
      $form['status_messages'] = [
        '#type'   => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new ReplaceCommand('.otp-form', $form));
      return $response;
    }
    $account = User::load($uid);
    $this->otp_service->expire($uid);
    $tempstore->delete('uid');
    user_login_finalize($account);
    $redirect_command = new RedirectCommand('/user');
    $response->addCommand($redirect_command);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
