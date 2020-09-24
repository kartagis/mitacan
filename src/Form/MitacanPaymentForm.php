<?php

namespace Drupal\mitacan\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class MitacanPaymentForm extends FormBase {
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form["fieldset"] = [
      "#type" => "container"
    ];
    $form["fieldset"]["firma_adi"] = [
      "#type" => "textfield",
      "#title" => "Firma Adı"
    ];
    $form["fieldset"]["kart_sahibi"] = [
      "#type" => "textfield",
      "#title" => "Kart Sahibi"
    ];
    $form["fieldset"]["kart_skt"] = [
      "#type" => "date",
      "#title" => "Kart Son Kullanma Tarihi"
    ];
    $form["fieldset"]["kart_ccv2"] = [
      "#type" => "textfield",
      "#title" => "Kart Güvenlik Kodu",
      "#size" => 3,
      "#maxlength" => 3,
    ];
    $form["fieldset"]["miktar"] = [
      "#type" => "textfield",
      "#title" => "Çekilecek Miktar"
    ];
    $form["fieldset"]["kart_no"] = [
      "#type" => "textfield",
      "#title" => "Kart Numarası"
    ];
    $form["fieldset"]["kart_taksit"] = [
      "#type" => "select",
      "#title" => "Taksit Sayısı",
      "#options" => ["Tek Çekim", 2, 3, 4, 5, 6]
    ];
    $form["submit"] = [
      "#type" => "submit",
      "#value" => "Ödeme Yap"
    ];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \dpm($form_state->getValue("fieldset")["kart_taksit"]);
  }
  public function getFormId() {
    return "mitacan_payment_form";
  }
}
