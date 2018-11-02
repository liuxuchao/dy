<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $lang['install_error_title'];?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="styles/install.css" rel="stylesheet" type="text/css" />
</head>

<body>
<?php include ROOT_PATH . 'install/templates/header.php';?>
    <div class="wrapper">
    	<div class="w1000">
        	<div class="install_end">
            	<div class="end_left"><img src="./images/fail.png" /></div>
                <div class="end_right">
                        <h1><?php if($exists == 1){echo $lang['install_done_title'];}else{echo $lang['install_error_title'];}?></h1>
                    <span></span>
                    <p><?php echo $err_msg;?></p>
                </div>
            </div>
        </div>
        <div class="footer">
            <?php include ROOT_PATH . 'install/templates/copyright.php';?>
        </div>
    </div>
</body>
</html>