<?
IncludeModuleLangFile(__FILE__);
if(
	CModule::IncludeModule('lol.pdd')
	&& $APPLICATION->GetGroupRight("lol.pdd") == "W"
)
{
	$aMenu = Array(
		array(
			"parent_menu" => "global_menu_services",
			"sort" => 100,
			"text" => GetMessage("MENU_LOL_PDD"),
			"title"=>GetMessage("MENU_LOL_PDD_TITLE"),
			"url" => "lol_pdd_domains.php?lang=".LANGUAGE_ID."&amp;set_default=Y",
			"more_url" => array("lol_pdd_domains.php"),
			"icon" => "lol_pdd_menu_icon",
			"page_icon" => "lol_pdd_page_icon",
			"items_id" => "menu_lol_pdd",
			"items" => array(
				array(
					"text" => GetMessage("MENU_LOL_PDD_DOMAINS"),
					"title"=>GetMessage("MENU_LOL_PDD_DOMAINS_TITLE"),
					"url" => "lol_pdd_domains.php?lang=".LANGUAGE_ID."&amp;set_default=Y",
					"more_url"=>array("lol_pdd_domains.php"),
				),
				array(
					"text" => GetMessage("MENU_LOL_PDD_MAILBOX"),
					"title"=>GetMessage("MENU_LOL_PDD_MAILBOX_TITLE"),
					"url" => "lol_pdd_mailbox.php?lang=".LANGUAGE_ID."&amp;set_default=Y",
					"more_url"=>array("lol_pdd_mailbox.php"),
				),
				array(
					"text" => GetMessage("MENU_LOL_PDD_MAILBOX_USER"),
					"title"=>GetMessage("MENU_LOL_PDD_MAILBOX_USER_TITLE"),
					"url" => "lol_pdd_mailbox_user.php?lang=".LANGUAGE_ID."&amp;set_default=Y",
					"more_url"=>array("lol_pdd_mailbox_user.php"),
				),
			)
		),
	);
	return $aMenu;
}
return false;
?>
