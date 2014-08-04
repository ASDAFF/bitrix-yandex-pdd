function pddSetCounter(msg)
{
	if(parseInt(msg)>0)
	{
		$("#bx-notifier-panel .bx-notifier-mail .bx-notifier-indicator-count").text(msg);		
	}
	else
	{
		$("#bx-notifier-panel .bx-notifier-mail .bx-notifier-indicator-count").text("");
	}
}

function pddAjaxMsg()
{
	$.ajax({
		type: "POST",
		url: "/bitrix/admin/lol_pdd_msg.php",
		data: "action=info",
		success: function(msg)
		{
			if(BX.browser.SupportLocalStorage())
			{		
				BX.localStorage.set("pddGotMsgNum", msg, 60);
				pddSetCounter(msg);
			}	
			else
			{
				pddSetCounter(msg);
			}
		}
	});
}


function getMsgPDD()
{
	$("#bx-notifier-panel .bx-notifier-mail").css("display", "inline-block");
	if(BX.browser.SupportLocalStorage())
	{
		var gotMsg = BX.localStorage.get("pddGotMsg");
		if(gotMsg)
		{
			pddSetCounter(BX.localStorage.get("pddGotMsgNum"));
		}
		else
		{
			BX.localStorage.set("pddGotMsg", 1, 60);
			pddAjaxMsg();
		}
	}
	else
	{
		pddAjaxMsg();
	}
}
 
function pddStorageSet(params)
{
	if (params.key == "pddGotMsgNum")
	{
		pddSetCounter(params.value);
	}	
}

$(function(){
	
	if (BX.browser.SupportLocalStorage())
	{
		BX.addCustomEvent(window, "onLocalStorageSet", pddStorageSet);
	}

	BX.addCustomEvent(window, "onImUpdateCounterMail", getMsgPDD);
	 
	if(BX.browser.SupportLocalStorage())
	{
		setInterval('getMsgPDD()', 60000);
	}
	else
	{
	 	setInterval('getMsgPDD()', 600000);
	}

	$("#bx-notifier-panel .bx-notifier-mail").click(function(evt){
		evt.preventDefault();
		$.ajax({
			type: "POST",
			url: "/bitrix/admin/lol_pdd_msg.php",
			data: "action=reg",
			success: function(data)
			{
				window.location = "http://passport.yandex.ru/passport?mode=oauth&type=trusted-pdd-partner&error_retpath="+window.location.href+"&access_token="+data;		   
			}
		});
	 });
 });