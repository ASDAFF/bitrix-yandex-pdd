<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lol.pdd/prolog.php");

$LOL_PDD_RIGHT = $APPLICATION->GetGroupRight("lol.pdd");
if ($LOL_PDD_RIGHT < "R")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lol.pdd/include.php");
IncludeModuleLangFile(__FILE__);
CModule::IncludeModule("lol.pdd");
$errorMessage = "";
$strOK = "";
?>

<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");?>

<?
if(isset($_REQUEST['autosave_id']))
{
	if ($_REQUEST['NAME']!="" && $_REQUEST['DOMAIN']!="" && $_REQUEST['PASS']!="" )
	{
		if ($_REQUEST['PASS']!=$_REQUEST['PASS2'])
		{
			$errorMessage .= GetMessage("LOL_PDD_ADD_SAVE_ERROR_PASS")."<br />";
		}
	}
	else 
	{
		$errorMessage .= GetMessage("LOL_PDD_ADD_SAVE_ERROR1")."<br />";
	}
	if(strlen($errorMessage) <= 0)
	{
		$domain = $_REQUEST['DOMAIN'];
		$login = $_REQUEST['NAME'];
		$pass = $_REQUEST['PASS'];
		$userId = $_REQUEST['USER_ID'];
		$arrData=CLOLYandexPDD::RegUser($domain, $login, $pass, $userId);
		if($arrData===true)
		{
			$strOK .= GetMessage("LOL_PDD_ADD_SAVE_OK")."<br />";
			if(isset($_REQUEST['save']) && !isset($_REQUEST["USER_ID"]))
			{
				LocalRedirect("lol_pdd_mailbox.php");	
			}
			if(isset($_REQUEST['save']) && isset($_REQUEST["USER_ID"]))
			{
				LocalRedirect("lol_pdd_mailbox_user.php");
			}
		}
		elseif($arrData=="occupied")
		{
			$errorMessage .= GetMessage("LOL_PDD_ADD_SAVE_ERROR2")."<br />";
		}
		elseif ($arrData=="badlogin")
		{
			$errorMessage .= GetMessage("LOL_PDD_ADD_SAVE_ERROR3")."<br />";
		}
		
	}
}
$login="";
if(isset($_REQUEST["LOGIN"]) && $_REQUEST["LOGIN"]!="")
{
	$params = Array(
			"max_len" => "30", 
			"replace_space" => "", 
			"replace_other" => "", 
			"delete_repeat_replace" => "true", 
			"use_google" => "false", 
	);
	$userId = $_REQUEST["ID"];
	$login =CUtil::translit($_REQUEST["LOGIN"], "ru", $params);
	$rest = substr($login, 0, 1);
	
	if(is_numeric($rest))
	{
		$login="";
	}
	//$str = preg_replace("[^a-zA-Z0-9\-\.]", "", $_REQUEST["LOGIN"]);
	
}
?>
<?
$aTabs = array(
		array("DIV" => "edit1", "TAB" => GetMessage("LOL_PDD_TAB_NAME"), "ICON" => "", "TITLE" => GetMessage("LOL_PDD_TAB_NAME")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);?>
<?
if(strlen($errorMessage)>0)
	CAdminMessage::ShowMessage($errorMessage);
if(strlen($strOK)>0)
	CAdminMessage::ShowNote($strOK);
?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?><?if($_REQUEST["USER_ID"]):?>?USER_ID=<?=$_REQUEST['USER_ID'];?><?endif;?>" name="form1">
<input type="hidden" name="ID" size="50" value="<?=$_REQUEST["ID"];?>">
<?$tabControl->Begin();
$tabControl->BeginNextTab();
?>

		<?if($_REQUEST["USER_ID"]):?>
			<?$rsUser = CUser::GetByID($_REQUEST["USER_ID"]);
			$arUser = $rsUser->Fetch(); ?>
		<tr>
			<td width="40%"><?echo GetMessage("LOL_PDD_ADD_USER")?>:</td>
			<td width="60%">
				<?=$arUser['LOGIN'];?>
			</td>
		</tr>
		<?endif;?>
    <tr>
		<td width="40%"><span class="required">*</span><?echo GetMessage("LOL_PDD_ADD_NAME")?>:</td>
		<td width="60%">
			<?if($login!=""):?>
				<input type="text" name="NAME" size="50" value="<?=$login;?>">
			<?else:?>
				<input type="text" name="NAME" size="50" value="">
			<?endif;?>
		</td>
	</tr>
 	<tr>
		<td width="40%"><span class="required">*</span><?echo GetMessage("LOL_PDD_ADD_DOMAIN")?>:</td>
		<td width="60%">
			<select name="DOMAIN">
				<?$arrData=CLOLYandexPDD::GetDomainList();?>
				<?foreach($arrData as $key=>$domain):?>
					<option value="<?=$domain["NAME"]?>"><?=$domain["NAME"]?></option>
				<?endforeach;?>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%"><span class="required">*</span><?echo GetMessage("LOL_PDD_ADD_PASS1")?>:</td>
		<td width="60%">
			<input class="pass" type="password" name="PASS" size="50" value="">
			<span class="showPass"></span>
		</td>
	</tr>
	<tr>
		<td width="40%"><span class="required">*</span><?echo GetMessage("LOL_PDD_ADD_PASS2")?>:</td>
		<td width="60%">
			<input class="pass" type="password" name="PASS2" size="50" value="">
		</td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%">
			<a href="" class="genPass" onclick="return false"><?echo GetMessage("LOL_PDD_ADD_GENPASS")?></a>
		</td>
	</tr>
	
<? 

?>
<?
$tabControl->BeginNextTab();

$tabControl->EndTab();
if(!isset($_REQUEST["USER_ID"]))
{
	$tabControl->Buttons(
			array(
					"disabled" => ($LOL_PDD_RIGHT < "W"),
					"back_url" => "/bitrix/admin/lol_pdd_mailbox.php?lang=".LANG
			)
	);
}
else 
{
	$tabControl->Buttons(
			array(
					"disabled" => ($LOL_PDD_RIGHT < "W"),
					"back_url" => "/bitrix/admin/lol_pdd_mailbox_user.php?lang=".LANG
			)
	);
}

?>

<?
$tabControl->End();
?>
</form>


<? CJSCore::Init( 'jquery' ); ?>
<script type="text/javascript">
$(document).ready(function() {
    function str_rand() {
        var result       = '';
        var words        = '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789';
        var max_position = words.length - 1;
            for( i = 0; i < 8; ++i ) {
                position = Math.floor ( Math.random() * max_position );
                result = result + words.substring(position, position + 1);
            }
        return result;
    }
    $(".genPass").click(function() {
        var pass = str_rand();
        $(".pass").attr("value",pass);
        $(".pass2").attr("value",pass); 
        $(".showPass").text(pass);
          
    });
});
</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>