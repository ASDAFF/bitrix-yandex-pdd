<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-18);
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

class lol_pdd extends CModule
{
  var $MODULE_ID = "lol.pdd";
  var $MODULE_VERSION;
  var $MODULE_VERSION_DATE;
  var $MODULE_NAME;
  var $MODULE_DESCRIPTION;
  var $MODULE_GROUP_RIGHTS = "Y";

  function lol_pdd()
  {
    $arModuleVersion = array();

    $path = str_replace("\\", "/", __FILE__);
    $path = substr($path, 0, strlen($path) - strlen("/index.php"));
    include($path."/version.php");

    if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
    {
      $this->MODULE_VERSION = $arModuleVersion["VERSION"];
      $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
    }

    $this->MODULE_NAME = GetMessage("PDD_MODULE_NAME");
    $this->MODULE_DESCRIPTION = GetMessage("PDD_MODULE_DESCRIPTION");
    
    $this->PARTNER_NAME = GetMessage("LOL_NAME"); 
    $this->PARTNER_URI = "http://web.lol.su";
    
  }

   
  function DoInstall()
  {
    global $DOCUMENT_ROOT, $APPLICATION, $errors;

    $errors = false;

	$FM_RIGHT = $APPLICATION->GetGroupRight("lol.pdd");
		
	if ($FM_RIGHT!="D")
	{
    	$this->InstallFiles();
    	$this->InstallDB();

    	$APPLICATION->IncludeAdminFile(GetMessage("PDD_INSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/step.php");
	}
  }

  function InstallFiles()
  {
  	global $APPLICATION;
  	
    CopyDirFiles(
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin", 
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin" 
      );

    CopyDirFiles(
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images", 
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID,
      true,
      true
      );
    
    CopyDirFiles(
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/themes", 
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes",
      true,
      true
      ); 
    CopyDirFiles(
	  $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js",
	  $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID,
	  true,
	  true
	  );
    
    $APPLICATION->SetFileAccessPermission("/bitrix/admin/lol_pdd_msg.php", array("*" => "R"));
    
    return true;
  }
  
  function InstallDB()
  {
    global $DB;
    
    RegisterModule($this->MODULE_ID);
    $this->CreatUserProperty();
    $this->CreatTypeMailEvent();
    $this->CreatTemplateMailEvent();
    
    RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "CLOLYandexPDD", "InitJS");
    
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lol.pdd/install/tasks/install.php");
    
    return true;
  }
  
  function DoUninstall()
  {
    global $DOCUMENT_ROOT, $APPLICATION;
   
	$FM_RIGHT = $APPLICATION->GetGroupRight("lol.pdd");
		
	if ($FM_RIGHT!="D")
	{
    	$this->UnInstallFiles();
    	$this->UnInstallDB();
    
    	$APPLICATION->IncludeAdminFile(GetMessage("PDD_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep.php");
	}
  }
  
  function UnInstallDB()
  {
  	global $DB;
  	
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lol.pdd/install/tasks/uninstall.php");
    
    UnRegisterModule($this->MODULE_ID);

    return true;
  }
  
  function UnInstallFiles()
  {
    DeleteDirFiles(
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin", 
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin" 
      );

    DeleteDirFilesEx(
      "/bitrix/images/".$this->MODULE_ID 
      );
      
    DeleteDirFiles(
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/themes", 
      $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes" 
      );   
      
    return true;
  }

	function GetModuleRightList()
	{
		$arr = array(
			"reference_id" => array("D","R","W"),
			"reference" => array(
				"[D] ".GetMessage("PDD_DENIED"),
				"[R] ".GetMessage("PDD_MAIL"),
				"[W] ".GetMessage("PDD_ADMIN"))
			);
		return $arr;
	}
	
	function CreatUserProperty()
	{
		$oUserTypeEntity    = new CUserTypeEntity();
		
		$aUserFields    = array(
				"ENTITY_ID"         => "USER",
				"FIELD_NAME"        => "UF_PDD_MAILBOX",
				"USER_TYPE_ID"      => "string",
				"XML_ID"            => "",
				"SORT"              => 500,
				"MULTIPLE"          => "N",
				"MANDATORY"         => "N",
				"SHOW_FILTER"       => "N",
				"SHOW_IN_LIST"      => "",
				"EDIT_IN_LIST"      => "",
				"IS_SEARCHABLE"     => "N",
				"SETTINGS"          => array(
						"DEFAULT_VALUE" => "",
						"SIZE"          => "20",
						"ROWS"          => "1",
						"MIN_LENGTH"    => "0",
						"MAX_LENGTH"    => "0",
						"REGEXP"        => "",
				),
				"EDIT_FORM_LABEL"   => array(
						"ru"    => GetMessage("PDD_PROP_NAME"),
				),
				"LIST_COLUMN_LABEL" => array(
						"ru"    => GetMessage("PDD_PROP_NAME"),
				),
				"LIST_FILTER_LABEL" => array(
						"ru"    => GetMessage("PDD_PROP_NAME"),
				),
				"ERROR_MESSAGE"     => array(
						"ru"    => GetMessage("PDD_PROP_ERROR"),
				),
				"HELP_MESSAGE"      => array(
						"ru"    => "",
				),
		);
		
		$iUserFieldId   = $oUserTypeEntity->Add( $aUserFields );
	}
	
	function CreatTypeMailEvent()
	{
		$oEventType = new CEventType();
		$oEventType->Add( array(
				"LID" => SITE_ID,
				"EVENT_NAME" => "ADD_MAILBOX_PDD",
				"NAME" => GetMessage("PDD_NAME_TYPE_MAIL_EVENT"),
				"DESCRIPTION"   => "#LOGIN_PDD# - ".GetMessage("PDD_LOGIN_PDD")."
							        #DOMAIN_PDD# - ".GetMessage("DOMAIN_PDD")."
							        #PASSWORD_PDD# - ".GetMessage("PASSWORD_PDD")."
							        #LINK_PDD# - ".GetMessage("LINK_PDD")."
									#EMAIL# - ".GetMessage("EMAIL")."
							        "
		) );
	}
	function CreatTemplateMailEvent()
	{
		$rsSites = CSite::GetList($by="sort", $order="desc", Array());
		while ($arSite = $rsSites->Fetch())
		{
			$oEventMessage  = new CEventMessage();
			$oEventMessage->Add( array(
					"ACTIVE"    => "Y",
					"EVENT_NAME"    => "ADD_MAILBOX_PDD",
					"LID"           => 	$arSite["ID"],
					"EMAIL_FROM"    => "#DEFAULT_EMAIL_FROM#",
					"EMAIL_TO"      => "#EMAIL#",
					"SUBJECT"       => GetMessage("CREATE_TEMPLATE_SUBJECT"),
					"MESSAGE"       => GetMessage("CREATE_TEMPLATE_MASSAGE"),
					"BODY_TYPE"     => "html",
			) );
		}
	}
}
?>