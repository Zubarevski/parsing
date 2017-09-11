<?
require_once(dirname(__FILE__).'/phpQuery/phpQuery.php');

class Parsing
{
	private $siteUrl = null;

	private $userId = null;

	private $userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';

	private $premium = 0;

	public $iblockId = 5;

	public $imagePath = '/upload/catalog/parse_images/';

	public $translitOptions = array("replace_space"=>"-","replace_other"=>"-");

	public function __construct($siteUrl, $userId, $premium = 0)
	{
		$this->siteUrl = $siteUrl;
		$this->userId  = $userId;
		$this->premium = $premium;
	}

	/**
	 * Возврашает массив названий свойств для сопоставления
	 *@return array
	 */
	public function getNamePropirties()
	{
		return array(
			"Производитель" => "ATT_BREND",
			"Серия" => "ATT_SERIES",
			"Тип" => "ATT_TYPE",
			"Модель" => "ATT_MODEL",
			"Тип установки" => "ATT_TYPE_SETUP",
			"Высота, мм" => "ATT_HEIGHT",
			"Длина, мм" => "ATT_LENGHT",
			"Ширина, мм" => "ATT_WIDTH",
			"Мощность, кВт" => "ATT_POWER_KW",
			"MAX температура ГВС" => "ATT_TEMPERATURE_DHW",
			"MAX температура отопления, °C" => "ATT_TEMPERATURE_HEATING",
			"Погодозависимое управление" => "ATT_WEATHER_CONTROL",
			"Страна" => "ATT_COUNTRY",
			"Срок поставки" => "ATT_DELIVERY_TIME",
			"Распродажа" => "ATT_SALE",
			"Присоединительный размер" => "ATT_CONNECTING_SIZE",
			"Тип присоединения" => "ATT_ATTACH_TYPE",
			"MAX рабочая температура, °C" => "ATT_OPERATING_TEMPERATURE",
			"Материал" => "ATT_MATERIAL",
			"Вид соединения" => "ATT_CONNECTION_TYPE",
			"Диаметр трубы" => "ATT_PIPE_SIZE",
			"Глубина, мм" => "ATT_DEPTH",
			"Межосевое расстояние, мм" => "ATT_CENTER_DISTANTION",
			"Теплоотдача радиатора при  ∆T=70°C, Вт" => "ATT_RADIATOR_HEAT",
			"Тип панельного радиатора" => "ATT_PANEL_RADIATOR",
			"Монтажная длина, мм" => "ATT_MOUNTING_LENGTH",
			"MAX напор, м.вод.ст" => "ATT_PRESSURE_WATER",
			"MAX расход, м3/ч" => "ATT_AIRFLOW",
			"Диаметр подключения насоса" => "ATT_PUMP_CONNECTIONS",
			"Вид" => "ATT_VARIETY",
			"Управление" => "ATT_CONTROL",
			"Объём, л" => "ATT_WATER_VOLUME",
			"Время нагрева, мин" => "ATT_HEATING_TIME",
			"Диаметр" => "ATT_DIAMETER",
			"Длина, м" => "ATT_LENGHT_METRE",
			"MAX рабочее давление, бар" => "ATT_WORKING_BAR",
			"Внутренний диаметр, мм" => "ATT_INTERNAL_DIAMETER",
			"Армирование" => "ATT_REINFORCEMENT",
			"Размер выходов" => "ATT_SIZE_OUTPUTS",
			"Количество выходов" => "ATT_NUMBER_OUTPUTS",
			"Запорная арматура" => "ATT_STOP_VALVE",
			"Вентили ручные" => "ATT_VALVES_MANUAL",
			"Расходомеры" => "ATT_FLOW_METERS",
			"Назначение" => "ATT_FUNCTION",
			"MIN рабочая температура, ˚С" => "ATT_OPERATING_TEMPERATURE_MIN",
			"Предел измерения" => "ATT_LIMIT_MEASUREMENT",
			"Kvs, м3/час" => "ATT_KVS",
			"Число секций" => "ATT_NUMBER_SECTIONS",
			"Емкость, л" => "ATT_WATER_AMOUNT",
			"Предустановленное давление, бар" => "ATT_PRESET_BAR",
			"Толщина, мм" => "ATT_THICKNESS",
			"ТЭН" => "ATT_TEN",
			"Количество теплообменников" => "ATT_NUMBER_HEAT",
			"Мощность ТЭН, кВт" => "ATT_POWER_TEN",
			"Тип резьбы на выходах" => "ATT_CARVINGS_OUTPUTS",
			"Комплектующие" => "ATT_COMPONENTS",
			"Диапазон балансировки по расходу, м3/час" => "ATT_RANGE_BALANSING",
			"Возможность установки манометра" => "ATT_POSSIBILITY_MANOMETER",
			"Высота всасывания, м.вод.ст" => "ATT_SUCTION_LIFT",
			"Количество контуров" => "ATT_NUMBER_CIRCUITS",
			"Диаметр контура отопления" => "ATT_DIAMETER_CIRCUITS",
			"Диаметр контура ГВС" => "ATT_DIAMETER_GVS",
			"Материал вторичного теплообменника" => "ATT_MATERIAL_SECOND_HEAT",
			"Диаметр дымоотвода, мм" => "ATT_CHIMNEY",
			"Количество зон" => "ATT_NUMBER_ZONES",
			"Угол, °" => "ATT_CORNER",
			"Время переключения, сек" => "ATT_SWITCHING_TIME",
			"Расход при ∆ T 25ºC, л/мин" => "ATT_FLOW_25",
			"Расход при ∆ T 35ºC, л/мин" => "ATT_FLOW_35",
			"Емкость, А*ч" => "ATT_CAPACITY_AH",
		);
	}

	/**
	 * Получение заголовков и ссылкок на разделы
	 *@return array
	 */
	public function getFolderLink()
	{
		$url = $this->siteUrl;
		$arResult = array();

		if ($this->checkHeaders($url)) {
			$page = $this->getCurl($url);

			if ($page) {
				$document = phpQuery::newDocument($page);
				$content = $document->find('#tabs1_.tabs-box > ul > li');

				foreach ($content as $key => $level1) {
					$elem1 = pq($level1); //pq - аналог $ в jQuery

					$groupName = htmlspecialchars(trim($elem1->find('a')->eq(0)->text()));

					$arResult["GROUP_0"][$groupName]["NAME"] = $groupName;
					$arResult["GROUP_0"][$groupName]["LINK"] = trim($elem1->find('a')->eq(0)->attr('href'));

					foreach ($elem1->find('ul > li >a') as $key => $level2) {
						$elem2 = pq($level2); //pq - аналог $ в jQuery
						$subGroupName = htmlspecialchars(trim($elem2->text()));
						$arResult["GROUP_0"][$groupName]["GROUP_1"][$subGroupName]["NAME"] = $subGroupName;
						$arResult["GROUP_0"][$groupName]["GROUP_1"][$subGroupName]["LINK"] = trim($elem2->attr('href'));
					}
				}
			}
		}

		return $arResult;
	}

	/**
	 * Получает список пагинации
	 *@return array
	 */
	public function getPager($link)
	{
		$url = $this->siteUrl . $link;
		$patch = $link . '?PAGEN_1=';
		$arPager = array();

		if ($this->checkHeaders($url)) {
			$page = $this->getCurl($url);

			if ($page) {
				$document = phpQuery::newDocument($page);
				$pager = trim($document->find('.pager.pager-bottom .pager-last.here > a')->attr('href'));
				$num = str_replace($patch, '', $pager);

				if (empty($num) || $num < 1) {
					$num = 1;
				}

				for ($i = 1; $i <= $num; $i++) {
					$arPager[] = $patch . $i;
				}
			}
		}

		return $arPager;
	}

	/**
	 * Получает заголовки и ссылки на товар
	 *@return array
	 */
	public function getProductLink($link)
	{
		$url = $this->siteUrl . $link;
		$arResult = array();

		if ($this->checkHeaders($url)) {
			$page = $this->getCurl($url);

			if ($page) {
				$document = phpQuery::newDocument($page);
				$content = $document->find('.tovar-table .tovar-string .tovar-descript');

				foreach ($content as $key => $strProduct) {
					$elemPq = pq($strProduct); //pq - аналог $ в jQuery

					$name = htmlspecialchars(trim($elemPq->find('.tovar-col.tovar2 > a')->text()));
					$article = htmlspecialchars(trim($elemPq->find('.tovar-col.tovar4')->text()));

					$arResult[$article]["NAME"] = $name;
					$arResult[$article]["ARTICLE"] = $article;
					$arResult[$article]["LINK"] = trim($elemPq->find('.tovar-col.tovar2 > a')->attr('href'));
				}
			}
		}

		return $arResult;
	}

	/**
	 * Выключаем папки которых нет в обновлении
	 *@return bool
	 */
	public function disabledUnwantedFolders($folders, $level = 0, $parent = 0)
	{
		$result = false;
		if (!empty($folders)) {
			$arFilter = array(
				"IBLOCK_ID" => $this->iblockId
			);
			if ($level > 0) {
				$arFilter["=DEPTH_LEVEL"] = $level;
			}
			if ($parent > 0) {
				$arFilter["SECTION_ID"] = $parent;
			}

			foreach ($folders as $section) {
				$arFilter["!=NAME"][] = $section["NAME"];
			}

			$sections = CIBlockSection::GetList(array("ID"=>"ASC"), $arFilter, false, array("ID"), false);
			$updateSections = new CIBlockSection();

			$arFields = array(
				"ACTIVE" => "N",
				"IBLOCK_ID" => $this->iblockId,
				"MODIFIED_BY" => $this->userId,
			);

			while ($section = $sections->GetNext()) {
				$updateSections->Update($section["ID"], $arFields);
			}
			$result = true;
		}

		return $result;
	}

	/**
	 * Выключаем товары которых нет в обновлении
	 *@return bool
	 */
	public function disabledUnwantedProducts($products, $sectionId)
	{
		$arFilter = array(
			"IBLOCK_ID" => $this->iblockId,
			"SECTION_ID" => $sectionId
		);
		foreach ($products as $product) {
			$arFilter["!=PROPERTY_ARTICLE"][] = $product["ARTICLE"];
		}
		$arResult = CIBlockElement::GetList(array("ID"=>"ASC"), $arFilter, false, false, array("ID"));

		while ($arBx = $arResult->GetNext()) {
			$this->disabledProduct($arBx["ID"]);
		}

		return true;
	}

	/**
	 * получаем разделы которые есть в базе
	 *@return array
	 */
	public function getRealFolders($folders, $level = 0, $parent = 0)
	{
		$currentFolder = array();

		if (!empty($folders)) {
			$arFilter=array(
				"IBLOCK_ID" => $this->iblockId
			);
			if ($level > 0) {
				$arFilter["=DEPTH_LEVEL"] = $level;
			}
			if ($parent > 0) {
				$arFilter["SECTION_ID"] = $parent;
			}
			foreach ($folders as $section) {
				$arFilter["NAME"][] = $section["NAME"];
			}

			$sections = CIBlockSection::GetList(array("ID"=>"ASC"), $arFilter, false, array("ID","NAME","CODE","SORT"), false);

			while ($section = $sections->GetNext()) {
				$currentFolder[$section["NAME"]] = $section;
			}
		}

		return $currentFolder;
	}

	/**
	 * получаем товары которые есть в базе
	 *@return array
	 */
	public function getRealProducts($products, $sectionId)
	{
		$currentProduct = array();

		if (!empty($products)) {
			$arFilter = array(
				"IBLOCK_ID" => $this->iblockId,
				"SECTION_ID" => $sectionId
			);
			foreach ($products as $section) {
				$arFilter["PROPERTY_ARTICLE"][] = $section["ARTICLE"];
			}
			$result = CIBlockElement::GetList(array("ID"=>"ASC"), $arFilter, false, false, array("ID","NAME","CODE","SORT","PROPERTY_ARTICLE"));
			while ($arBx = $result->GetNext()) {
				$currentProduct[$arBx["~PROPERTY_ARTICLE_VALUE"]] = $arBx;
			}
		}

		return $currentProduct;
	}

	/**
	 * обновление раздела
	 *@return bool
	 */
	public function updateFolder($idSection, $parent = 0, $sections, $sort)
	{
		$updateSections = new CIBlockSection();

		$arFields = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $this->iblockId,
			"MODIFIED_BY"=> $this->userId,
			"NAME" => $sections["NAME"],
			"CODE" => Cutil::translit(htmlspecialchars_decode($sections["NAME"]),"ru",$this->translitOptions),
			"SORT" => $sort
		);
		if ($parent > 0) {
			$arFields["IBLOCK_SECTION_ID"] = $parent;
		}

		return $updateSections->Update($idSection, $arFields);
	}

	/**
	 * обновление товара
	 *@return bool
	 */
	public function updateProduct($productId, $parent = 0, $product, $sort)
	{
		$updateProduct = new CIBlockElement();

		$property = array(
			"ARTICLE" => $product["ARTICLE"],
			"PARSE_LINK" => $product["LINK"]
		);
		$arFields = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $this->iblockId,
			"MODIFIED_BY"=> $this->userId,
			"NAME" => $product["NAME"],
			"CODE" => Cutil::translit(htmlspecialchars_decode($product["NAME"]), "ru", $this->translitOptions),
			"SORT" => $sort,
		);
		if ($parent > 0) {
			$arFields["IBLOCK_SECTION_ID"] = $parent;
		}
		if ($updateProduct->Update($productId, $arFields)) {
			CCatalogProduct::Update($productId, array());
			$this->setProp($productId,$property);

			return true;
		}else{
			return false;
		}
	}

	/**
	 * добавление раздела
	 *@return string
	 */
	public function insertFolder($parent = 0, $sections, $sort)
	{
		$insertSections = new CIBlockSection();

		$arFields = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $this->iblockId,
			"MODIFIED_BY"=> $this->userId,
			"NAME" => $sections["NAME"],
			"CODE" => Cutil::translit(htmlspecialchars_decode($sections["NAME"]),"ru",$this->translitOptions),
			"SORT" => $sort
		);
		if ($parent > 0) {
			$arFields["IBLOCK_SECTION_ID"] = $parent;
		}

		return $insertSections->Add($arFields);
	}

	/**
	 * добавление товара
	 *@return string
	 */
	public function insertProduct($parent = 0, $product, $sort)
	{
		$productId = false;
		$insertSections = new CIBlockElement();

		$property = array(
			"ARTICLE" => $product["ARTICLE"],
			"PARSE_LINK" => $product["LINK"]
		);
		$arFields = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $this->iblockId,
			"MODIFIED_BY"=> $this->userId,
			"NAME" => $product["NAME"],
			"CODE" => Cutil::translit(htmlspecialchars_decode($product["NAME"]), "ru", $this->translitOptions),
			"SORT" => $sort,
		);
		if ($parent > 0) {
			$arFields["IBLOCK_SECTION_ID"] = $parent;
		}
		$productId = $insertSections->Add($arFields);
		if ($productId) {
			CCatalogProduct::Add(array("ID" => $productId));
			$this->setProp($productId,$property);
			$this->disabledProduct($productId);
		}

		return $productId;
	}

	/**
	 * получение товаров для обновления
	 *@return array
	 */
	public function getProductsForUpdate()
	{
		$arProduct = array();
		$arFilter=array(
			"IBLOCK_ID" => $this->iblockId
		);
		$arResult = CIBlockElement::GetList(array("ID"=>"ASC"), $arFilter, false, false, array("ID","PROPERTY_PARSE_LINK"));

		while ($product = $arResult->GetNext()) {
			$arProduct[$product["ID"]]=$product["~PROPERTY_PARSE_LINK_VALUE"];
		}
		return $arProduct;
	}

	/**
	 * получение картинок для обновления
	 *@return array
	 */
	public function getProductImagesForUpdate()
	{
		$arProduct = array();
		//фильтр для выборки существующих разделов
		$arFilter=array(
			"IBLOCK_ID"=>$this->iblockId,
			"ACTIVE"=>"Y",
			"PROPERTY_PARSE_IMAGES"=>array("Y"),
		);

		$arResult = CIBlockElement::GetList(array("ID"=>"ASC"), $arFilter, false, false, array("ID","PROPERTY_PARSE_LINK"));

		while ($product = $arResult->GetNext()) {
			$arProduct[$product["ID"]]=$product["~PROPERTY_PARSE_LINK_VALUE"];
		}
		return $arProduct;
	}

	/**
	 * получение цен для обновления
	 *@return array
	 */
	public function getPricesForUpdate($premium)
	{
		$arPrices = array();

		$arFilter=array(
			"IBLOCK_ID"=>$this->iblockId,
			"ACTIVE"=>"Y",
		);

		$arResult = CIBlockElement::GetList(array("ID"=>"ASC"), $arFilter, false, false, array("ID","PROPERTY_OLD_PRICE"));

		while ($price = $arResult->GetNext()) {
			$oldPrice=0;
			if (!empty($price["~PROPERTY_OLD_PRICE_VALUE"])) {
				$oldPrice = $price["~PROPERTY_OLD_PRICE_VALUE"];
			}
			$arPrices[$price["ID"]]["OLD_PRICE"] = $oldPrice;
			$arPrices[$price["ID"]]["PRICE"] = $oldPrice;
			
			if ($premium > 0) {
				$arPrices[$price["ID"]]["PRICE"] = ceil((double)$oldPrice * ($premium / 100 + 1));
			}
		}

		return $arPrices;
	}

	/**
	 * Проверяет есть ли ответ от сервера с кодом 200
	 *@return bool
	 */
	public function checkHeaders($url)
	{
		$headers = @get_headers($url);

		return strpos($headers[0], '200');
	}

	/**
	 * Выключает товар
	 *@return string
	 */
	public function disabledProduct($id)
	{
		$result = '';
		$props = array();

		if (!empty($id)) {
			$props["PARSE_IMAGES"]["n0"]["VALUE"]="Y";
			$eld = new CIBlockElement();
			CIBlockElement::SetPropertyValuesEx($id, $this->iblockId, $props);

			$arFields = array(
				"ACTIVE" => "N",
				"IBLOCK_ID" => $this->iblockId,
				"MODIFIED_BY"=> $this->userId
			);

			if ($eld->Update($id, $arFields)) {
				$result = $result.": успешно обновлено.";
			} else {
				$result = $result.": ".$eld->LAST_ERROR;
			}
		}

		return $result;
	}

	/**
	 * добавляет/обновляет свойства по id
	 *@return bool
	 */
	public function setProp($id, $prop)
	{
		$result = false;
		if (!empty($id) && !empty($prop)) {
			CIBlockElement::SetPropertyValuesEx($id, $this->iblockId, $prop);
			$result = true;
		}

		return $result;
	}

	/**
	 * добавляет/обновляет цену по id
	 *@return bool
	 */
	public function updatePrice($idElem, $currentPrice)
	{
		$apBase = CIBlockPriceTools::GetCatalogPrices($this->iblockId, array('BASE'));
		$priceTypeId = $apBase["BASE"]["ID"];

		$arFields = Array(
			"PRODUCT_ID" => $idElem,
			"CATALOG_GROUP_ID" => $priceTypeId,
			"PRICE" => $currentPrice,
			"CURRENCY" => "RUB",
			"QUANTITY_FROM" => false,
			"QUANTITY_TO" => false
		);

		$prices = CPrice::GetList(array(),array("PRODUCT_ID" => $idElem,"CATALOG_GROUP_ID" => $priceTypeId));

		if ($price = $prices->Fetch()) {
			$result = CPrice::Update($price["ID"], $arFields);
		} else {
			$result = CPrice::Add($arFields);
		}

		return $result;
	}

	/**
	 * добавляет/обновляет цену по id
	 *@return bool
	 */
	public function updateProductPrice($idElem, $currentPrice)
	{
		$apBase = CIBlockPriceTools::GetCatalogPrices($this->iblockId, array('BASE'));
		$priceTypeId = $apBase["BASE"]["ID"];

		$arFields = Array(
			"PRODUCT_ID" => $idElem,
			"CATALOG_GROUP_ID" => $priceTypeId,
			"PRICE" => $currentPrice,
			"CURRENCY" => "RUB",
			"QUANTITY_FROM" => false,
			"QUANTITY_TO" => false
		);

		$prices = CPrice::GetList(array(),array("PRODUCT_ID" => $idElem,"CATALOG_GROUP_ID" => $priceTypeId));

		if ($price = $prices->Fetch()) {
			$result = CPrice::Update($price["ID"], $arFields);
		} else {
			$result = CPrice::Add($arFields);
		}

		return $result;
	}

	/**
	 * Получает свойства товара по URL
	 *@return array
	 */
	public function getProductProps($link)
	{
		$url = $this->siteUrl . $link;
		$arProp = array();
		$arProp['ERROR'] = false;

		if ($this->checkHeaders($url)) {
			$page = $this->getCurl($url);

			if ($page) {
				$document = phpQuery::newDocument($page);
				$bsBlock = $document->find('.item-middle .popup-tobasket');
				$character = $document->find('.content-tabs .tabs-box.character .col');
				$description = $document->find('.content-tabs .tabs-box.article');
				$images = $document->find('.item-middle .item-left a.item-img');
				//цена и наличие
				foreach ($bsBlock as $key => $item) {
					$priceElem = pq($item); //pq - аналог $ в jQuery

					$price = str_replace(",", ".", $priceElem->find(".popup-in .price1")->text());
					$price = htmlspecialchars(trim(str_replace(" ", "", $price)));
					if (empty($price)) {
						$price = 0;
					}

					$nalich = htmlspecialchars(trim($priceElem->find(".popup-in .popup-block")->eq(2)->text()));
					$pos = strpos($nalich, 'В наличии');

					$arProp["NALICH"]["n0"]["VALUE"] = $pos === false ? "N" : "Y";
					$arProp["OLD_PRICE"] = number_format((double)$price, 2, '.', '');
					if ($this->premium > 0) {
						$arProp["PRICE"] = ceil((double)$price * ($this->premium / 100 + 1));
					} else {
						$arProp["PRICE"] = $arProp["OLD_PRICE"];
					}
				}

				$properties = $this->getNamePropirties();

				//характеристики
				foreach ($character as $key => $item) {
					$charElem = pq($item); //pq - аналог $ в jQuery

					foreach ($charElem->find("table tr") as $propName) {
						$nameElem = pq($propName); //pq - аналог $ в jQuery

						$pr_name = htmlspecialchars(trim(str_replace(":", "", $nameElem->find('td:first li')->text())));
						$pr_value = htmlspecialchars(trim($nameElem->find('td:last')->text()));

						//если в есть ключь создаем свойство
						if (array_key_exists($pr_name, $properties)) {
							$arProp["PROPERTY"][$properties[$pr_name]] = $pr_value;
						}
					}
				}
				//описание
				foreach ($description as $key => $item) {
					$descElem = pq($item); //pq - аналог $ в jQuery

					$desc = htmlspecialchars(trim($descElem->text()));
					if (!empty($desc)) {
						$arProp["DESCRIPTION"] = $desc;
					}
				}
				//картинки
				foreach ($images as $key => $item) {
					$imagElem = pq($item); //pq - аналог $ в jQuery

					$urlImages = htmlspecialchars(trim($imagElem->attr('href')));
					$file = basename($urlImages);
					$arProp["IMAGES"][] = $file;

				}
			}
		} else {
			$arProp['ERROR'] = true;
		}

		return $arProp;
	}


	/**
	 * проверяет необходимость обновлять картинки
	 *@return bool
	 */
	public function checkImages($idElem, $oldImages, $newImages)
	{
		$noEmpty = false;
		$countsImages = false;

		if (!empty($oldImages)) {
			if (array_key_exists($idElem, $oldImages) && array_key_exists("IMAGES", $newImages)) {
				if (count($oldImages[$idElem]) == count($oldImages[$newImages["IMAGES"]])) {
					$countsImages = true;
				}
			}

			$noEmpty = true; 
		}

		if (!$noEmpty || !$countsImages){
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Получает ссылки на новые картинки
	 *@return array
	 */
	public function getImagesLink($link)
	{
		$url = $this->siteUrl . $link;
		$arResult = array();
		$arResult['ERROR'] = false;
		$arResult['IMAGES'] = array();

		if ($this->checkHeaders($url)) {
			$page = $this->getCurl($url);

			if ($page) {
				$document = phpQuery::newDocument($page);
				$images = $document->find('.item-middle .item-left a.item-img');
				//картинки
				foreach ($images as $key => $item) {
					$imagElem = pq($item); //pq - аналог $ в jQuery

					$arResult["IMAGES"][] = htmlspecialchars(trim($imagElem->attr('href')));
				}
			}
		} else {
			$arResult['ERROR'] = true;
		}

		return $arResult;
	}

	/**
	 * закачивает картинки по URL
	 *@return bool
	 */
	public function loadImages($name, $link, $serverName)
	{
		$url = $this->siteUrl . $link;
		$pathFile = $serverName.$this->imagePath . $name;
		
		if ($this->checkHeaders($url)) {
			$output = $this->getCurl($url);

			$fh = fopen($pathFile, 'w');
			fwrite($fh, $output);
			fclose($fh);
		}

		return true;
	}

	/**
	 * удаляем картинки по id продукта
	 *@return bool
	 */
	public function removeImages($id, $detailPicture)
	{
		$photos = array();
		$propDel = array();

		if (!empty($detailPicture)) {
			$photos[] = $detailPicture;
		}

		$dopPhotos = CIBlockElement::GetProperty($this->iblockId, $id, array("sort" => "asc"), array("CODE" => "MORE_PHOTO"));
		while ($obj = $dopPhotos->GetNext()){
			
			if (!empty($obj['VALUE'])) {
				$photos[] = $obj['VALUE'];
				$propDel[$obj["PROPERTY_VALUE_ID"]]["VALUE"] = array(
					"MODULE_ID" => "iblock",
					"del" => "Y"
				);
			}
		}

		if (!empty($photos)) {
			foreach ($photos as $key => $value) {
				CFile::Delete($value);
			}
		}
		if (!empty($propDel)) {
			CIBlockElement::SetPropertyValueCode($id, "MORE_PHOTO", $propDel);
		}

		return true;
	}

	/**
	 * Обновление картинок
	 *@return array
	 */
	public function updateImages($id, $detailPicture, $dopPicture)
	{
		$message = array();
		$propertyImages = array();
		$arFields = array(
			"IBLOCK_ID" => $this->iblockId,
			"MODIFIED_BY"=> $this->userId,
			"DETAIL_PICTURE"=> false
		);
		if ($detailPicture) {
			$arFields["DETAIL_PICTURE"] = CFile::MakeFileArray($detailPicture);
			$message[] = "главная ок";
		}

		$el = new CIBlockElement();

		if ($el->Update($id, $arFields, false, true, true)) {
			if ($dopPicture) {
				if (CIBlockElement::SetPropertyValueCode($id, "MORE_PHOTO", $dopPicture)) {
					$message[] = "дополнительная ок";
				} else {
					$message[] = "Ошибка обновления дополнительной";
				}
			}

			$propertyImages["PARSE_IMAGES"]["n0"]["VALUE"] = "N";
			$this->setProp($id, $propertyImages);
		} else {
			$message[] = $el->LAST_ERROR;
		}

		return $message;
	}

	/**
	 * получает страницу по url
	 *@return string
	 */
	public function getCurl($link)
	{
		$ch = curl_init($link);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPTuserAgent, $this->userAgent);
		$output = curl_exec($ch);
		curl_close($ch);

		return $output;
	}
}