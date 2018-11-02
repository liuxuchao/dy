<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang['checking_title'];?></title>
<link href="styles/install.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/transport_jquery.js"></script>
<script type="text/javascript" src="js/common.js"></script>
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
                    <div class="step curr">
                    	<div class="warp">
                        	<i>2</i>
                        	<span>检查环境</span>
                        </div>
                    </div>
                    <div class="step">
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
<script type="text/javascript">
Ajax.call('cloud.php?step=check','', check_api, 'GET', 'TEXT','FLASE');
Ajax.call('cloud.php?step=right_ad','', right_ad_api, 'GET', 'TEXT','FLASE');
Ajax.call('cloud.php?step=update_mend','', update_mend_api, 'GET', 'TEXT','FLASE');
function check_api(result)
{
  if(result)
  {
    setInnerHTML('zoome',result);
    setInputCheckedStatus();

    $("js-pre-step").onclick = function() {
        location.href="./index.php?lang=" + getAddressLang() + "&step=welcome";
    };
    $("js-recheck").onclick = function () {
        location.href="./index.php?lang=" + getAddressLang() + "&step=check";
    };
    $("js-submit").onclick = function () {
        this.form.action="index.php?lang=" + getAddressLang() + "&step=setting_ui" + "&ui=" + $('userinterface').value;
    };
  }
}

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
</script>
</body>
</html>