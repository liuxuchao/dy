<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang['setting_title'];?></title>
<link href="styles/install.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/transport.js"></script>
<script type="text/javascript" src="js/common.js"></script>
<script type="text/javascript" src="js/draggable.js"></script>
<script type="text/javascript" src="js/setting.js"></script>
<script type="text/javascript">
var $_LANG = {};
<?php foreach($lang['js_languages'] as $key => $item): ?>
$_LANG["<?php echo $key;?>"] = "<?php echo $item;?>";
<?php endforeach; ?>
</script>
</head>
<body id="checking">
<?php include ROOT_PATH . 'install/templates/header.php';?>
    <div class="wrapper">
    	<div class="w1000">
        	<div class="attention" id="attention">
            	
            </div>
            <div class="content">
            	<div class="tab">
                	<div class="step">
                    	<div class="warp">
                        	<i>1</i>
                        	<span>欢迎使用</span>
                        </div>
                    </div>
                    <div class="step">
                    	<div class="warp">
                        	<i>2</i>
                        	<span>检查环境</span>
                        </div>
                    </div>
                    <div class="step curr">
                    	<div class="warp">
                        	<i>3</i>
                        	<span>配置系统</span>
                        </div>
                    </div>
                </div>
                <div class="zoome" id="zoome">
                
                </div>
                <div class="right" id="right_ad">
                	
                </div>
            </div>
        </div>
        <div class="footer">
     		<?php include ROOT_PATH . 'install/templates/copyright.php';?></div>
		</div>
    </div>
<div class="loading" id="js-monitor" style="display:none;">
            <div class="loading-mask"></div>
            <div class="loading-content" id="loading-content">
                <div class="loading-top">
                    <div class="tit" id="js-monitor-title">安装程序监测器</div>
                    <div class="close" id="js-monitor-close"></div>
                </div>
                <div class="loading-warp">
                    <div class="img"><img id="js-monitor-loading" src="images/load.gif" /></div>
                    <div class="desc" id="js-monitor-wait-please"></div>
                    <div class="desc" id="js-notice">快速正在安装中，请稍后......</div>
                </div>
                <div class="loading-bottom"></div>
            </div>
        
    	</div>
<script type="text/javascript">
function check_mobile_code()
{
	var f = $("js-setting");
	var mobile=f["mobile"].value;
	var mobile_code=f["mobile_code"].value;
	/*if(mobile.length==0)
	{
		alert($_LANG['emptymobile']);
		return false;	
	}
	else if(mobile_code.length==0)
	{
		alert($_LANG['emptymobile_code']);
		return false;
	}
	else
	{
		Ajax.call('cloud.php?step=check_code','mobile='+mobile+'&mobile_code='+mobile_code,function check_code(data){
				if(data.error){
					alert(data.content);
				}
				else{
					install();
				}
			}, 'POST', 'JSON','FLASE');
	}*/
	install();
}
Ajax.call('cloud.php?step=setting_ui','', setting_ui_api, 'GET', 'TEXT','FLASE');
Ajax.call('cloud.php?step=right_ad','', right_ad_api, 'GET', 'TEXT','FLASE');
Ajax.call('cloud.php?step=update_mend','', update_mend_api, 'GET', 'TEXT','FLASE');
function right_ad_api(result)
{
  if(result)
  {
    setInnerHTML('right_ad',result);
  }
}
function update_mend_api(result)
{
  if(result)
  {
    setInnerHTML('attention',result);
  }
}
function setting_ui_api(result)
{
  if(result)
  {
    setInnerHTML('zoome',result);
    setInputCheckedStatus();
    var f = $("js-setting");

    //f.setAttribute("action", "javascript:check_mobile_code();void 0;");
	
	f.setAttribute("action", "javascript:install();");

    f["js-db-name"].onblur = function () {
        var list = getDbList();
        for (var i = 0; i < list.length; i++) {
            if (f["js-db-name"].value === list[i]) {
                var answer = confirm($_LANG["db_exists"]);
                if (answer === false) {
                    f["js-db-name"].value = "";
                }
            }
        }
    }
    f["js-admin-password"].onblur = function  () {
            var password = f['js-admin-password'].value;
            var confirm_password = f['js-admin-password2'].value;
            if (!(password.length >= 8 && /\d+/.test(password) && /[a-zA-Z]+/.test(password)))
            {
                $("js-install-at-once").setAttribute("disabled", "true");
                if (!(password.length >= 8)){
                    $("js-admin-password-result").innerHTML="<span class='comment'><img src='images\/no.gif'>"+$_LANG["password_short"]+"<\/span>";
                }
                else
                {
                    $("js-admin-password-result").innerHTML="<span class='comment'><img src='images\/no.gif'>"+$_LANG["password_invaild"]+"<\/span>";
                }
            }
            else
            {
                $("js-admin-password-result").innerHTML="<img src='images\/yes.gif'>";
                if (password==confirm_password)
                {
                    $("js-install-at-once").removeAttribute("disabled");
                    $("js-admin-confirmpassword-result").innerHTML="<img src='images\/yes.gif'>";
                }
                else
                {
                    $("js-install-at-once").setAttribute("disabled", "true");
                    if (confirm_password!='')
                    {
                    $("js-admin-confirmpassword-result").innerHTML="<span class='comment'><img src='images\/no.gif'>"+$_LANG["password_not_eq"]+"<\/span>";
                    }
                }
            }
        }
    f["js-admin-password2"].onblur = function  () {
        var password = f['js-admin-password'].value;
        var confirm_password = f['js-admin-password2'].value;
        if (!(confirm_password.length >= 8 && /\d+/.test(confirm_password) && /[a-zA-Z]+/.test(confirm_password) && password==confirm_password))
        {
          $("js-install-at-once").setAttribute("disabled", "true");
            
          if (!(confirm_password.length >= 8)){
                    $("js-admin-confirmpassword-result").innerHTML="<span class='comment'><img src='images\/no.gif'>"+$_LANG["password_short"]+"<\/span>";
          }
          else
          {
                    if (password==confirm_password){
                        $("js-admin-confirmpassword-result").innerHTML="<span class='comment'><img src='images\/no.gif'>"+$_LANG["password_invaild"]+"<\/span>";
                    }
                    else
                    {
                        $("js-admin-confirmpassword-result").innerHTML="<span class='comment'><img src='images\/no.gif'>"+$_LANG["password_not_eq"]+"<\/span>";
                    }
          }
        }
        else
        {
            $("js-install-at-once").removeAttribute("disabled");
            $("js-admin-confirmpassword-result").innerHTML="<img src='images\/yes.gif'>";
        }
    }
    f["js-admin-password"].onkeyup = function () {
      var pwd = f['js-admin-password'].value;
      var Mcolor = "#FFF",Lcolor = "#FFF",Hcolor = "#FFF";
	  var Safety='pwd-strength';
      var m=0;

      var Modes = 0;
      for (i=0; i<pwd.length; i++)
      {
        var charType = 0;
        var t = pwd.charCodeAt(i);
        if (t>=48 && t <=57)
        {
          charType = 1;
        }
        else if (t>=65 && t <=90)
        {
          charType = 2;
        }
        else if (t>=97 && t <=122)
		{
			charType = 4;	
		}
        else
		{
			charType = 4;	
		}
        Modes |= charType;
      }

      for (h=0;h<4;h++)
      {
        if (Modes & 1) m++;
          Modes>>>=1;
      }

      if (pwd.length<=4)
      {
        m = 1;
      }

      switch(m)
      {
        case 1 :
          Safety = "pwd-strength weak";
        break;
        case 2 :
          Safety = "pwd-strength middle";
        break;
        case 3 :
          Safety = "pwd-strength strong";
        break;
        case 4 :
          Safety = "pwd-strength strong";
        break;
        default :
        break;
      }
      if(document.getElementById("Safety_style"))
      {
         document.getElementById("Safety_style").className  = Safety;
      }


    }
    f["js-db-list"].onfocus = displayDbList;

    $("js-monitor-close").onclick = function () {
        $("js-monitor").style.display = "none";
        unlockSpecInputs();
    };
	
	 //$("send_mobile_code").onclick = function () {
//        	var mobile=f["mobile"].value;
//			var mobile_code=f["mobile_code"].value;
//			if(mobile.length==0)
//			{
//				alert($_LANG['emptymobile']);
//				return false;
//			}
//			else
//			{
//				var reg = /^0?1[3|4|5|8][0-9]\d{8}$/;
//				if(!reg.test(mobile))
//				{
//					alert($_LANG['mobile_error']);
//					return false;
//				}
//				else
//				{
//					Ajax.call('cloud.php?step=send_code','mobile='+mobile,function check_code(data){
//						if(!data.error){
//							alert(data.content);
//						}
//						else
//						{
//							alert(data.content);
//							return false;
//						}
//					}, 'POST', 'JSON','TRUE');
//					$("send_mobile_code").disabled=true;
//					var i=60;
//					var initime=window.setInterval(function () {
//						var msgval="("+i+")秒";
//						if(i==0)
//						{
//							$("send_mobile_code").value="发送验证码";	
//							$("send_mobile_code").disabled=false;
//							window.clearInterval(initime);
//						}
//						else
//						{
//							$("send_mobile_code").value=msgval;	
//						}
//						i-=1;
//
//					}, 1000);
//				}
//				
//			}
//	};

   // var detail = $("js-monitor-view-detail")
//    detail.innerHTML = $_LANG["display_detail"];
//    detail.onclick = function () {
//        var mn = $("js-monitor-notice");
//        if (mn.style.display === "block") {
//            mn.style.display = "none"
//            this.innerHTML = $_LANG["display_detail"];
//        } else {
//            mn.style.display = "block"
//            this.innerHTML = $_LANG["hide_detail"];
//        }
//    };
//alert(1);
    //iframe = frames['js-monitor-notice'];
    notice = $("js-notice");
    var d = new Draggable();
    d.bindDragNode("js-monitor", "js-monitor-title");

    $("js-system-lang-" + getAddressLang()).setAttribute("checked", "checked");

    $("js-pre-step").onclick = function () {
        location.href = "./index.php?lang=" + getAddressLang() + "&step=check";
    };

    f["js-install-demo"].onclick = switchInputsStatus;
	
		var winHeight =window.innerHeight;
		var winWidth =window.innerWidth;
		var top = (winHeight-310)/2 +'px';
		var left =(winWidth-520)/2 +'px';
		
		var loading=$("loading-content");
		
		loading.style.top=top;
		loading.style.left=left;

  }
}
</script>
</body>
</html>
