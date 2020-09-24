<?php

namespace Drupal\mitacan\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystem;

class MitacanXlsxUploadForm extends FormBase {
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['xlsx_upload'] = [
      '#type' => 'managed_file',
      '#title' => 'Excel dosyasını yükleyin',
      '#upload_validators' => [
        'file_validate_extensions' => ['xlsx'],
        'file_validate_size' => [25600000],
      ],
      '#upload_location' => 'public://'
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Yükle'
    ];
    return $form;
  }

  public function getFormId() {
    return 'mitacan.upload';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $src = ['Ý', 'Þ', 'ý', 'þ', 'ð'];
    $tar = ['İ', 'Ş', 'ı', 'ş', 'ğ'];
    $messenger = \Drupal::messenger();
    $connection = \Drupal::database();
    $fid = $form_state->getValue("xlsx_upload");
    $input = File::load(reset($fid));
    $input->setPermanent();
    $uri = \Drupal::service('file_system')->realpath($input->getFileUri());
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($uri);
    $spreadsheet = $reader->load($uri);
    $dataArray = $spreadsheet->getActiveSheet()->toArray();
    $connection->truncate("excel")->execute();
    $ilksatir = TRUE;
    $messenger->addMessage("Excel işlemine başlıyorum.");
    foreach ($dataArray as $dataItem) {
      $bossatir = (is_null($dataItem[0]))?TRUE:FALSE; //if (is_null($dataItem[0])) {$BosSatir = True;} else {$BosSatir = False;};
     $marka = (is_null($dataItem[1]))?"defaultlogo.png":$dataItem[1];//if (is_null($dataItem[1])) {$Marka = 'DefaultLogo.png';} else {$Marka = $dataItem[1];};
      $gorsel = (is_null($dataItem[2]))?"hazirlaniyor.png":$dataItem[2];//if (is_null($dataItem[2])) {$Resim1 = 'Hazirlaniyor.png';} else {$Resim1 = $dataItem[2];};
      $paketadedi = is_null($dataItem[6])?1:$dataItem[6];//if (is_null($dataItem[6])) {$PaketAdedi = 1;} else {$PaketAdedi = $dataItem[6];};
      if ($ilksatir || $bossatir) {
        $ilksatir = FALSE;
      } else {
        $connection->insert("excel")
        ->fields([
          'stoktipi'     => '00000',
          'stokkodu'		 => $dataItem[0],
          'logo'			   => $marka,
          'gorsel'		   => $gorsel,
          'grupkodu' 		 => $dataItem[3],
          'grupadi' 		 => $dataItem[4],
          'grupindex'    => $dataItem[5],
          'paketadedi'   => $paketadedi,
        ])
        ->execute();
        $connection->delete("excel")->condition("stokkodu", "STOKKODU")->execute();
        $connection->delete("excel")->condition("stokkodu", "IGN%", "LIKE")->condition("gorsel", "hazirlaniyor.png")->execute();
        $connection->query('UPDATE excel JOIN excel AS e1 ON excel.grupkodu = e1.grupkodu AND e1.grupindex = 1 SET excel.ebeveyn = e1.stokkodu');
      }
    }
    $messenger->addMessage('Excel verisi veritabanına eklendi.');
    /*
    $batch = [
      $operations => [
        ["excelToDb"]
      ],
      $finished => "excelToDb_finished_callback"
    ];
    batch_set($batch);
    */
  }

  public function excelToDb() {
    $connection = \Drupal::database();
    $fid = $form_state->getValue("xlsx_upload");
    $input = File::load(reset($fid));
    $input->setPermanent();
    $uri = \Drupal::service('file_system')->realpath($input->getFileUri());
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($uri);
    $spreadsheet = $reader->load($uri);
    $dataArray = $spreadsheet->getActiveSheet()->toArray();
    $connection->truncate("mitacan_excel")->execute();
    $connection->query("SET NAMES utf8");
    $ilksatir = TRUE;
    $this->addMessenger()->addMessage("Excel işlemine başlıyorum.");
    foreach ($dataArray as $dataItem) {
      $bossatir = (is_null($dataItem[0]))?TRUE:FALSE; //if (is_null($dataItem[0])) {$BosSatir = True;} else {$BosSatir = False;};
      $marka = (is_null($dataItem[1]))?"defaultlogo.png":$dataItem[1];//if (is_null($dataItem[1])) {$Marka = 'DefaultLogo.png';} else {$Marka = $dataItem[1];};
      $gorsel = (is_null($dataItem[2]))?"hazirlaniyor.png":$dataItem[2];//if (is_null($dataItem[2])) {$Resim1 = 'Hazirlaniyor.png';} else {$Resim1 = $dataItem[2];};
      $paketadedi = is_null($dataItem[6])?1:$dataItem[6];//if (is_null($dataItem[6])) {$PaketAdedi = 1;} else {$PaketAdedi = $dataItem[6];};
      if ($ilksatir || $bossatir) {
        $ilksatir = FALSE;
      } else {
        $connection->insert("mitacan_excel")
        ->fields([
          'stoktipi'     => '00000',
          'stokkodu'		 => $dataItem[0],
          'logo'			   => $marka,
          'gorsel'		   => $gorsel,
          'grupkodu' 		 => $dataItem[3],
          'grupadi' 		 => $dataItem[4],
          'grupindex'    => $dataItem[5],
          'paketadedi'   => $paketadedi,
        ])
        ->execute();
        $connection->query("DELETE FROM mitacan_excel WHERE stokkodu='STOKKODU'");
      }
    }
    drupal_set_message('Excel verisi veritabanına eklendi.');
  }
}
