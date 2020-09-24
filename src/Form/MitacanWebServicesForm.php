<?php

namespace Drupal\mitacan\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MitacanWebServicesForm extends FormBase {
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['grupkodu_1'] = [
      '#type' => 'submit',
      '#value' => 'GrupKodu1 İçeri Al',
      '#submit' => ['::GrupKodu1IceriAlma']
    ];
    $form['actions']['grupkodu_2'] = [
      '#type' => 'submit',
      '#value' => 'GrupKodu2 İçeri Al',
      '#submit' => ['::GrupKodu2IceriAlma']
    ];
    $form['actions']['grupkodu_3'] = [
      '#type' => 'submit',
      '#value' => 'GrupKodu3 İçeri Al',
      '#submit' => ['::GrupKodu3IceriAlma']
    ];
    $form['actions']['cariler'] = [
      '#type' => 'submit',
      '#value' => 'Carileri İçeri Al',
      '#submit' => ['::CarilerIceriAlma']
    ];
    $form['actions']['urunler'] = [
      '#type' => 'submit',
      '#value' => 'Ürünleri İçeri Al',
      '#submit' => ['::UrunlerIceriAlma']
    ];
    return $form;
  }

  public function GrupKodu1IceriAlma() {
    $req = \Drupal::httpClient()->get("http://mitapp.mitacan.com/b2b/api/vstklib/GetStkGrb1?UserCode=semih@bilgesan.com");
    $res = $req->getBody();
    $res = json_decode($res);
    foreach ($res->Data as $r) {
      $term = Term::create([
	  'vid' => 'stok_grup_kodu',
	  'name' => $r->GrupAdi,
	  'field_stok_grup_kodu' => $r->Grup1Kodu,
      ]);
      $term->enforceIsNew();
      $term->save();
    }
  }

  public function GrupKodu2IceriAlma() {
    $birinci = \Drupal::database()->select("taxonomy_term__field_stok_grup_kodu", "ttfsgk")->fields("ttfsgk", ["entity_id","field_stok_grup_kodu_value"])->condition("field_stok_grup_kodu_value", "1%", "LIKE")->execute()->fetchAll();
    foreach ($birinci as $bir) {
      $req = \Drupal::httpClient()->get("http://mitapp.mitacan.com/b2b/api/vstklib/GetStkGrb2?UserCode=semih@bilgesan.com&Grup1Code=".$bir->field_stok_grup_kodu_value);
      $res = $req->getBody();
      $res = json_decode($res);
      foreach ($res->Data as $r) {
	$term = Term::create([
	    'vid' => 'stok_grup_kodu',
	    'name' => $r->GrupAdi,
	    'field_stok_grup_kodu' => $r->Grup2Kodu,
	    'parent' => $bir->entity_id
	]);
	$term->save();
      }
    }
  }

  public function GrupKodu3IceriAlma() {
    $birinci = \Drupal::database()->select("taxonomy_term__field_stok_grup_kodu", "ttfsgk")->fields("ttfsgk", ["entity_id","field_stok_grup_kodu_value"])->condition("field_stok_grup_kodu_value", "1%", "LIKE")->execute()->fetchAll();
    $ikinci = \Drupal::database()->select("taxonomy_term__field_stok_grup_kodu", "ttfsgk")->fields("ttfsgk", ["entity_id","field_stok_grup_kodu_value"])->condition("field_stok_grup_kodu_value", "2%", "LIKE")->execute()->fetchAll();
    foreach ($birinci as $bir) {
      foreach ($ikinci as $iki) {
	$req = \Drupal::httpClient()->get("http://mitapp.mitacan.com/b2b/api/vstklib/GetStkGrb3?UserCode=semih@bilgesan.com&Grup1Code=".$bir->field_stok_grup_kodu_value."&Grup2Code=".$iki->field_stok_grup_kodu_value);
	$res = $req->getBody();
	$res = json_decode($res);
	foreach ($res->Data as $r) {
	  $queue = Drupal::service('queue')->get('mitacanQueue');
	  if ($queue->createItem($r)) {
	    $term = Term::create([
		'vid' => 'stok_grup_kodu',
		'name' => $r->GrupAdi,
		'field_stok_grup_kodu' => $r->Grup3Kodu,
		'parent' => $iki->entity_id
	    ]);
	    $term->enforceIsNew();
	    $term->save();
	  }
	}
      }
    }
  }
  public function UrunlerIceriAlma() {
    $product_tables = \Drupal::entityTypeManager()->getStorage('commerce_product')->getTableMapping()->getTableNames();
    foreach($product_tables as $product_table) {
      truncate($product_table);
    }
    truncate("url_alias");
    $req = \Drupal::httpClient()->get('http://mitapp.mitacan.com/b2bv2/api/Mitacan/GetStkKodList');
    $res = $req->getBody();
    $res = json_decode($res);
    $sid = \Drupal::entityManager()->getStorage('commerce_store')->loadDefault()->id();
    foreach($res->Data as $r) {
      $stokkodu = [
	'query' => [
	  'stokKodu' => $r->StokKodu
	]
      ];
      $depomiktari = \Drupal::httpClient()->get("http://mitapp.mitacan.com/b2bv2/api/Mitacan/GetDepoMiktari", $stokkodu);
      $depomiktari = $depomiktari->getBody();
      $depomiktari = json_decode($depomiktari);
      foreach($depomiktari->Data as $r) {
	$stokmiktari = $r->stokMiktari;
      }
      $entity_id_1 = "";
      $entity_id_2 = "";
      $entity_id_3 = "";
      $sid = \Drupal::entityManager()->getStorage('commerce_store')->loadDefault()->id();
      $grupadi = \Drupal::database()->query("SELECT ttfd.tid,ttfd.name,ttfsk.field_stok_kodu_value,ttfsk.entity_id FROM taxonomy_term_field_data AS ttfd INNER JOIN taxonomy_term__field_stok_kodu AS ttfsk ON ttfd.tid=ttfsk.entity_id WHERE ttfsk.field_stok_kodu_value = :stokkodu", [":stokkodu" => $r->StokKodu])->fetchAll();
      $grupadi = $grupadi[0]->tid;
      $query = \Drupal::database()->select('excel', 'e')->fields('e')
	->condition('stokkodu', $r->StokKodu);
      $or = $query->orConditionGroup()
	->condition('grupindex', 1)
	->condition('grupindex', NULL, 'IS NULL');
      $results = $query->condition($or)->execute()->fetchAll();
      foreach($results as $result) {
	$imagename = "public://urun/".$result->gorsel;
	   $images = \Drupal::entityTypeManager()
	   ->getStorage("file")
	   ->loadByProperties(["uri" => $imagename]);
	   $image = reset($images);
	if (!$image) {
	$image = File::create([
	    'uri' => $imagename,
	]);
	$image->save();
	}
	$logoname = "public://urun/logo/".$result->logo;
	   $logos = \Drupal::entityTypeManager()
	   ->getStorage("file")
	   ->loadByProperties(["uri" => $logoname]
	       );
	   $logo = reset($logos);
	   if (!$logo) {
	$logo = File::create([
	    'uri' => $logoname,
	]);
	$logo->save();
	}
	$price_data = !empty($r->Fiyat)?(string) $r->Fiyat:"0.00";
	$price = new Price($price_data, ($r->DovizCinsi === "TRL")?"TRY":$r->DovizCinsi);
	$variation = ProductVariation::create([
	    'type' => 'default',
	    'title' => $r->StokAdi,
	    'sku' => $r->StokKodu,
	    'price' => $price,
	    'field_listeli_urun_sirasi' => $result->grupindex,
	    'field_stok_kodu' => $r->StokKodu,
	    'field_stok' => $stokmiktari,
	    'qty_increments' => $result->paketadedi,
	]);
	$variation->save();
	$product = Product::create([
	    'type' => 'default',
	    'title' => $r->StokAdi,
	    'stores' => $sid, 
	    'variations' => $variation,
	    'status' => ($r->SiparisVerilebilir == "true" || $r->PasifStok == "true")?1:0,
	    'field_stok_tipi' => $r->StokTipi,
	    'field_stok_tipi_adi' => \Drupal::database()->select("taxonomy_term_data", "ttd")->fields("ttd", ["tid"])->condition("vid", "stok_tipi")->execute()->fetchCol(),
	    'field_liste_adi' => $grupadi,
	    'field_liste_no' => $result->grupkodu,
	]);
	if (!empty($r->StkGrupKodu1)) {
	  $entity_id_1 = \Drupal::database()->select("taxonomy_term__field_stok_grup_kodu", "ttfsgk")->fields("ttfsgk", ["entity_id"])->condition("field_stok_grup_kodu_value", $r->StkGrupKodu1)->execute()->fetchCol();
	  $entity_id_1 = (!empty($entity_id_1[0]))?$entity_id_1[0]:"";
	}
	if (!empty($r->StkGrupKodu2)) {
	  $entity_id_2 = \Drupal::database()->select("taxonomy_term__field_stok_grup_kodu", "ttfsgk")->fields("ttfsgk", ["entity_id"])->condition("field_stok_grup_kodu_value", $r->StkGrupKodu2)->execute()->fetchCol();
	  $entity_id_2 = (!empty($entity_id_2[0]))?$entity_id_2[0]:"";
	}
	if (!empty($r->StkGrupKodu3)) {
	  $entity_id_3 = \Drupal::database()->select("taxonomy_term__field_stok_grup_kodu", "ttfsgk")->fields("ttfsgk", ["entity_id"])->condition("field_stok_grup_kodu_value", $r->StkGrupKodu3)->execute()->fetchCol();
	  $entity_id_3 = (!empty($entity_id_3[0]))?$entity_id_3[0]:"";
	}
	$entity_ids = [$entity_id_1, $entity_id_2, $entity_id_3];
	foreach ($entity_ids as $entity_id) {
	  $product->field_stok_grup_kodu[] = [
	    "target_id" => $entity_id,
	  ];
	}
	$product->field_urun_foto[] = [
	  "target_id" => $image->id(),
	  "alt" => explode(".",explode("/",$imagename)[3])[0],
	  "title" => explode(".",explode("/",$imagename)[3])[0],
	];
	  $product->field_urun_logo[] = [
	    "target_id" => $logo->id(),
	    "alt" => explode(".",explode("/",$logoname)[4])[0],
	    "title" => explode(".",explode("/",$logoname)[4])[0],
	  ];
	    $product->save();
    \Drupal::database()->insert('urun')
	      ->fields([
		  'stokkodu' => $r->StokKodu,
		  'image_id' => $image->id(),
		  'logo_id' => $logo->id()
	      ])
	      ->execute();
      }
    }
  }
  public function getFormId() {
    return 'mitacan.services';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('web');
    $req = \Drupal::httpClient()->get($url);
    $res = $req->getBody();
    $res = json_decode($res);
    $res->ServisAdi = $item;
    \ksm($res);
  }
}

function web_services_options() {
  $arr = [
    "http://mitapp.mitacan.com/b2b/api/vStkLib/GetStkKodlist?UserCode=semih@bilgesan.com" => "Stok Listesi",
    "http://mitapp.mitacan.com/b2b/api/vSatLib/GetTumCariler?UserCode=B2BUSR01" => "Cariler",
    "http://mitapp.mitacan.com/b2b/api/vstklib/GetStkGrb1?UserCode=semih@bilgesan.com" => "Birinci stok grubu",
    "http://mitapp.mitacan.com/b2b/api/vstklib/GetStkGrb2?UserCode=semih@bilgesan.com&Grup1Code=10010" => "Ikinci stok grubu",
    "http://mitapp.mitacan.com/b2b/api/vstklib/GetStkGrb3?UserCode=semih@bilgesan.com&Grup1Code=10010&Grup2Code=20160" => "Ucuncu stok grubu",
  ];
  return $arr;
}
