<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lol.pdd/prolog.php");


$LOL_PDD_RIGHT = $APPLICATION->GetGroupRight("lol.pdd");
if($LOL_PDD_RIGHT!="W")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
$return_url = $APPLICATION->GetCurUri();
IncludeModuleLangFile(__FILE__);
CModule::IncludeModule("lol.pdd");


$sTableID = "tbl_lol_pdd_mailbox_user";
$oSort = new CAdminSorting($sTableID);
$lAdmin = new CAdminList($sTableID, $oSort);
$errorMessage = "";
$strOK = "";

$bSearch = isset($_REQUEST["search"]) && $_REQUEST["search"] == 'Y';
/*Параметры фильтра*/
$arFilterFields = Array(
		"find",
		"find_type",
		"find_id",
		"find_timestamp_1",
		"find_timestamp_2",
		"find_last_login_1",
		"find_last_login_2",
		"find_active",
		"find_login",
		"find_name",
		"find_email",
		"find_keywords",
		"find_group_id"
);
if ($bIntranetEdition)
	$arFilterFields[] = "find_intranet_users";
$USER_FIELD_MANAGER->AdminListAddFilterFields($entity_id, $arFilterFields);

$lAdmin->InitFilter($arFilterFields);

function CheckFilter($FilterArr)
{
	global $strError;
	foreach($FilterArr as $f)
		global $$f;

	$str = "";
	if(strlen(trim($find_timestamp_1))>0 || strlen(trim($find_timestamp_2))>0)
	{
		$date_1_ok = false;
		$date1_stm = MkDateTime(FmtDate($find_timestamp_1,"D.M.Y"),"d.m.Y");
		$date2_stm = MkDateTime(FmtDate($find_timestamp_2,"D.M.Y")." 23:59","d.m.Y H:i");
		if (!$date1_stm && strlen(trim($find_timestamp_1))>0)
			$str.= GetMessage("MAIN_WRONG_TIMESTAMP_FROM")."<br>";
		else $date_1_ok = true;
		if (!$date2_stm && strlen(trim($find_timestamp_2))>0)
			$str.= GetMessage("MAIN_WRONG_TIMESTAMP_TILL")."<br>";
		elseif ($date_1_ok && $date2_stm <= $date1_stm && strlen($date2_stm)>0)
		$str.= GetMessage("MAIN_FROM_TILL_TIMESTAMP")."<br>";
	}

	if(strlen(trim($find_last_login_1))>0 || strlen(trim($find_last_login_2))>0)
	{
		$date_1_ok = false;
		$date1_stm = MkDateTime(FmtDate($find_last_login_1,"D.M.Y"),"d.m.Y");
		$date2_stm = MkDateTime(FmtDate($find_last_login_2,"D.M.Y")." 23:59","d.m.Y H:i");
		if(!$date1_stm && strlen(trim($find_last_login_1))>0)
			$str.= GetMessage("MAIN_WRONG_LAST_LOGIN_FROM")."<br>";
		else
			$date_1_ok = true;
		if(!$date2_stm && strlen(trim($find_last_login_2))>0)
			$str.= GetMessage("MAIN_WRONG_LAST_LOGIN_TILL")."<br>";
		elseif($date_1_ok && $date2_stm <= $date1_stm && strlen($date2_stm)>0)
		$str.= GetMessage("MAIN_FROM_TILL_LAST_LOGIN")."<br>";
	}

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
			"ID" => $find_id,
			"TIMESTAMP_1" => $find_timestamp_1,
			"TIMESTAMP_2" => $find_timestamp_2,
			"LAST_LOGIN_1" => $find_last_login_1,
			"LAST_LOGIN_2" => $find_last_login_2,
			"ACTIVE" => $find_active,
			"LOGIN" => ($find!='' && $find_type == "login"? $find: $find_login),
			"NAME" => ($find!='' && $find_type == "name"? $find: $find_name),
			"EMAIL" => ($find!='' && $find_type == "email"? $find: $find_email),
			"KEYWORDS" => $find_keywords,
			"GROUPS_ID" => $find_group_id
	);
	if ($bIntranetEdition)
		$arFilter["INTRANET_USERS"] = $find_intranet_users;
	$USER_FIELD_MANAGER->AdminListAddFilter($entity_id, $arFilter);
}

if($handle_subord)
{
	$arFilter["CHECK_SUBORDINATE"] = $arUserSubordinateGroups;
	if($USER->CanDoOperation('edit_own_profile'))
		$arFilter["CHECK_SUBORDINATE_AND_OWN"] = $uid;
}
/*Конец параметров фильта*/




$sleep = COption::GetOptionInt("lol.pdd", "yandex_sleep", 1)*1000;
$stepCount = COption::GetOptionInt("lol.pdd", "yandex_request", 5);




$rsData=CUser::GetList(($by="ID"),($order="desc"),$arFilter, array("SELECT"=>array("UF_PDD_MAILBOX")));
$sessionId = md5($USER->GetID());

if(isset($_REQUEST["action"]) && $_REQUEST["action"]=="create" || isset($_REQUEST["action"]) && $_REQUEST["action"]=="drop")
{
	
	if(!$_REQUEST["ID"])
	{
		$rsUsers = CUser::GetList(($by="id"), ($order="desc"), array(), array("ID"));
		$i=1;
		while($users = $rsUsers->Fetch())
		{
			$post[] = $users["ID"];
			$i++;
		}
		$allI = ceil($i/$stepCount);
	}
	else
	{
		if(!isset($_SESSION[$sessionId]))
		{
			$post = $_REQUEST["ID"];
		}
		else 
		{
			$post = $_SESSION[$sessionId];
		}
		
		$allI = ceil(count($post)/$stepCount);
		
		 
	}
	if($_REQUEST["thisI"])
	{
		$thisI = $_REQUEST["thisI"];
		if($_REQUEST["action"]=="drop")
		{
			$result = CLOLYandexPDD::GroupDeleteMailBox($post, $_REQUEST["select_domain"], $allI, $thisI);
		}
		if($_REQUEST["action"]=="create")
		{
			$result = CLOLYandexPDD::CreateGroupMailbox($post, $_REQUEST["select_domain"], $allI, $thisI);
		}
		
		$result = $result["result"];
	}
	else
	{
		
		$thisI = 1;
		if($_REQUEST["action"]=="drop")
		{
			$result = CLOLYandexPDD::GroupDeleteMailBox($post, $_REQUEST["select_domain"], $allI);
		}
		if($_REQUEST["action"]=="create")
		{
			$result = CLOLYandexPDD::CreateGroupMailbox($post, $_REQUEST["select_domain"], $allI);
		}
		
		$result = $result["result"];
	}
	
	if(!isset($_SESSION[$sessionId]))
	{
		session_start();
		$_SESSION[$sessionId] = $post;
	}
	
	if(isset($result["thisI"]) && isset($result["allI"]) && $result["thisI"]==$result["allI"])
	{
		unset($_SESSION["$sessionId"]);
		?>
		<script>
			window.location.reload();
		</script>
		<?
	}
	else
	{
		
		$thisI = $result["thisI"]+1;
		?>
		<script>

			var url= "<?=$return_url;?>";
			var thisI = "<?=$thisI?>";
			var allI = "<?=$result["allI"];?>";
			var domain = "<?=$_REQUEST['select_domain'];?>";

			var par ="";
			<?foreach ($post as $id):?>
				if (par.length > 0)
					par = par + "&";
	
				par = par + "ID[]=" + "<?=$id?>";
			<?endforeach;?>

			<?if($_REQUEST["action"]=="create"):?>
				var data = "action=create&select_domain="+domain+"&"+par+"&thisI="+thisI+"&allI="+allI;
			<?endif;?>
			<?if($_REQUEST["action"]=="drop"):?>
				var data = "action=drop&select_domain="+domain+"&"+par+"&thisI="+thisI+"&allI="+allI;
			<?endif;?>
			
			var url = "/bitrix/admin/lol_pdd_mailbox_user.php";


				window.setTimeout('AjaxSend(url,data);',<?=$sleep;?>);
			
			
		
		</script>
		<?
	}
	if($_REQUEST["action"]=="create")
	{
		$message = GetMessage("CREATE_PDD_MAIBOX");
	}
	if($_REQUEST["action"]=="drop")
	{
		$message = GetMessage("DROP_PDD_MAIBOX");
	}
	CAdminMessage::ShowMessage(array(
	"TYPE" => "PROGRESS",
	"MESSAGE" => $message,
	"DETAILS" => "#PROGRESS_BAR#",
	"HTML" => true,
	"PROGRESS_TOTAL" => 100,
	"PROGRESS_VALUE" => $thisI * 100 / $result["allI"],
	));
	
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
	die();	
	
}


if(isset($_REQUEST["action"]) && $_REQUEST["action"]=="delete")
{
	$arMailBox[] = $_REQUEST["MailBox"];
	$arDelete=CLOLYandexPDD::DeleteMailBox($arMailBox);
	if($arDelete==1)
	{
		LocalRedirect("/bitrix/admin/lol_pdd_mailbox_user.php");
		
	}
	else
	{
		$errorMessage .= GetMessage("LOL_PDD_NO_DEL")."<br />";
	}	
}




$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(""));


$arHeaders = array();

$arHeaders = array(
	array("id"=>"LOGIN", 			"content"=>GetMessage("LOGIN"), "default"=>true),
	array("id"=>"LAST_NAME", 		"content"=>GetMessage("LAST_NAME"), "default"=>true),
	array("id"=>"UF_PDD_MAILBOX", 	"content"=>GetMessage("UF_PDD_MAILBOX"), "default"=>true),
);
$lAdmin->AddHeaders($arHeaders);


while($arRes = $rsData->NavNext(true, "f_")):
	$arActionsBar = array();
	$row =& $lAdmin->AddRow($f_ID, $arRes);
	
	
	$row->AddField("LAST_NAME", $f_NAME.' '.$f_LAST_NAME);
	if(!$arRes["UF_PDD_MAILBOX"])
	{
		$row->AddField("UF_PDD_MAILBOX", '<a href="/bitrix/admin/lol_pdd_mailbox_add.php?LOGIN='.$f_LOGIN.'&USER_ID='.$f_ID.'&lang='.LANGUAGE_ID.'" title="'.GetMessage("LOL_PDD_CREAT").'">'.GetMessage("LOL_PDD_CREAT").'</a>');
		$arActionsBar[] = array(
				"ICON" => "copy", "TEXT" => GetMessage("LOL_PDD_CREATE_USER_MAILBOX"),
				"ACTION" => "window.location='lol_pdd_mailbox_add.php?LOGIN=".$f_LOGIN."&USER_ID=".$f_ID."'");
	}
	else
	{
		$emailLogin = explode("@",$arRes["UF_PDD_MAILBOX"]);
		$arActionsBar[] = array(
				"ICON" => "edit", "TEXT" => GetMessage("LOL_PDD_EDIT_USER_MAILBOX"),
				"ACTION" => "window.location='lol_pdd_mailbox_edit.php?LOGIN=".$emailLogin[0]."&USER_ID=".$f_ID."'");
		$arActionsBar[] = array(
				"ICON" => "delete", "TEXT" => GetMessage("LOL_PDD_DELETE_USER_MAILBOX"),
				"ACTION" => "window.location='lol_pdd_mailbox_user.php?action=delete&MailBox=".$arRes["UF_PDD_MAILBOX"]."'");
	}
	
	
	$row->AddActions($arActionsBar);

endwhile;


$arFooter = array();
$arFooter[] = array(
		"title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"),
		"value"=>$rsData->SelectedRowsCount(),
);
$lAdmin->AddFooter($arFooter);




$arrDomain=CLOLYandexPDD::GetDomainList();
$sections .= '<div id="select_domain"><select name="select_domain">';
foreach ($arrDomain as $damain)
{
	$sections .= '<option value="'.$damain["NAME"].'">'.$damain["NAME"].'</option>';
}
$sections .= '</select></div>';

$arActions["domain"] = array("type" => "html", "value" => $sections);
$arActions["create"] = array("name"=>GetMessage("LOL_PDD_CREATE"), "action" => "setAccess('create');");
$arActions["drop"] = array("name"=>GetMessage("LOL_PDD_DELETE"), "action" => "setAccess('drop');");

$lAdmin->AddGroupActionTable($arActions);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("LOL_PDD_PAGE_TITLE"));






require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");


if(strlen($errorMessage)>0)
	CAdminMessage::ShowMessage($errorMessage);
if(strlen($strOK)>0)
	CAdminMessage::ShowNote($strOK);
?>
<script>

function setAccess(action)
{
		var
			oForm = document.forms['form_<?= $sTableID ?>'],
			expType = oForm.action_target && oForm.action_target.checked && oForm.action_target.selected,
			par = "";
	
		if (!expType)
		{
				for (var i = 0, l = oForm.elements.length; i < l; i++)
				{
					if (oForm.elements[i].tagName.toUpperCase() == "INPUT"
						&& oForm.elements[i].type.toUpperCase() == "CHECKBOX"
						&& oForm.elements[i].name.toUpperCase() == "ID[]"
						&& oForm.elements[i].checked == true)
					{
						if (par.length > 0)
							par = par + "&";
		
						par = par + "ID[]=" + BX.util.urlencode(oForm.elements[i].value);
						 
					}
					if(oForm.elements[i].tagName.toUpperCase() == "SELECT")
					{
						var domain = BX.util.urlencode(oForm.elements[i].value);
					}
				}
	
		}	

	var url= "<?=$return_url;?>";
	var use = "<?=$_REQUEST['use'];?>";
	var thisI = "<?=$_REQUEST['thisI'];?>";
	var allI = "<?=$_REQUEST['allI'];?>";

	

	if(action=="drop")
	{
		if(confirm("<?=GetMessage('LOL_PDD_OK_DROP')?>"))
		{
			AjaxSend(url,"action=drop&select_domain="+domain+"&"+par);
			location.hash = 'pdd_result_div';
			var startForm =  "<div class='adm-info-message-wrap adm-info-message-gray'><div class='adm-info-message'><div class='adm-info-message-title'><?=GetMessage('DROP_PDD_MAIBOX');?></div><div class='adm-progress-bar-outer' style='width: 500px;'><div class='adm-progress-bar-inner' style='width: 0px;'><div class='adm-progress-bar-inner-text' style='width: 500px;'>0%</div></div>0%</div><div class='adm-info-message-buttons'></div></div></div>";
			BX('pdd_result_div').innerHTML = startForm;
		}	
	 	
	}
	if(action=="create")
	{
		AjaxSend(url,"action=create&select_domain="+domain+"&"+par);
		location.hash = 'pdd_result_div';
		var startForm =  "<div class='adm-info-message-wrap adm-info-message-gray'><div class='adm-info-message'><div class='adm-info-message-title'><?=GetMessage('CREATE_PDD_MAIBOX');?></div><div class='adm-progress-bar-outer' style='width: 500px;'><div class='adm-progress-bar-inner' style='width: 0px;'><div class='adm-progress-bar-inner-text' style='width: 500px;'>0%</div></div>0%</div><div class='adm-info-message-buttons'></div></div></div>";
		BX('pdd_result_div').innerHTML = startForm;
	}
					
}
function AjaxSend(url, data)
{
	CHttpRequest.Action = function(result)
	{
		BX('pdd_result_div').innerHTML = result;
	}
	if (data)
		CHttpRequest.Post(url, data);
	else
		CHttpRequest.Send(url);
}


</script>


<div id="pdd_result_div"></div>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?
$arFindFields = array(
		GetMessage('MAIN_FLT_USER_ID'),
		GetMessage('MAIN_FLT_MOD_DATE'),
		GetMessage('MAIN_FLT_AUTH_DATE'),
		GetMessage('MAIN_FLT_ACTIVE'),
		GetMessage('MAIN_FLT_LOGIN'),
		GetMessage('MAIN_FLT_EMAIL'),
		GetMessage('MAIN_FLT_FIO'),
		GetMessage('MAIN_FLT_PROFILE_FIELDS'),
		GetMessage('MAIN_FLT_USER_GROUP')
	);
if ($bIntranetEdition)
	$arFindFields[] = GetMessage("F_FIND_INTRANET_USERS");

$USER_FIELD_MANAGER->AddFindFields($entity_id, $arFindFields);
$oFilter = new CAdminFilter(
	$sTableID."_filter",
	$arFindFields
);

$oFilter->Begin();
?>
<tr>
	<td><b><?=GetMessage("MAIN_FLT_SEARCH")?></b></td>
	<td nowrap>
		<input type="text" size="25" name="find" value="<?echo htmlspecialcharsbx($find)?>" title="<?=GetMessage("MAIN_FLT_SEARCH_TITLE")?>">
		<select name="find_type">
			<option value="login"<?if($find_type=="login") echo " selected"?>><?=GetMessage('MAIN_FLT_LOGIN')?></option>
			<option value="email"<?if($find_type=="email") echo " selected"?>><?=GetMessage('MAIN_FLT_EMAIL')?></option>
			<option value="name"<?if($find_type=="name") echo " selected"?>><?=GetMessage('MAIN_FLT_FIO')?></option>
		</select>
	</td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_ID")?></td>
	<td><input type="text" name="find_id" size="47" value="<?echo htmlspecialcharsbx($find_id)?>"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_TIMESTAMP").":"?></td>
	<td><?echo CalendarPeriod("find_timestamp_1", htmlspecialcharsbx($find_timestamp_1), "find_timestamp_2", htmlspecialcharsbx($find_timestamp_2), "find_form","Y")?></td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_LAST_LOGIN").":"?></td>
	<td><?echo CalendarPeriod("find_last_login_1", htmlspecialcharsbx($find_last_login_1), "find_last_login_2", htmlspecialcharsbx($find_last_login_2), "find_form","Y")?></td>
</tr>
<tr>
	<td><?echo GetMessage("F_ACTIVE")?></td>
	<td><?
		$arr = array("reference"=>array(GetMessage("MAIN_YES"), GetMessage("MAIN_NO")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_active", $arr, htmlspecialcharsbx($find_active), GetMessage('MAIN_ALL'));
		?>
	</td>
</tr>
<tr>
	<td><?echo GetMessage("F_LOGIN")?></td>
	<td><input type="text" name="find_login" size="47" value="<?echo htmlspecialcharsbx($find_login)?>"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_EMAIL")?></td>
	<td><input type="text" name="find_email" value="<?echo htmlspecialcharsbx($find_email)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("F_NAME")?></td>
	<td><input type="text" name="find_name" value="<?echo htmlspecialcharsbx($find_name)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_KEYWORDS")?></td>
	<td><input type="text" name="find_keywords" value="<?echo htmlspecialcharsbx($find_keywords)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr valign="top">
	<td><?echo GetMessage("F_GROUP")?><br><img src="/bitrix/images/main/mouse.gif" width="44" height="21" border="0" alt=""></td>
	<td><?
	$z = CGroup::GetDropDownList("AND ID!=2");
	echo SelectBoxM("find_group_id[]", $z, $find_group_id, "", false, 10);
	?></td>
</tr>
<?
if ($bIntranetEdition)
{
	?>
	<tr>
		<td><?echo GetMessage("F_FIND_INTRANET_USERS")?>:</td>
		<td><?
			$arr = array("reference"=>array(GetMessage("MAIN_YES")), "reference_id"=>array("Y"));
			echo SelectBoxFromArray("find_intranet_users", $arr, htmlspecialcharsbx($find_intranet_users), GetMessage('MAIN_ALL'));
			?>
		</td>
	</tr>
	<?
}
?>
<?
$USER_FIELD_MANAGER->AdminListShowFilter($entity_id);
$oFilter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"find_form"));
$oFilter->End();
?>
</form>
<?
if($message)
	echo $message->Show();
$lAdmin->DisplayList();
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>