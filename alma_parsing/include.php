<?
global $DB;
$db_type = strtolower($DB->type);
CModule::AddAutoloadClasses(
	"alma_parsing",
	array(
		"alma_parsing" => "install/index.php",
	)
);

?>