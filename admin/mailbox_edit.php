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
$return_url = $APPLICATION->GetCurUri();
?>

<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");?>

<? if(isset($_REQUEST['autosave_id']))
{
	if($_REQUEST['PASS']!="")
	{
		
		if ($_REQUEST['PASS']!=$_REQUEST['PASS2'])
		{
			$errorMessage .= GetMessage("LOL_PDD_EDIT_SAVE_ERROR_PASS")."<br />";
		}
		else 
		{
			$pass = $_REQUEST['PASS'];
		}
	}
	else 
	{
		$pass = 0;
	}
	$login = $_REQUEST['LOGIN'];
	$fname = $_REQUEST['FNAME'];
	$iname = $_REQUEST['INAME'];
	$sex = $_REQUEST['SEX'];
	if(strlen($errorMessage)==0)
	{
		$arrData=CLOLYandexPDD::EditUserInfo($login, $fname, $iname, $sex, $pass);
		if($arrData===true)
		{
			$strOK .= GetMessage("LOL_PDD_EDIT_SAVE_OK")."<br />";
			
			if($_REQUEST['save'] && $_REQUEST['USER_ID'])
			{
				LocalRedirect("lol_pdd_mailbox_user.php");
			}
			
			if($_REQUEST['save'])
			{
				LocalRedirect("lol_pdd_mailbox.php");
			}
		}
		if($arrData===false)
		{
			$errorMessage .= GetMessage("LOL_PDD_EDIT_SAVE_NO")."<br />";
		}
	}
	
}
?>
<? 
if(isset($_REQUEST["LOGIN"]))
{
	$arUserInfo=CLOLYandexPDD::GetUserInfo($_REQUEST["LOGIN"]);
}
else
{
	$arUserInfo=CLOLYandexPDD::GetUserInfo($arrData["LOGIN"]);
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

<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?LOGIN=<?echo $arUserInfo['LOGIN'];?><?if($_REQUEST['USER_ID']):?>&USER_ID=<?=$_REQUEST['USER_ID'];?><?endif;?>" name="form1">

<?$tabControl->Begin();
$tabControl->BeginNextTab();
?>

    <tr>
		<td width="40%"><?echo GetMessage("LOL_PDD_EDIT_EMAILBOX")?>:</td>
		<td width="60%">
			<span><?=$arUserInfo['LOGIN']?>@<?=$arUserInfo['DOMAIN'] ?></span>
			<input type="hidden" name="LOGIN" size="50" value="<?=$arUserInfo['LOGIN']?>">
		</td>
	</tr>
 	<tr>
		<td width="40%"><?echo GetMessage("LOL_PDD_EDIT_FNAME")?>:</td>
		<td width="60%">
			<input type="text" name="FNAME" size="50" value="<?=$arUserInfo['FNAME']?>">
		</td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("LOL_PDD_EDIT_INAME")?>:</td>
		<td width="60%">
			<input type="text" name="INAME" size="50" value="<?=$arUserInfo['INAME']?>">
		</td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("LOL_PDD_EDIT_SEX")?>:</td>
		<td width="60%">
			<select name="SEX">
					<option <?if($arUserInfo['SEX']==1) echo " selected ";?> value="1"><?=GetMessage("LOL_PDD_EDIT_SEX_M")?></option>
					<option <?if($arUserInfo['SEX']==2) echo " selected ";?> value="2"><?=GetMessage("LOL_PDD_EDIT_SEX_W")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("LOL_PDD_EDIT_PASS")?>:</td>
		<td width="60%">
			<input class="pass" type="password" name="PASS" size="50" value="">
			<span class="showPass"></span>
		</td>
	</tr>
	<tr>	
		<td width="40%"><?echo GetMessage("LOL_PDD_EDIT_PASS2")?>:</td>
		<td width="60%">
			<input class="pass" type="password" name="PASS2" size="50" value="">
		</td>
		
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%">
			<a href="" class="genPass" onclick="return false"><?echo GetMessage("LOL_PDD_EDIT_GENPASS")?></a>
		</td>
	</tr>
	
<? 

?>
<?
$tabControl->BeginNextTab();

$tabControl->EndTab();

if($_REQUEST['USER_ID'])
{
	$tabControl->Buttons(
			array(
					"disabled" => ($LOL_PDD_RIGHT < "W"),
					"back_url" => "/bitrix/admin/lol_pdd_mailbox_user.php?lang=".LANG
			)
	);
}
else
{
	$tabControl->Buttons(
			array(
					"disabled" => ($LOL_PDD_RIGHT < "W"),
					"back_url" => "/bitrix/admin/lol_pdd_mailbox.php?lang=".LANG
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