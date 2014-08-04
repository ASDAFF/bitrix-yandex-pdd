<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lol.pdd/prolog.php");


$LOL_PDD_RIGHT = $APPLICATION->GetGroupRight("lol.pdd");
if($LOL_PDD_RIGHT!="W")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
$return_url = $APPLICATION->GetCurUri();
IncludeModuleLangFile(__FILE__);
CModule::IncludeModule("lol.pdd");



$sTableID = "tbl_lol_pdd_emailbox";
$oSort = new CAdminSorting($sTableID);
$lAdmin = new CAdminList($sTableID, $oSort);
$errorMessage = "";
$strOK = "";

// инициализация параметров списка - фильтры
$arFilterFields = Array(
		"find_email_name"
);
$lAdmin->InitFilter($arFilterFields);

function CheckFilter($arFields) // проверка введенных полей
{
	global $strError;
	$str = "";
	$strError .= $str;
	if(strlen($str)>0)
	{
		global $lAdmin;
		$lAdmin->AddFilterError($str);
		return false;
	}

	return true;
}
$arFilter = Array();
if(CheckFilter($arFilterFields))
{
	$arFilter = Array(
			"EMAIL_NAME" => ($find_email_name)		
	);
}


if($_REQUEST["set_filter"]=="Y")
{
	$arrData=CLOLYandexPDD::GetMailBoxList($arFilter);
}
else
{
	$arrData=CLOLYandexPDD::GetMailBoxList();
}
if($_REQUEST["action_button"]=="delete" && !isset($_REQUEST["action_target"]) && $_REQUEST["ID"]!="")
{
	$arDelete=CLOLYandexPDD::DeleteMailBox($_REQUEST["ID"]);
	if($arDelete==1)
	{
		LocalRedirect($return_url);
	}
	else
	{
		$errorMessage .= GetMessage("LOL_PDD_NO_DEL")."<br />";
	}
}
if($_REQUEST["action_button"]=="delete" && isset($_REQUEST["action_target"]))
{
	$arDelete=CLOLYandexPDD::DeleteMailBox(0);
	if($arDelete==1)
	{
		LocalRedirect($return_url);
	}
	else
	{
		$errorMessage .= GetMessage("LOL_PDD_NO_DEL")."<br />";
	}
}
if(isset($_REQUEST["action"]) && $_REQUEST["action"]=="drop")
{
	$arMailBox[] = $_REQUEST["MailBox"];
	$arDelete=CLOLYandexPDD::DeleteMailBox($arMailBox);
	if($arDelete==1)
	{
		LocalRedirect("/bitrix/admin/lol_pdd_mailbox.php");
	}
	else
	{
		$errorMessage .= GetMessage("LOL_PDD_NO_DEL")."<br />";
	}
}

if(strlen($errorMessage)>0)
	CAdminMessage::ShowMessage($errorMessage);
if(strlen($strOK)>0)
	CAdminMessage::ShowNote($strOK);

$rsData = new CDBResult;
$rsData->InitFromArray($arrData);

$rsData = new CAdminResult($rsData, $sTableID);

$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(""));

$arHeaders = array();

$arHeaders[]=
		array(	"id"		=>"EMAIL_NAME",
				"content"	=>GetMessage("LOL_PDD_EMAIL_NAME"),
				"sort"		=>false,
				"default"	=>true,
		);
$arHeaders[]=
		array(	"id"		=>"DOMAIN_NAME",
				"content"	=>GetMessage("LOL_PDD_DOMAIN_NAME"),
				"sort"		=>false,
				"default"	=>true,
		);
$arHeaders[]=
		array(	"id"		=>"EMAIL_LOCK",
				"content"	=>GetMessage("LOL_PDD_EMAIL_LOCK"),
				"sort"		=>false,
				"default"	=>true,
		);
$arHeaders[]=
		array(	"id"		=>"EMAIL_STATUS",
				"content"	=>GetMessage("LOL_PDD_EMAIL_STATUS"),
				"sort"		=>false,
				"default"	=>true,
		);

$lAdmin->AddHeaders($arHeaders);

while($arRes = $rsData->NavNext(true, "f_")):
$row =& $lAdmin->AddRow($f_EMAIL_NAME."@"."$f_DOMAIN_NAME", $arRes);

$row->AddField("EMAIL_NAME", '<a href="/bitrix/admin/lol_pdd_mailbox_edit.php?LOGIN='.$f_EMAIL_NAME.'&lang='.LANGUAGE_ID.'" title="'.GetMessage("LOL_PDD_EDIT").'">'.$f_EMAIL_NAME.'</a>');
$row->AddViewField("EMAIL_LOCK",GetMessage("LOL_PDD_EMAIL_LOCK_".$f_EMAIL_LOCK));
$row->AddViewField("EMAIL_STATUS",GetMessage("LOL_PDD_EMAIL_STATUS_".$f_EMAIL_STATUS));


$arActionsBar = array();
$arActionsBar[] = array(
		"ICON" => "edit", "TEXT" => GetMessage("LOL_PDD_EDIT_USER_MAILBOX"),
		"ACTION" => "window.location='lol_pdd_mailbox_edit.php?LOGIN=".$f_EMAIL_NAME."'");
$arActionsBar[] = array(
		"ICON" => "delete", "TEXT" => GetMessage("LOL_PDD_DELETE_USER_MAILBOX"),
		"ACTION" => "window.location='lol_pdd_mailbox.php?action=drop&MailBox=".$f_EMAIL_NAME."@".$f_DOMAIN_NAME."'");

$row->AddActions($arActionsBar);

endwhile;

$aContext = array(
		array(
				"TEXT" => GetMessage("LOL_PDD_ADD_EMAIL"),
				"ICON" => "btn_new",
				"LINK" => "lol_pdd_mailbox_add.php?lang=".LANG,
				"TITLE" => GetMessage("LOL_PDD_ADD_EMAIL")
		),
);
$lAdmin->AddAdminContextMenu($aContext);




$arFooter = array();
$arFooter[] = array(
		"title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"),
		"value"=>$rsData->SelectedRowsCount(),
);
$lAdmin->AddFooter($arFooter);
$lAdmin->AddGroupActionTable(
		array(
				"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("LOL_PDD_PAGE_TITLE"));


require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
if($message)
	echo $message->Show();

?>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?
$arFilter = Array(
		"EMAIL_NAME"=> $find_email_name
	);
$oFilter = new CAdminFilter(
		$sTableID."_filter",
		array(
				GetMessage('TASK_FILTER_LETTER'),
				GetMessage('TASK_FILTER_MODULE_ID'),
				GetMessage('TASK_FILTER_SYS'),
				GetMessage('TASK_FILTER_BINDING')
		)
);
$oFilter->Begin();
?>
<tr>
	<td nowrap><?echo GetMessage("TASK_FILTER_EMAIL_NAME")?>:</td>
	<td nowrap><input type="text" name="find_email_name" value="<?echo htmlspecialcharsbx($find_email_name)?>" size="35"></td>
</tr>

<?
$oFilter->Buttons(array("table_id"=>htmlspecialcharsbx($sTableID), "url"=>$APPLICATION->GetCurPage(), "form"=>"find_form"));
$oFilter->End();
?>
</form>
<?
$lAdmin->DisplayList();
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>