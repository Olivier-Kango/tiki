<?php
// Initialization
require_once('tiki-setup.php');

if($feature_blogs != 'y') {
  $smarty->assign('msg',tra("This feature is disabled"));
  $smarty->display("styles/$style_base/error.tpl");
  die;  
}


// Now check permissions to access this page
if($tiki_p_blog_post != 'y') {
  $smarty->assign('msg',tra("Permission denied you cannot post"));
  $smarty->display("styles/$style_base/error.tpl");
  die;  
}

if(isset($_REQUEST["blogId"])) {
  $blogId = $_REQUEST["blogId"];
  $blog_data = $tikilib->get_blog($_REQUEST["blogId"]);
} else {
  $blogId = 0;
}
$smarty->assign('blogId',$blogId);

if(isset($_REQUEST["postId"])) {
  $postId = $_REQUEST["postId"];
} else {
  $postId = 0;
}
$smarty->assign('postId',$postId);

$smarty->assign('data','');
$smarty->assign('created',date("U"));
// If the articleId is passed then get the article data
if(isset($_REQUEST["postId"]) && $_REQUEST["postId"]>0) {
  // Check permission
  
   
  $data = $tikilib->get_post($_REQUEST["postId"]);
  
  // If the user owns the weblog then he can edit
  $blog_data = $tikilib->get_blog($data["blogId"]);
  if($user && $user==$blog_data["user"]) {
    $data["user"] = $user;
  } 
  
  if($data["user"]!=$user || !$user) {
    if($tiki_p_blog_admin != 'y') {
      $smarty->assign('msg',tra("Permission denied you cannot edit this post"));
      $smarty->display("styles/$style_base/error.tpl");
      die;  
    }
  }
  
  $smarty->assign('data',$data["data"]);
  $smarty->assign('created',$data["created"]);
  $smarty->assign('parsed_data',$tikilib->parse_data($data["data"]));
}

$smarty->assign('preview','n');
if(isset($_REQUEST["preview"])) {
  $data = $_REQUEST["data"];
  $parsed_data = $tikilib->parse_data($_REQUEST["data"]);

  if($blog_spellcheck == 'y') {
  if(isset($_REQUEST["spellcheck"])&&$_REQUEST["spellcheck"]=='on') {
  $parsed_data = $tikilib->spellcheckreplace($data,$parsed_data,$language,'blogedit');
  $smarty->assign('spellcheck','y');
  } else {
  $smarty->assign('spellcheck','n');
  }
  }

  $smarty->assign('data',$data);
  $smarty->assign('parsed_data',$parsed_data);
  $smarty->assign('preview','y');
}

if(isset($_REQUEST["save"])) {
  $smarty->assign('individual','n');
  if($userlib->object_has_one_permission($_REQUEST["blogId"],'blog')) {
    $smarty->assign('individual','y');
    if($tiki_p_admin != 'y') {
      // Now get all the permissions that are set for this type of permissions 'image gallery'
      $perms = $userlib->get_permissions(0,-1,'permName_desc','','blogs');
      foreach($perms["data"] as $perm) {
        $permName=$perm["permName"];
        if($userlib->object_has_permission($user,$_REQUEST["blogId"],'blog',$permName)) {
          $$permName = 'y';
          $smarty->assign("$permName",'y');
        } else {
          $$permName = 'n';
          $smarty->assign("$permName",'n');
        }
      }
    }
  }
  if($tiki_p_blog_admin == 'y') {
    $tiki_p_create_blogs = 'y';
    $smarty->assign('tiki_p_create_blogs','y');
    $tiki_p_blog_post = 'y';
    $smarty->assign('tiki_p_blog_post','y');
    $tiki_p_read_blog = 'y';
    $smarty->assign('tiki_p_read_blog','y');
  }

  if($tiki_p_blog_post != 'y') {
    $smarty->assign('msg',tra("Permission denied you cannot post"));
    $smarty->display("styles/$style_base/error.tpl");
    die;  
  }
  
  if($_REQUEST["postId"]>0) {
    $data = $tikilib->get_post($_REQUEST["postId"]);
    $blog_data = $tikilib->get_blog($data["blogId"]);
    if($user && $user==$blog_data["user"]) {
      $data["user"] = $user;
    } 
    if($data["user"]!=$user || !$user) {
      if($tiki_p_blog_admin != 'y') {
        $smarty->assign('msg',tra("Permission denied you cannot edit this post"));
        $smarty->display("styles/$style_base/error.tpl");
        die;  
      }
    }
  }

  $_REQUEST["data"] = $tikilib->capture_images($_REQUEST["data"]);
  if($_REQUEST["postId"]>0) {
    $tikilib->update_post($_REQUEST["postId"],$_REQUEST["data"],$user);
  } else {
    $tikilib->blog_post($_REQUEST["blogId"],$_REQUEST["data"],$user);
  }
  $links = $tikilib->get_links($_REQUEST["data"]);
  $tikilib->cache_links($links);
  header("location: tiki-view_blog.php?blogId=".$_REQUEST["blogId"]);
  die;
}
$blogs = $tikilib->list_user_blogs($user,1);
if(count($blogs)==0) {
  $smarty->assign('msg',tra("You can't post in any blog maybe you have to create a blog first"));
  $smarty->display("styles/$style_base/error.tpl");
  die;  
}
$smarty->assign_by_ref('blogs',$blogs);
$section='blogs';
include_once('tiki-section_options.php');


// Display the Index Template
$smarty->assign('mid','tiki-blog_post.tpl');
$smarty->assign('show_page_bar','n');
$smarty->display("styles/$style_base/tiki.tpl");
?>