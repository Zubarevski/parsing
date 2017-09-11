<?
header('Content-Type: application/json');

define('ADMIN_MODULE_NAME', 'alma_parsing');

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/alma_parsing/lib/parsing.php';


if (!$USER->CanDoOperation('edit_php'))
	$APPLICATION->AuthForm('Доступ запрещен');

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';

$APPLICATION->RestartBuffer();//выключаем шапку

if(isset($_REQUEST['type_ajax'])){
	/**
	 * Обновление структуры разделов и товаров
	 * без добавления свойств товарам
	 */
	if ($_REQUEST['type_ajax'] == 'get_tovar' && isset($_REQUEST['id'])  && isset($_REQUEST['values'])) {
		$message = array();
		$arItems = array();
		$arPager = array();
		$arProduct = array();
		$sectionId = abs((int)$_REQUEST['id']);
		$link = trim($_REQUEST['values']);

		$parSite = new Parsing("https://www.duim24.ru", $USER->GetID());
		$arPager = $parSite->getPager($link);

		if (!empty($arPager)) {
			for($i = 0; $i < count($arPager); ++$i) {
				$arItems[] = $parSite->getProductLink($arPager[$i]);
			}
		}
		if (!empty($arItems)) {
			for($i = 0; $i < count($arItems); ++$i) {
				foreach ($arItems[$i] as $key => $tovar) {
					$arProduct[$key] = $tovar;
				}
			}
			if (!empty($arProduct)) {
				//Выключаем товары которых нет в обновлении
				$parSite->disabledUnwantedProducts($arProduct, $sectionId);
				// получаем товары которые есть в базе
				$arElement = $parSite->getRealProducts($arProduct, $sectionId);

				$el = false;
				$el = new CIBlockElement();

				$sort = 0;
				foreach ($arProduct as $key => $product) {
					$sort++;
					$productId = false;

					if( $arElement[$product["ARTICLE"]] ){
						$productId = $arElement[$product["ARTICLE"]]["ID"];
					}
					if ($productId) {
						if (!$parSite->updateProduct($productId, $sectionId, $product, $sort)) {
							$message["ERROR"][] = "Ошибка при обновлении";
						}
					} else {
						$productId = $parSite->insertProduct($sectionId, $product, $sort);
					}
					
					if (!$productId) {
						$message["ERROR"][] = "Ошибка при добавлении";
					}
				}
			}
		}
		$message["SECTION_ID"] = $sectionId;

		echo json_encode($message);
	}

	//Обновление свойств и цен для товаров
	if ($_REQUEST['type_ajax'] == 'update_tovar' && isset($_REQUEST['id'])) {
		$productId = abs((int)$_REQUEST['id']);
		$message = array();
		$arProp = array();
		$arPhotosId = array();

		if (isset($_REQUEST['premium'])) {
			$premium = $_REQUEST['premium'];
			$premium = str_replace("%", "", $premium);
			$premium = abs((float)trim(str_replace(",", ".", $premium)));
		} else {
			$premium = 0;
		}
		
		$parSite = new Parsing("https://www.duim24.ru", $USER->GetID(), $premium);
		
		$arFilter = array(
			"IBLOCK_ID" => $parSite->iblockId,
			"ID" => $productId,
		);
		$arResult = CIBlockElement::GetList(array("ID"=>"ASC"), $arFilter, false, false, array("ID","NAME","CODE","SORT","PROPERTY_PARSE_LINK","DETAIL_PICTURE"));

		while ($arBx = $arResult->GetNext()) {
			$arProp[$arBx["ID"]] = $parSite->getProductProps($arBx["~PROPERTY_PARSE_LINK_VALUE"]);

			//получаем старые картинки
			if (!empty($arBx["DETAIL_PICTURE"])) {
				$arPhotosId[$arBx["ID"]][] = $arBx["DETAIL_PICTURE"];
			}
			$more_photo = CIBlockElement::GetProperty($parSite->iblockId, $arBx["ID"], array("sort" => "asc"), array("CODE" => "MORE_PHOTO"));
			while ($obj = $more_photo->GetNext()){
				if (!empty($obj['VALUE'])) {
					$arPhotosId[$arBx["ID"]][] = $obj['VALUE'];
				}
			}
		}

		if (!empty($arProp)) {
			foreach ($arProp as $idElem => $value) {
				if (!$value["ERROR"]) {
					$arFields = array(
						"ACTIVE" => "Y",
						"IBLOCK_ID" => $parSite->iblockId,
						"MODIFIED_BY"=> $USER->GetID(),
					);
					//описание
					if (!empty($value["DESCRIPTION"])) {
						$arFields["DETAIL_TEXT"] = $value["DESCRIPTION"];
					}
					$el = new CIBlockElement();
					if ($el->Update($idElem, $arFields, false, true, true)) {
						$property = array();
						$property["NALICH"] = $value["NALICH"];
						$property["OLD_PRICE"] = $value["OLD_PRICE"];

						if (isset($value["PROPERTY"])) {
							foreach ($value["PROPERTY"] as $nProp => $vProp) {
								$property[$nProp] = $vProp;
							}
						}
						if ($parSite->checkImages($idElem,$arPhotosId,$value)) {
							$property["PARSE_IMAGES"]["n0"]["VALUE"] = "N";
						} else {
							$property["PARSE_IMAGES"]["n0"]["VALUE"] = "Y";
						}
						if ($parSite->setProp($idElem,$property)){
							$message["PROP"] = "OK";
						} else {
							$message["PROP"] = "Ошибка";
						}
						//обновляем цену
						if (!empty($value["PRICE"])) {
							if ($parSite->updatePrice($idElem, $value["PRICE"])) {
								$message["PRICE"] = "OK";
								$message['PREM'] = $premium;
								$message["ACTIVE"] = 1;
							} else {
								$message["PRICE"] = "Ошибка";
								//ошибка цены значит выключаем товар
								$parSite->disabledProduct($idElem);
								$message["ACTIVE"] = 0;
							}
						}
					} else {
						//сообшение об ошибке
						$message["ERROR"] ="Ошибка обновления товара";
					}

				} else {
					//страници товара недоступна значит выключаем
					$parSite->disabledProduct($idElem);
				}
			}
		}
		$message["PRODUCT_ID"] = $productId;

		echo json_encode($message);
	}
	//Загрузка картинок
	if ($_REQUEST['type_ajax'] == 'images' && isset($_REQUEST['id'])) {
		$productId = abs((int)$_REQUEST['id']);
		$arImages = array();
		$message = array();
		$message["COUNT"] = 0;

		$parSite = new Parsing("https://www.duim24.ru", $USER->GetID());

		$arFilter = array(
			"IBLOCK_ID" => $parSite->iblockId,
			"ID" => $productId
		);
		$arResult = CIBlockElement::GetList(array("ID"=>"ASC"), $arFilter, false, false, array("ID","NAME","CODE","SORT","PROPERTY_PARSE_LINK","PROPERTY_ARTICLE","DETAIL_PICTURE"));

		while ($arBx = $arResult->GetNext()) {
			$arImages = $parSite->getImagesLink($arBx["~PROPERTY_PARSE_LINK_VALUE"]);

			if (!$arImages['ERROR']) {//если товар доступен по ссылке
				//закачиваем новые картинки
				if (!empty($arImages["IMAGES"])) {
					for ($i=0; $i < count($arImages["IMAGES"]); $i++) {
						if ($i==0) {
							$nameImage = $arBx["ID"]."(".$i.")0.jpg";
						} else {
							$nameImage = $arBx["ID"]."(".$i.").jpg";
						}
						if (!empty($nameImage) && !empty($arImages["IMAGES"][$i])) {
							$parSite->loadImages($nameImage, $arImages["IMAGES"][$i], $_SERVER['DOCUMENT_ROOT']);
							$message["COUNT"]++;
						}
					}
				}
			} else {
				$parSite->disabledProduct($productId);
				$message["ERROR"] = "Товар недоступен по URL";
			}
		}
		$message["PRODUCT_ID"] = $productId;

		echo json_encode($message);
	}

	//обновление картинок
	if ($_REQUEST['type_ajax'] == 'images_bitrix' && isset($_REQUEST['id'])) {
		$productId = abs((int)$_REQUEST['id']);
		$message = array();
		$detailPicture=false;
		$dopPicture=false;

		$parSite = new Parsing("https://www.duim24.ru", $USER->GetID());
		
		$arFilter = array(
			"IBLOCK_ID"=>$parSite->iblockId,
			"ID"=>$productId,
		);

		$arResult = CIBlockElement::GetList(array("ID"=>"ASC"), $arFilter, false, false, array("ID","NAME","CODE","SORT","PROPERTY_PARSE_LINK","DETAIL_PICTURE"));

		while ($arBx = $arResult->GetNext()) {
			$maxI = 10;
			$arPref = array("jpg","jpeg","png");
			$fileName = $arBx["ID"];

			$tempDopPicture = array();
			$path = $_SERVER['DOCUMENT_ROOT'] . $parSite->imagePath;

			for ($i=0; $i < $maxI; $i++) { 
				for ($j=0; $j < count($arPref); $j++) {
					if (file_exists($path . $fileName . "(".$i.")0." . $arPref[$j])) {
						$detailPicture=$path . $fileName. "(".$i.")0." . $arPref[$j];
					}
					if (file_exists($path . $fileName . "(".$i.")." . $arPref[$j])) {
						$tempDopPicture[] = $path . $fileName . "(".$i.")." . $arPref[$j];
					}
				}
			}
			if (!empty($tempDopPicture)) {
				for ($i=0; $i < count($tempDopPicture); $i++) { 
					$dopPicture[]["VALUE"] = CFile::MakeFileArray($tempDopPicture[$i]);
				}
			}
			if ($detailPicture || $dopPicture) {
				$parSite->removeImages($productId, $arBx["DETAIL_PICTURE"]);
				$message["MESSAGE"] = $parSite->updateImages($productId, $detailPicture, $dopPicture);
			}
		}
		$message["PRODUCT_ID"] = $productId;

		echo json_encode($message);
	}

	//обновление цен
	if ($_REQUEST['type_ajax'] == 'price_bitrix' && isset($_REQUEST['id'])  && isset($_REQUEST['values'])) {
		$message = array();
		$productId = abs((int)$_REQUEST['id']);
		$arPrice = $_REQUEST['values'];

		if (isset($arPrice['PRICE']) && $arPrice['PRICE'] > 0) {
			$parSite = new Parsing("https://www.duim24.ru", $USER->GetID());

			if ($parSite->updateProductPrice($productId, $arPrice['PRICE'])) {
				$message["MESS"] = 'успешно';
			} else {
				$message["MESS"] = 'ошибка';
			}
		} else {
			$message["MESS"] = 'ошибка нет цены';
		}
		$message["PRICE"] = $arPrice['PRICE'];
		$message["PRODUCT_ID"] = $productId;

		echo json_encode($message);
	}
}