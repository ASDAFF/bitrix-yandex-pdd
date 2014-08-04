<?
global $MESS;
IncludeModuleLangFile(__FILE__);

class CLOLYandexPDD
{
	function GetTokens()
	{
		$strTokens = COption::GetOptionString('lol.pdd', 'tokens', '');
		
		$arTokens = array();
		if ($strTokens != '')
			$arTokens = unserialize($strTokens);
		return $arTokens;
	}
	
	function GetDomainList()
	{
		$arTokens=self::GetTokens();
		
		$arDomains=array();
		$selectDomains=array();
		foreach($arTokens as $sToken)
		{
			$obData=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/get_domain_users.xml?on_page=1&page=1&token=".$sToken));
			$selectDomains = self::ConvertEncoding($obData->domains);
			foreach($selectDomains->domain as $keyDomain=>$value)
			{
				$domainName = strval(self::ConvertEncoding($value->name));
	
				$arDomains[]=array(
					"ID"=>$sToken,
					"NAME"=>$domainName,
					"MAILBOX_COUNT"=>intval($obData->domains->domain->emails->total),
					"MAILBOX_MAX"=>intval($obData->domains->domain->{'emails-max-count'}),
					"STATUS"=>self::ConvertEncoding($obData->domains->domain->status),
				);
			}
		}
		return $arDomains;
	}
	
	
	
	
	
	function GetMailBoxList($arFilter=array())
	{
		$arTokens=self::GetTokens();
		$arDomains=array();
		foreach($arTokens as $sToken)
		{

			if(!isset($arFilter["EMAIL_NAME"]) || !strlen($arFilter["EMAIL_NAME"]))
			{
				
				$obDataMailBoxList=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/get_domain_users.xml?on_page=100&token=".$sToken));
				
				$selectEmailName = array();
				$selectEmailLock = array();
				$selectEmailStatus = array();
				$arMailBoxName =array();
	
				$selectEmailName = $obDataMailBoxList->domains->domain->emails;
				
				foreach($selectEmailName->email as $keyEmail=>$value)
				{
					$emailName = strval(self::ConvertEncoding($value->name));
					
					$obDataMailBoxLock=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/get_user_info.xml?token=".$sToken."&login=".$emailName));
					$selectEmailLock = intval($obDataMailBoxLock->domain->user->enabled);
					$selectEmailStatus = intval($obDataMailBoxLock->domain->user->signed_eula);
					$selectDomainName = self::ConvertEncoding($obDataMailBoxLock->domain->name);
					$arMailBox[] = array(
							"DOMAIN_NAME"=>$selectDomainName,
							"EMAIL_NAME"=>$emailName,
							"EMAIL_LOCK"=>$selectEmailLock,
							"EMAIL_STATUS"=>$selectEmailStatus,
						);
				}
			}
			else
			{
				$obDataMailBoxLock=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/get_user_info.xml?token=".$sToken."&login=".$arFilter['EMAIL_NAME']));
				$emailName = self::ConvertEncoding($obDataMailBoxLock->domain->user->login);
				$selectEmailLock = intval($obDataMailBoxLock->domain->user->enabled);
				$selectEmailStatus = intval($obDataMailBoxLock->domain->user->signed_eula);
				$selectDomainName = self::ConvertEncoding($obDataMailBoxLock->domain->name);
			
				$arMailBox[] = array(
						"DOMAIN_NAME"=>$selectDomainName,
						"EMAIL_NAME"=>$emailName,
						"EMAIL_LOCK"=>$selectEmailLock,
						"EMAIL_STATUS"=>$selectEmailStatus,
				);
			}
		}
		if($arMailBox[0]["EMAIL_NAME"])
		{
			return $arMailBox;
		}
			
	}
	function DeleteMailBox($arrMailBox)
	{
		$arTokens=self::GetTokens();
		$arDomains=array();
		foreach($arTokens as $sToken)
		{
			if($arrMailBox!=0)
			{
				foreach ($arrMailBox as $delKey=>$value)
				{
					$box = explode("@",$value);
					$obDataMailBoxList=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/api/del_user.xml?token=".$sToken."&domain=".$box[1]."&login=".$box[0]));
					if(isset($obDataMailBoxList->status->success))
					{
						$sucsess = 1;
						$rsUsers = CUser::GetList(($by="ID"), ($order="desc"), array("UF_PDD_MAILBOX"=>$value),array("ID", "NAME"));
						if($arUsers = $rsUsers->Fetch())
						{
							$user = new CUser;
							$fields = Array(
									"UF_PDD_MAILBOX"  => "",
							);
							$user->Update($arUsers["ID"], $fields);
						}
					}
				}
			}
			else 
			{
				$arrMailBoxList=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/get_domain_users.xml?on_page=1000&token=".$sToken));
				$selectEmailName = $arrMailBoxList->domains->domain->emails;
				foreach($selectEmailName->email as $keyEmail=>$value)
				{
					$obDataMailBoxList=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/api/del_user.xml?token=".$sToken."&domain=".$obDataMailBoxLock->domain->name."&login=".$value->name));
					if(isset($obDataMailBoxList->status->success))
					{
						$sucsess = 1;
						$userBox = $value->name."@".$obDataMailBoxLock->domain->name;
						$rsUsers = CUser::GetList(($by="ID"), ($order="desc"), array("UF_PDD_MAILBOX"=>$userBox),array("ID", "NAME"));
						if($arUsers = $rsUsers->Fetch())
						{
							
							$user = new CUser;
							$fields = Array(
									"UF_PDD_MAILBOX"  => "",
							);
							$user->Update($arUsers["ID"], $fields);
						}
					}	
				}
					
				
			}
			
		}
	
		return $sucsess;
	}
	
	function GroupDeleteMailBox($allMailBox, $domain, $allI, $thisI=0)
	{
		$stepCount = COption::GetOptionInt("lol.pdd", "yandex_request", 5);
	
		for($i=$thisI*$stepCount; $i<$allI*$stepCount, $i<$thisI*$stepCount+$stepCount; $i++)
		{
			if($allMailBox[$i]!="")
			{
				$arrMailBox[] = $allMailBox[$i];
			}
				
		}
	
		$arTokens=self::GetTokens();
		$arDomains=array();
		foreach($arTokens as $sToken)
		{	
			foreach ($arrMailBox as $userId)
				{
					$rsUsers = CUser::GetByID(intval($userId));
					$arUsers = $rsUsers->Fetch();
				
					$box = explode("@",$arUsers["UF_PDD_MAILBOX"]);
					$obDataMailBoxList=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/api/del_user.xml?token=".$sToken."&domain=".$box[1]."&login=".$box[0]));
					if(isset($obDataMailBoxList->status->success))
					{
						$user = new CUser;
						$fields = Array(
								"UF_PDD_MAILBOX"  => "",
						);
						$user->Update($arUsers["ID"], $fields);
						
					}
				}
			
		}
		
			$success["ok"] = true;
			$success["result"] = array(	"allI" =>$allI,
										"thisI" =>$thisI);
		
		
		return $success;
	}
	
	
	
	
	function RegUser($domain, $login, $pass, $userId)
	{
		$arTokens=self::GetTokens();
		$arDomains=array();
		$selectDomains=array();
		foreach($arTokens as $sToken)
		{
			$obData=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/api/reg_user.xml?token=".$sToken."&domain=".$domain."&login=".$login."&passwd=".$pass));
			$selectDomains = self::ConvertEncoding($obData->status);
			
				$error = strval(self::ConvertEncoding($obData->status->error));
					
		}
		if($error=="")
		{
		
			$user = new CUser;
			$user->Update($userId, array("UF_PDD_MAILBOX"=>$login."@".$domain));
				
			$rsUsers = CUser::GetByID($userId);
			$arUsers = $rsUsers->Fetch();
			
			$arEventFields = array(
					'LOGIN_PDD' => $login,
					'DOMAIN_PDD' => $domain,
					'PASSWORD_PDD' => $pass,
					'LINK_PDD' => "http://mail.yandex.ru/for/".$domain,
					'EMAIL' => $arUsers['EMAIL']
			
			);
				
			CEvent::Send("ADD_MAILBOX_PDD", $userId, $arEventFields);
			return true;
		}
		return $error;
		
	}
	
	function GetUserInfo($login)
	{
		$arTokens=self::GetTokens();
		$arDomains=array();
		$selectDomains=array();
		foreach($arTokens as $sToken)
		{
			$obData=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/get_user_info.xml?token=".$sToken."&login=".$login));
			
			$arUserInfo = array(
					"LOGIN"=>self::ConvertEncoding($obData->domain->user->login),
					"FNAME"=>self::ConvertEncoding($obData->domain->user->fname),
					"INAME"=>self::ConvertEncoding($obData->domain->user->iname),
					"SEX"=>self::ConvertEncoding($obData->domain->user->sex),
					"HINTQ"=>self::ConvertEncoding($obData->domain->user->hintq),
					"HINTA"=>self::ConvertEncoding($obData->domain->user->hinta),
					"DOMAIN"=>self::ConvertEncoding($obData->domain->name),
			
			);
	
		}
		return $arUserInfo;
	
	}	
	function EditUserInfo($login, $fname, $iname, $sex, $pass)
	{
		$arTokens=self::GetTokens();
		$arDomains=array();
		$arInfo = array();
		$selectDomains=array();
		$result = true;
		foreach($arTokens as $sToken)
		{
			
			if($pass===0)
			{
				$obData=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/edit_user.xml?token=".$sToken."&login=".$login."&iname=".$iname."&fname=".$fname."&sex=".$sex));
				
			}
			else 
			{
				$obData=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/edit_user.xml?token=".$sToken."&login=".$login."&password=".$pass."&iname=".$iname."&fname=".$fname."&sex=".$sex));
			}
			if(isset($obData->error))
			{
				$result = false;
			}
		}
		return $result;
	
	}
	
	function CreateGroupMailbox($arUsersId, $domain, $allI, $thisI=0)
	{
		
		$stepCount = COption::GetOptionInt("lol.pdd", "yandex_request", 5);
		
		for($i=$thisI*$stepCount; $i<$allI*$stepCount, $i<$thisI*$stepCount+$stepCount; $i++)
		{
			if($arUsersId[$i]!="")
			{
				$usersId[] = $arUsersId[$i];
			}
				
		}
		$arTokens=self::GetTokens();
		$arDomains=array();
		$selectDomains=array();
		foreach($arTokens as $sToken)
		{
				foreach ($usersId as $userId)
				{
					$rsUsers = CUser::GetByID(intval($userId));
					$arUsers = $rsUsers->Fetch();
					if($arUsers["UF_PDD_MAILBOX"])
					{
						continue;
					}
					$login = TOLower(preg_replace ("/[^a-zA-ZА-Яа-я0-9\s]/","",$arUsers["LOGIN"]));
					$pass = randString(7, array("abcdefghijklnmopqrstuvwxyz","ABCDEFGHIJKLNMOPQRSTUVWXYZ","0123456789"));
					
					$obData=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/api/reg_user.xml?token=".$sToken."&domain=".$domain."&login=".$login."&passwd=".$pass));
					if(strval(self::ConvertEncoding($obData->status->error)))
					{
						
						$error[$arUsers["ID"]] = strval(self::ConvertEncoding($obData->status->error));
					}

					if(!$error[$arUsers["ID"]])
					{
						$user = new CUser;
						$user->Update($arUsers["ID"], array("UF_PDD_MAILBOX"=>$login."@".$domain));
						
						$arEventFields = array(
								'LOGIN_PDD' => $login,
								'DOMAIN_PDD' => $domain,
								'PASSWORD_PDD' => $pass,
								'LINK_PDD' => "http://mail.yandex.ru/for/".$domain,
								'EMAIL' => $arUsers['EMAIL']
					
								);
						
							CEvent::Send("ADD_MAILBOX_PDD", $arUsers['LID'], $arEventFields);
						
						
					}
					elseif($error[$arUsers["ID"]]=="occupied")
					{
						unset($error[$arUsers["ID"]]);
						$login = $login.rand(1,99);
						$obData=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/api/reg_user.xml?token=".$sToken."&domain=".$domain."&login=".$login."&passwd=".$pass));
						
						if(strval(self::ConvertEncoding($obData->status->error)))
								{
									$error[$arUsers["ID"]] = strval(self::ConvertEncoding($obData->status->error));
								}
						
						if(!$error[$arUsers["ID"]])
						{
							$user = new CUser;
							$user->Update($arUsers["ID"], array("UF_PDD_MAILBOX"=>$login."@".$domain));
							
							$arEventFields = array(
									'LOGIN_PDD' => $login,
									'DOMAIN_PDD' => $domain,
									'PASSWORD_PDD' => $pass,
									'LINK_PDD' => "http://mail.yandex.ru/for/".$domain,
									'EMAIL' => $arUsers['EMAIL']
							
							);
								
							CEvent::Send("ADD_MAILBOX_PDD", $arUsers['LID'], $arEventFields);
						}
					}
				}
			
			$success["ok"] = true;
			$success["result"] = array("allI" =>$allI, "thisI" =>$thisI);
			return $success;
		
		}
	
	}
	
	function GetMailInfo() 
	{
		if($_SESSION['USE_PDD_MAILBOX'])
			{
				$arTokens=self::GetTokens();
				$arDomains=array();
				$selectDomains=array();
				foreach($arTokens as $sToken)
				{
					$mailBox = $_SESSION['PDD_MAILBOX'];
					$box = explode("@",$mailBox);
					$obData=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/get_mail_info.xml?token=".$sToken."&login=".$box[0]));
					$countMsg = intval($obData->ok['new_messages'][0]);
					return $countMsg;
				}		
			}
	}
	
	function UserOauthToken()
	{
		if($_SESSION['USE_PDD_MAILBOX'])
		{
			$arTokens=self::GetTokens();
			$arDomains=array();
			$selectDomains=array();
			foreach($arTokens as $sToken)
			{
				global $USER;
				$rsUsers = CUser::GetByID($USER->GetID());
				$arUsers = $rsUsers->Fetch();
				$mailBox = $arUsers["UF_PDD_MAILBOX"];
				$box = explode("@",$mailBox);
				$obData=simplexml_load_string(file_get_contents("https://pddimp.yandex.ru/api/user_oauth_token.xml?token=".$sToken."&domain=".$box[1]."&login=".$box[0]));
				
				$accessToken = (array)self::ConvertEncoding($obData->domains->domain->email->{'oauth-token'});
				
				return $accessToken[0];
			}
		}
	}
	
	
	
	function InitJS()
	{
		CJSCore::Init(array("jquery", "core_ls"));
		global $APPLICATION;
		global $USER;
		if(!$_SESSION['USE_PDD_MAILBOX'])
		{
			$rsUsers = CUser::GetByID($USER->GetID());
			$arUsers = $rsUsers->Fetch();
			
			$arUsers["UF_PDD_MAILBOX"];
			if($arUsers["UF_PDD_MAILBOX"])
			{
				$_SESSION['USE_PDD_MAILBOX'] = true;
				$_SESSION['PDD_MAILBOX'] = $arUsers["UF_PDD_MAILBOX"];
			}
		}
		if($_SESSION['PDD_MAILBOX'])
		{
			$APPLICATION->AddHeadScript("/bitrix/js/lol.pdd/script.js");
		}
	}
	
	function ConvertEncoding($data)
	{
		if(defined("BX_UTF"))
			return $data;
		if(is_array($data) || is_object($data))
		{
			foreach($data as $k=>$v)
			{
				$data[$k]=self::ConvertEncoding($v);
			}
			return $data;
		}
		else
			return utf8win1251($data);
	}
	
}


?>