<?php

namespace Drupal\mitacan\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MitacanUsdRateForm extends FormBase {
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['usd_rate'] = [
      '#type' => 'textfield',
      '#title' => 'USD kurunu giriniz.',
      '#default_value' => \Drupal::configFactory()->getEditable('mitacan.settings')->get('usd_rate')
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Kaydet',
      '#attributes' => ['class' => ['btn', 'btn-success']],
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('mitacan.settings')->set('usd_rate', $form_state->getValue('usd_rate'))->save();
  }

  public function getEditableConfigNames() {
    return 'mitacan.settings';
  }

  public function getFormId() {
    return 'mitacan_usd_rate';
  }
}
