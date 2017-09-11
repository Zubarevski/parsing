<?
IncludeModuleLangFile(__FILE__);
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */

if ($APPLICATION->GetGroupRight("alma_parsing") != "D")
{

	$aMenu = array(
		"parent_menu" => "global_menu_settings",
		"section" => "alma_parsing",
		"sort" => 1860,
		"text" => "Парсинг сайта",
		"title" => "Парсинг сайта",
		"icon" => "sys_menu_icon",
		"page_icon" => "sys_page_icon",
		"items_id" => "menu_alma_parsing",
		"items" => array(
			array(
				"text" => "Загрузка каталога",
				"url" => "alma_parsing.php?lang=RU",
				"more_url" => array("alma_parsing.php"),
				"title" => "Загрузка каталога",
			),
		),
	);

	return $aMenu;
}
return false;
