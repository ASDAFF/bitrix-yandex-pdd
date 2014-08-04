<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("lol.pdd");

$action = $_REQUEST["action"];

if($action=="info")
{
	$result = CLOLYandexPDD::GetMailInfo();
}
if($action=="reg")
{
	$result = CLOLYandexPDD::UserOauthToken();	
}
	echo $result ;
	
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>