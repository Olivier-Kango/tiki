<?php
//Code to be executed before a join activity
// This activity needs an instance to be passed to 
// be started, so get the instance into $instance.
if(isset($_REQUEST['iid'])) {
  $instance->getInstance($_REQUEST['iid']);
} else {
  $smarty->assign('msg',tra("No instance indicated"));
  $smarty->display("styles/$style_base/error.tpl");
  die;  
}
if(isset($_REQUEST['iid'])&&isset($_REQUEST['activityId'])) {
  $instance->setActivityUser($_REQUEST['activityId'],$user);
}

?>
