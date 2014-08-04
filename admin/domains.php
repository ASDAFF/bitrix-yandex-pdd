<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lol.pdd/prolog.php");

$LOL_PDD_RIGHT = $APPLICATION->GetGroupRight("lol.pdd");
if($LOL_PDD_RIGHT!="W")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);
CModule::IncludeModule("lol.pdd");

$sTableID = "tbl_lol_pdd_domains";
$oSort = new CAdminSorting($sTableID);
$lAdmin = new CAdminList($sTableID, $oSort);

$arrData=CLOLYandexPDD::GetDomainList();

$rsData = new CDBResult;
$rsData->InitFromArray($arrData);

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(""));

$arHeaders = array();

$arHeaders[]=
	array(	"id"		=>"NAME",
		"content"	=>GetMessage("LOL_PDD_NAME"),
		"sort"		=>false,
		"default"	=>true,
	);	
$arHeaders[]=
	array(	"id"		=>"MAILBOX_COUNT",
		"content"	=>GetMessage("LOL_PDD_MAILBOX_COUNT"),
		"sort"		=>false,
		"align"		=>"right",
		"default"	=>true,
	);
$arHeaders[]=
	array(	"id"		=>"MAILBOX_MAX",
		"content"	=>GetMessage("LOL_PDD_MAILBOX_MAX"),
		"sort"		=>false,
		"align"		=>"right",
		"default"	=>true,
	);
$arHeaders[]=
	array(	"id"		=>"STATUS",
		"content"	=>GetMessage("LOL_PDD_STATUS"),
		"sort"		=>false,
		"default"	=>true,
	);
$lAdmin->AddHeaders($arHeaders);

while($arRes = $rsData->NavNext(true, "f_")):
	$row =& $lAdmin->AddRow($f_ID, $arRes);

	$strHTML=GetMessage("LOL_PDD_STATUS_".$f_STATUS);
	$row->AddViewField("STATUS",$strHTML);

endwhile;

$arFooter = array();
$arFooter[] = array(
	"title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"),
	"value"=>$rsData->SelectedRowsCount(),
	);
$lAdmin->AddFooter($arFooter);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("LOL_PDD_PAGE_TITLE"));

/***************************************************************************
		   HTML form
****************************************************************************/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($message)
	echo $message->Show();
$lAdmin->DisplayList();
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
