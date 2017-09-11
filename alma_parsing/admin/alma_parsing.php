<?php
define('ADMIN_MODULE_NAME', 'alma_parsing');

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/alma_parsing/lib/parsing.php';

CJSCore::Init(array("jquery"));

if (!$USER->CanDoOperation('edit_php')) {
	$APPLICATION->AuthForm('Доступ запрещен');
}

$APPLICATION->SetTitle("Обновление каталога");

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';


if (!$_REQUEST['parsing']) {?>
	<form action='' method='post'>
		<div class="premium-box" style="display:none">
			Укажите наценку в %, например 0.6:<br><input id="premium" disabled="disabled" type="text" name="premium" value="0" style="margin: 4px 0 10px;"><br>
		</div>
		<select name="parsing" onchange="operation(this.value)">
			<option value="" selected>--Выберите операцию--</option>
			<option value="struct">Обновление структуры</option>
			<option value="tovar">Обновление товаров</option>
			<option value="images">Обновление картинок</option>
			<option value="price">Обновление цен</option>
		</select>
		<button class="adm-btn" type="sybmit">выполнить</button>
	</form>
<?
} else {
	/**
	 * Обновление структуры разделов и товаров
	 * без добавления свойств товарам
	 */
	if ($_REQUEST['parsing'] == "struct") {
		$typeAjax = "get_tovar";
		$arProduct = array();

		$parSite = new Parsing("https://www.duim24.ru", $USER->GetID());
		$arItems = $parSite->getFolderLink();

		if (!empty($arItems)) {
			$arUpdateGroup = array();
			$arSection = array();
			$level = 1;
			$parent = 0;

			//сортируем по уникальному полю
			foreach ($arItems["GROUP_0"] as $key => $value) {//сортируем по уникальному полю
				$arUpdateGroup[$key]["NAME"] = $value["NAME"];
			}

			// выключаем разделы которых нет в обновлении
			$parSite->disabledUnwantedFolders($arUpdateGroup, $level, $parent);

			// получаем разделы которые есть в базе
			$arSection = $parSite->getRealFolders($arUpdateGroup, $level, $parent);
			
			$sort = 0;
			foreach ($arUpdateGroup as $groupKey => $group) {
				$sort++;
				$idSection = false;

				if ( $arSection[$group["NAME"]] ){
					$idSection = $arSection[$group["NAME"]]["ID"];
				}

				if ($idSection) {
					$parSite->updateFolder($idSection, $parent, $group, $sort);
				} else {
					$idSection = $parSite->insertFolder($parent, $group, $sort);
				}

				if ($idSection) {// если у нас есть ID родительского раздела
					$arUpdateGroupLvl2 = array();

					foreach ($arItems["GROUP_0"][$groupKey]["GROUP_1"] as $key => $value) {
						$arUpdateGroupLvl2[$key] = $value;
					}

					if (!empty($arUpdateGroupLvl2)) {
						$level = 2;
						$parent = $idSection;

						// выключаем разделы которых нет в обновлении
						$parSite->disabledUnwantedFolders($arUpdateGroupLvl2, $level, $parent);

						// получаем разделы которые есть в базе и есть в обновлении
						$arSectionLvl2 = $parSite->getRealFolders($arUpdateGroupLvl2, $level, $parent);

						$sortLvl2 = 0;//задаем сортировку для раздела
						foreach ($arUpdateGroupLvl2 as $groupKeyLvl2 => $grouplvl2) {
							$sortLvl2++;
							$idSectionLvl2 = false;

							if ( $arSectionLvl2[$grouplvl2["NAME"]] ) {
								$idSectionLvl2 = $arSectionLvl2[$grouplvl2["NAME"]]["ID"];
							}

							if ($idSectionLvl2) {
								$parSite->updateFolder($idSectionLvl2, $parent, $grouplvl2, $sortLvl2);
							} else {
								$idSectionLvl2 = $parSite->insertFolder($parent, $grouplvl2, $sortLvl2);
							}
							//Собираем массив для ajax
							if ($idSectionLvl2 && !empty($grouplvl2["LINK"])) {
								$arProduct[$idSectionLvl2] = $grouplvl2["LINK"];
							}
						}
					}
				}
			}
		}

		if (!empty($arProduct)) {
?>
			<script type="text/javascript">
				var rows = <?=json_encode($arProduct);?>;
				var premium = '';
				var typeAjax = <?=$typeAjax;?>;

				$(document).ready(function(){
					var wait = BX.showWait('loading');
					var deferreds = [];
					var i=5; // шаг выполнения операции

					//создаем очередь загрузки
					var idx=0;
					$.each(rows,function(id,value){
						var d = new $.Deferred();
						window.setTimeout(function() { parseRow(typeAjax, id, value, premium, d) }, i*idx);
						deferreds.push(d);
						idx++;
					});

					$.when.apply($, deferreds).done(function () {
						console.log("Выполнено");
						BX.closeWait('loading',wait); // отключаем loading
					});
				});
			</script>
	<?
		}
	}

	/**
	 * Обновление свойств и цен для товаров
	 */
	if ($_REQUEST['parsing']=="tovar") {
		$typeAjax = "update_tovar";
        $arProduct = array();

		if (isset($_REQUEST['premium'])) {
			$premium = $_REQUEST['premium'];
			$premium = str_replace("%", "", $premium);
			$premium = abs((float)trim(str_replace(",", ".", $premium)));
		} else {
			$premium = 0;
		}

		$parSite = new Parsing("https://www.duim24.ru", $USER->GetID());
		$arProduct = $parSite->getProductsForUpdate();

		if (!empty($arProduct)) {
?>
			<script type="text/javascript">
				var rows = <?=json_encode($arProduct);?>;
				var premium = <?=$premium;?>;
				var typeAjax = <?=$typeAjax;?>;

				$(document).ready(function(){
					var wait = BX.showWait('loading');
					var deferreds = [];
					var i=5; // шаг выполнения операции

					//создаем очередь загрузки
					var idx=0;
					$.each(rows,function(id,value){
						var d = new $.Deferred();
						window.setTimeout(function() { parseRow(typeAjax, id, value, premium, d) }, i*idx);
						deferreds.push(d);
						idx++;
					});
					$.when.apply($, deferreds).done(function () {
						BX.closeWait('loading', wait); // отключаем loading
					});
				});
			</script>
	<?
		}
	}

	/**
	 * Обновление картинок для товаров
	 */
	if ($_REQUEST['parsing']=="images") {
		$typeAjax = "images";
        $arProduct = array();

		$parSite = new Parsing("https://www.duim24.ru", $USER->GetID());
		$arProduct = $parSite->getProductImagesForUpdate();

		if (!empty($arProduct)) {
	?>
			<script type="text/javascript">
				var rows = <?=json_encode($arProduct);?>;
				var premium = '';
				var typeAjax = <?=$typeAjax;?>;

				$(document).ready(function(){
					var wait = BX.showWait('loading');
					var deferreds = [];
					var i=5; // шаг выполнения операции

					//создаем очередь загрузки
					var idx=0;
					$.each(rows,function(id,value){
						var d = new $.Deferred();
						window.setTimeout(function() { parseRow(typeAjax,id,value,premium, d) }, i*idx);
						deferreds.push(d);
						idx++;
					});

					$.when.apply($, deferreds).done(function () {
						var typeAjaxForImages = 'images_bitrix';
						var deferreds2 = [];
						var i2=5; // шаг выполнения операции

						//создаем очередь загрузки повторно
						var idx2=0;
						$.each(rows,function(id,value){
							var j = new $.Deferred();
							window.setTimeout(function() { update_images(typeAjaxForImages,id,value,j) }, i2*idx2);
							deferreds2.push(j);
							idx2++;
						});

						$.when.apply($, deferreds2).done(function () {
							BX.closeWait('loading',wait); // отключаем loading
						});
					});
				});
			</script>
<?
		}
	}

	/**
	 * Обновление только цен для товаров
	 */
	if ($_REQUEST['parsing']=="price" && isset($_REQUEST['premium'])) {
		$typeAjax = "price_bitrix";
		if (isset($_REQUEST['premium'])) {
			$premium = $_REQUEST['premium'];
			$premium = str_replace("%", "", $premium);
			$premium = abs((float)trim(str_replace(",", ".", $premium)));
		} else {
			$premium=0;
		}

		$arPrices = array();

		$arPrices = $parSite->getPricesForUpdate($premium);

		if (!empty($arPrices)) {
?>
			<script type="text/javascript">
				var rows = <?=json_encode($arPrices);?>;
				var premium = <?=$premium;?>;
				var typeAjax = <?=$typeAjax;?>;

				$(document).ready(function(){
					var wait = BX.showWait('loading'); // запускаем loading
					var deferreds = [];
					var i=1; // шаг выполнения операции

					//создаем очередь загрузки
					var idx=0;
					$.each(rows,function(id,value){
						var d = new $.Deferred();
						window.setTimeout(function() { parseRow(typeAjax,id,value,premium, d) }, i*idx);
						deferreds.push(d);
						idx++;
					});

					$.when.apply($, deferreds).done(function () {
						BX.closeWait('loading',wait); // отключаем loading
					});
				});
			</script>
<?
		}
	}
}
?>
<script type="text/javascript">
function parseRow(typeAjax, id, value, premium, d){
	$.ajax({
		type: "POST",
		url: 'ajax_parsing.php',
		data: {
			type_ajax: typeAjax,
			id: id,
			values: value,
			premium: premium
		},
		dataType: "json",
		success: function (data) {
			progress();
			d.resolve();
			console.log(data);
		},
		error:function(msg){
			progress();
			d.resolve();
			console.log(msg['responseText']);
		}
	});
}
function update_images(typeAjax,id,value,j){
	$.ajax({
		type: "POST",
		url: 'ajax_parsing.php',
		data: {
			type_ajax: typeAjax,
			id: id,
			values: value
		},
		dataType: "json",
		success: function (data) {
			progress();
			j.resolve();
			console.log(data);
		},
		error:function(msg){
			progress();
			j.resolve();
			console.log(msg['responseText']);
		}
	});
}
function operation(val){
	if(val === 'tovar' || val === 'price'){
		$('.premium-box').css('display', 'block');
		$('#premium').prop('disabled', false);
	}else{
		$('.premium-box').css('display', 'none');
		$('#premium').prop('disabled', true);
	}
}
</script>
<?
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';