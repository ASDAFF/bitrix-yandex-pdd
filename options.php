<?
$module_id = "lol.pdd";
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lol.pdd/include.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/admin/task_description.php");

if (!$USER->CanDoOperation('lol_pdd_edit_settings'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

if ($REQUEST_METHOD=="GET" && $USER->CanDoOperation('lol_pdd_edit_settings') && strlen($RestoreDefaults)>0 && check_bitrix_sessid())
{
	COption::RemoveOption("lol.pdd");
	$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
	while($zr = $z->Fetch())
		$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
}


global $MESS;
IncludeModuleLangFile(__FILE__);

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_TOKENS"), "ICON" => "", "TITLE" => GetMessage("MAIN_TAB_TITLE_TOKENS")),
	array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
	array("DIV" => "edit3", "TAB" => GetMessage("MAIN_TAB_ABOUT"), "ICON" => "", "TITLE" => GetMessage("MAIN_TAB_TITLE_ABOUT")),
	);

$siteList = array();
$rsSites = CSite::GetList($by="sort", $order="asc", Array());
$i = 0;
while($arRes = $rsSites->Fetch())
{
	$siteList[$i]["ID"] = $arRes["ID"];
	$siteList[$i]["NAME"] = $arRes["NAME"];
	$i++;
}
$siteCount = $i;

unset($rsSites);
unset($arRes);

$tabControl = new CAdmintabControl("tabControl", $aTabs);

$arTokenCaptcha=false;
if($REQUEST_METHOD == "POST" && (
			strlen($Update)>0 || 
			strlen($new_token) && strlen($new_token_domain)>0 && strlen($new_token_captcha_key)>0 && strlen($new_token_captcha_text)>0 || 
			strlen($new_token)>0 && strlen($new_token_login)>0 && strlen($new_token_password)>0 && strlen($new_token_domain)>0
		) && $USER->CanDoOperation('lol_pdd_edit_settings') && check_bitrix_sessid())
{
	$arTokens = array();
	if (isset($_POST['tokens']))
	{
		$arTokens = $_POST['tokens'];
		foreach ($arTokens as $k => $token)
		{
			$token = trim($token);
			if (strlen($token) <= 0)
				unset($arTokens[$k]);
			else
				$arTokens[$k] = $token;
		}
	}

	if(strlen($new_token)>0)
	{
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; ru; rv:1.9.2) Gecko/20100115 Firefox/3.6");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_REFERER, 'http://pdd.yandex.ru/');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
	
		curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
		
		$arHeader = array();
		$arHeader[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
		$arHeader[] = 'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7';
		$arHeader[] = 'Accept-Language: ru,en-us;q=0.7,en;q=0.3';
		$arHeader[] = 'Pragma: ';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		if(strlen($new_token_captcha_key)>0 && strlen($new_token_captcha_text)>0)
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_URL, "https://pddimp.yandex.ru/token/get.xml");
			curl_setopt($ch, CURLOPT_POSTFIELDS, array("domain"=>$new_token_domain,"key"=>$new_token_captcha_key, "token_get"=>"Get token", "rep"=>$new_token_captcha_text));
			$sTokenPage=curl_exec($ch);
		}
		else
		{
			curl_setopt($ch, CURLOPT_URL, "https://passport.yandex.ru/passport?mode=auth");
			curl_setopt($ch, CURLOPT_POSTFIELDS, array("login"=>$new_token_login,"passwd"=>$new_token_password));
		
			curl_exec($ch);
			
			curl_setopt($ch, CURLOPT_URL, "https://pddimp.yandex.ru/get_token.xml?domain_name=".$new_token_domain);
			$sTokenPage=curl_exec($ch);
		}
		
		if(preg_match("/action=\"get.xml\"/", $sTokenPage) && preg_match("/<input name=\"key\" type=\"hidden\" value=\"(.*?)\">/", $sTokenPage, $arKeyRegs))
		{
			$arTokenCaptcha=array("DOMAIN"=>$new_token_domain, "KEY"=>$arKeyRegs[1]);
		}
		else
		{
			unlink(dirname(__FILE__).'/cookie.txt');
			
			if(preg_match("/token=\"([a-z0-9]+)\"/", $sTokenPage, $arRegs))
			{
				$arTokens[]=$arRegs[1];
			}
			else
			{
				$strError=GetMessage('PDD_OPTION_TOKENS_NEW_ERROR');
			}
		}
		
		curl_close($ch);
		
	}
	
	
	$value = (count($arTokens) <= 0) ? '' : serialize($arTokens);
	COption::SetOptionString('lol.pdd', 'tokens', $value);
	
	if($_REQUEST["yandex_request"])
	{
		$value = intval($_REQUEST["yandex_request"]);
		COption::SetOptionString('lol.pdd', 'yandex_request', $value);
	}
	if($_REQUEST["yandex_sleep"])
	{
		$value = intval($_REQUEST["yandex_sleep"]);
		COption::SetOptionString('lol.pdd', 'yandex_sleep', $value);
	}
	
	if($strError=="" && $arTokenCaptcha==false)
	{
		if((strlen($Update)>0 || strlen($new_token)>0) && strlen($_REQUEST["back_url_settings"])>0)
			LocalRedirect($_REQUEST["back_url_settings"]);
		else
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
	}

}

if(strlen($strError)>0)
	CAdminMessage::ShowMessage($strError);

if(strlen($strOK)>0)
	CAdminMessage::ShowNote($strOK);

CUtil::InitJSCore();

$tabControl->Begin();

?>

<form method="POST" enctype="multipart/form-data" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&lang=<?echo LANG?>">
<?=bitrix_sessid_post()?>
<?if(strlen($_REQUEST["back_url_settings"])>0):?>
	<input type="hidden" name="back_url_settings" value="<?=htmlspecialchars($_REQUEST["back_url_settings"])?>">
<?endif?>
<?$tabControl->BeginNextTab();
?>
	<tr class="heading">
		<td colspan="2">
			<?echo GetMessage("PDD_OPTION_TOKENS_TITLE");?>
		</td>
	</tr>
<?
	$strTokens = COPtion::GetOptionString('lol.pdd', 'tokens', '');

	$arTokens = array();
	if ($strTokens != '')
	{
		$arTokens = unserialize($strTokens);
	}

	if (is_array($arTokens) && count($arTokens) > 0)
	{
		foreach ($arTokens as $k=>$token)
		{
?>
			<tr>
				<td width="100%"><input type="text" size="50" name="tokens[<?=$k?>]" value="<?echo htmlspecialchars($token)?>" /></td>
			</tr>
<?
		}
	}
	else
	{
?>
	<tr>
		<td align="center" colspan="2"><?echo BeginNote(),GetMessage('PDD_OPTION_TOKENS_NOTOKENS'),EndNote();?></td>
	</tr>
<?
	}
?>
	<tr><td colspan="2"><table width="50%" align="center">
		<tr class="heading"><td width="100%" colspan="2"><?= GetMessage('PDD_OPTION_TOKENS_TOKEN_ADD')?></td></tr>
		<tr>
			<td colspan="2"><input type="text" style="width: 100%;" name="tokens[]" value="" /><br><br></td>
		</tr>
		
		<tr class="heading"><td width="100%" colspan="2"><?=GetMessage('PDD_OPTION_TOKENS_NEW')?></td></tr>
	<?if($arTokenCaptcha!==false):?>
	<tr>
		<td align="center" colspan="2"><?echo BeginNote(),GetMessage('PDD_OPTION_TOKENS_NEW_CAPTCHA_NOTE'),EndNote();?></td>
	</tr>

				<tr>
					<td></td>
					<td><img src="https://u.captcha.yandex.net/image?key=<?=$arTokenCaptcha["KEY"]?>" /></td>
				</tr>
				<tr>
					<td><?=GetMessage('PDD_OPTION_TOKENS_NEW_CAPTCHA')?>:</td>
					<td><input type="text" style="width: 100%;" name="new_token_captcha_text" value="" />
					<input type="hidden" name="new_token_domain" value="<?=$arTokenCaptcha["DOMAIN"]?>">
					<input type="hidden" name="new_token_captcha_key" value="<?=$arTokenCaptcha["KEY"]?>">
					</td>
				</tr>	
	<?else:?>
	<tr>
		<td align="center" colspan="2"><?echo BeginNote(),GetMessage('PDD_OPTION_TOKENS_NEW_NOTE'),EndNote();?></td>
	</tr>

				<tr>
					<td><?=GetMessage('PDD_OPTION_TOKENS_NEW_DOMAIN')?>:</td>
					<td><input type="text" style="width: 100%;" name="new_token_domain" value="" /></td>
				</tr>
				<tr>
					<td><?=GetMessage('PDD_OPTION_TOKENS_NEW_LOGIN')?>:</td>
					<td><input type="text" style="width: 100%;" name="new_token_login" value="" /></td>
				</tr>
				<tr>
					<td><?=GetMessage('PDD_OPTION_TOKENS_NEW_PASSWORD')?>:</td>
					<td><input type="password" style="width: 100%;" name="new_token_password" value="" /></td>
				</tr>
	<?endif?>				
			<tr>
				<td width="100%" colspan="2">
				
				<input type="submit" name="new_token" value="<?=GetMessage('PDD_OPTION_TOKENS_NEW_BUTTON')?>" /></td>
			</tr>	

		<tr class="heading"><td width="100%" colspan="2"><?=GetMessage('PDD_OPTION_TOKENS_NEW_MANUAL')?></td></tr>
	<tr>
		<td align="center" colspan="2"><?echo BeginNote(),GetMessage('PDD_OPTION_TOKENS_NEW_MANUAL_NOTE'),EndNote();?></td>
	</tr>

				<tr>
					<td><?=GetMessage('PDD_OPTION_TOKENS_NEW_MANUAL_DOMAIN')?>:</td>
					<td><input type="text" style="width: 100%;" name="new_token_manual_domain" id="new_token_manual_domain" value="" /></td>
				</tr>
			<tr>
				<td width="100%" colspan="2">
				<input type="button" name="new_token_manual" value="<?=GetMessage('PDD_OPTION_TOKENS_NEW_BUTTON')?>" onClick="NewTokenManual();" /></td>
			</tr>	
	<tr class="heading"><td width="100%" colspan="2"><?=GetMessage('PDD_OPTION_YANDEX_SETTING')?></td></tr>
				<tr>
					<td><?=GetMessage('PDD_OPTION_YNDEX_REQUEST')?>:</td>
					<td><input type="text" style="width: 100%;" name="yandex_request" value="<?=COption::GetOptionInt("lol.pdd", "yandex_request", 5);?>" /></td>
				</tr>
				<tr>
					<td><?=GetMessage('PDD_OPTION_YNDEX_SLEEP')?>:</td>
					<td><input type="text" style="width: 100%;" name="yandex_sleep" value="<?=COption::GetOptionInt("lol.pdd", "yandex_sleep", 1);?>" /></td>
				</tr>
		
		
	</table></td></tr>
	
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");?>

<?$tabControl->BeginNextTab();?>
	<tr>
		<td colspan="2"><?echo GetMessage('PDD_OPTION_ABOUT_LOL')?></td>
	</tr>


	<tr>
		<td colspan="2"><?echo BeginNote(),GetMessage('PDD_OPTION_ABOUT_YANDEX'),EndNote();?></td>
	</tr>

<?$tabControl->Buttons();?>
<script>
	function RestoreDefaults()
	{
		if(confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
			window.location = "<?= $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)?>&<?=bitrix_sessid_get()?>";
	}

	function NewTokenManual()
	{
		window.open("https://pddimp.yandex.ru/get_token.xml?domain_name="+document.getElementById('new_token_manual_domain').value, "get_token", "menubar=no,location=yes,resizable=no,scrollbars=yes,status=yes,width=640,height=480");
	}

</script>
<input type="submit" <?if (!$USER->CanDoOperation('lol_pdd_edit_settings')) echo "disabled" ?> name="Update" value="<?= GetMessage('PDD_OPTION_SAVE')?>">
<input type="reset" name="reset" value="<?= GetMessage('PDD_OPTION_RESET')?>">
<input type="hidden" name="Update" value="Y">
<input <?if (!$USER->CanDoOperation('lol_pdd_edit_settings')) echo "disabled" ?> type="button" title="<?= GetMessage('PDD_OPTION_RESTORE_DEFAULTS')?>" OnClick="RestoreDefaults();" value="<?= GetMessage('PDD_RESTORE_DEFAULTS')?>">
<?$tabControl->End();?>
</form>
