<?php

namespace Drupal\login_otp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CssCommand;

/**
 * Class OTPForm.
 */
class OTPForm extends FormBase {

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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      if ($key == 'otp' && empty($value)) {
        $form_state->setErrorByName('otp', $this->t('OTP is required.'));
      }
      if ($key == 'otp' && strlen($value) < 6) {
        $form_state->setErrorByName('otp', $this->t('OTP is incomplete.'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  public function ajaxOTPCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
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
    \Drupal::messenger()
      ->addMessage($this->t('@value', [
        '@value' => $form_state->getValue('otp')
      ]));
    unset($form['#prefix']);
    unset($form['#suffix']);
    $form['status_messages'] = [
      '#type'   => 'status_messages',
      '#weight' => -10,
    ];
    $form['otp']['#value'] = '';
    $response->addCommand(new ReplaceCommand('.otp-form', $form));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}