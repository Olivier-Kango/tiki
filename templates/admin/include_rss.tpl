{remarksbox type="tip" title="{tr}Tip{/tr}"}
	{tr}Use this page to configure the feeds generated by Tiki. To syndicate feeds from other sites, use the <strong>Admin</strong> &gt; <strong>External Feeds</strong> menu option, or{/tr} <a class="rbox-link" href="tiki-admin_rssmodules.php" title="{tr}External feeds{/tr}">{tr}Click Here{/tr}</a>.
{/remarksbox}

<form action="tiki-admin.php?page=rss" method="post">
	<input type="hidden" name="rss" />
    <div class="row">
        <div class="form-group col-lg-12 clearfix">
            <div class="pull-right">
                <input type="submit" class="btn btn-default btn-sm" value="{tr}Change preferences{/tr}">
            </div>
        </div>
    </div>

	{tabset name="admin_rss"}
		{tab name="{tr}General Preferences{/tr}"}
			{preference name=feed_default_version}
			<div class="adminoptionboxchild feed_default_version_childcontainer 5">
				{preference name=feed_atom_author_name}
				{preference name=feed_atom_author_email}
				{preference name=feed_atom_author_url}
			</div>
			<div class="adminoptionboxchild feed_default_version_childcontainer 2">
				{preference name=feed_rss_editor_email}
				{preference name=feed_rss_webmaster_email}
			</div>

			{preference name=feed_img}
			{preference name=feed_language}
			{preference name=feed_cache_time}

			{preference name=feed_basic_auth}
		{/tab}

		{tab name="{tr}Feeds{/tr}"}
			{preference name=feed_articles}
			<div class="adminoptionboxchild" id="feed_articles_childcontainer">
				<a href="tiki-articles_rss.php" title="{tr}Feed for articles{/tr}"> {icon _id=feed}</a>
				{preference name=feed_articles_title}
				{preference name=feed_articles_desc}
				{preference name=feed_articles_max}
				{preference name=feed_articles_showAuthor}
				{preference name=feed_articles_homepage}
			</div>

			{preference name=feed_blogs}
			<div class="adminoptionboxchild" id="feed_blogs_childcontainer">
				<a href="tiki-blogs_rss.php" title="{tr}Feed for blogs{/tr}"> {icon _id=feed}</a>
				{preference name=feed_blogs_title}
				{preference name=feed_blogs_desc}
				{preference name=feed_blogs_max}
				{preference name=feed_blogs_showAuthor}
				{preference name=feed_blogs_homepage}
			</div>

			{preference name=feed_blog}
			<div class="adminoptionboxchild" id="feed_blog_childcontainer">
				<a href="tiki-list_blogs.php" title="{tr}Feed for individual blogs{/tr}"> {icon _id=feed}</a>
				{preference name=feed_blog_title}
				{preference name=feed_blog_desc}
				{preference name=feed_blog_max}
				{preference name=feed_blog_showAuthor}
				{preference name=feed_blog_homepage}
			</div>

			{preference name=feed_image_galleries}
			<div class="adminoptionboxchild" id="feed_image_galleries_childcontainer">
				<a href="tiki-image_galleries_rss.php" title="{tr}Feed for image galleries{/tr}"> {icon _id=feed}</a>
				{preference name=feed_image_galleries_title}
				{preference name=feed_image_galleries_desc}
				{preference name=feed_image_galleries_max}
				{preference name=feed_image_galleries_showAuthor}
				{preference name=feed_image_galleries_homepage}
			</div>

			{preference name=feed_image_gallery}
			<div class="adminoptionboxchild" id="feed_image_gallery_childcontainer">
				<a href="tiki-galleries.php" title="{tr}Feed for individual image galleries{/tr}"> {icon _id=feed}</a>
				{preference name=feed_image_gallery_title}
				{preference name=feed_image_gallery_desc}
				{preference name=feed_image_gallery_max}
				{preference name=feed_image_gallery_showAuthor}
				{preference name=feed_image_gallery_homepage}
			</div>

			{preference name=feed_file_galleries}
			<div class="adminoptionboxchild" id="feed_file_galleries_childcontainer">
				<a href="tiki-file_galleries_rss.php" title="{tr}Feed for file galleries{/tr}"> {icon _id=feed}</a>
				{preference name=feed_file_galleries_title}
				{preference name=feed_file_galleries_desc}
				{preference name=feed_file_galleries_max}
				{preference name=feed_file_galleries_showAuthor}
				{preference name=feed_file_galleries_homepage}
			</div>

			{preference name=feed_file_gallery}
			<div class="adminoptionboxchild" id="feed_file_gallery_childcontainer">
				<a href="tiki-list_file_gallery.php" title="{tr}Feed for individual file galleries{/tr}"> {icon _id=feed}</a>
				{preference name=feed_file_gallery_title}
				{preference name=feed_file_gallery_desc}
				{preference name=feed_file_gallery_max}
				{preference name=feed_file_gallery_showAuthor}
				{preference name=feed_file_gallery_homepage}
			</div>

			{preference name=feed_wiki}
			<div class="adminoptionboxchild" id="feed_wiki_childcontainer">
				<a href="tiki-wiki_rss.php" title="{tr}Feed for wiki pages (with diff){/tr}"> {icon _id=feed}</a>
				<a href="tiki-wiki_rss_no_diff.php" title="{tr}Feed for wiki pages (no diff){/tr}"> {icon _id=feed}</a>
				{preference name=feed_wiki_title}
				{preference name=feed_wiki_desc}
				{preference name=feed_wiki_max}
				{preference name=feed_wiki_showAuthor}
				{preference name=feed_wiki_homepage}
			</div>

			{preference name=feed_forums}
			<div class="adminoptionboxchild" id="feed_forums_childcontainer">
				<a href="tiki-forums_rss.php" title="{tr}Feed for forums{/tr}"> {icon _id=feed}</a>
				{preference name=feed_forums_title}
				{preference name=feed_forums_desc}
				{preference name=feed_forums_max}
				{preference name=feed_forums_showAuthor}
				{preference name=feed_forums_homepage}
			</div>
			
			{preference name=feed_forum}
			<div class="adminoptionboxchild" id="feed_forum_childcontainer">
				<a href="tiki-forums.php" title="{tr}Feed for individual forums{/tr}"> {icon _id=feed}</a>
				{preference name=feed_forum_title}
				{preference name=feed_forum_desc}
				{preference name=feed_forum_max}
				{preference name=feed_forum_showAuthor}
				{preference name=feed_forum_homepage}
			</div>

			{preference name=feed_tracker}
			<div class="adminoptionboxchild" id="feed_tracker_childcontainer">
				<a href="tiki-list_trackers.php" title="{tr}Feed for trackers{/tr}"> {icon _id=feed}</a>
				{preference name=feed_tracker_title}
				{preference name=feed_tracker_desc}
				{preference name=feed_tracker_max}
				{preference name=feed_tracker_showAuthor}
				{preference name=feed_tracker_homepage}
				{preference name=feed_tracker_labels}
			</div>

			{preference name=feed_calendar}
			<div class="adminoptionboxchild" id="feed_calendar_childcontainer">
				<a href="tiki-calendars_rss.php" title="{tr}Feed for calendars{/tr}"> {icon _id=feed}</a>
				{preference name=feed_calendar_title}
				{preference name=feed_calendar_desc}
				{preference name=feed_calendar_max}
				{preference name=feed_calendar_showAuthor}
				{preference name=feed_calendar_homepage}
			</div>
			
			{preference name=feed_directories}
			<div class="adminoptionboxchild" id="feed_directories_childcontainer">
				<a href="tiki-directories_rss.php" title="{tr}Feed for directories{/tr}"> {icon _id=feed}</a>
				{preference name=feed_directories_title}
				{preference name=feed_directories_desc}
				{preference name=feed_directories_max}
				{preference name=feed_directories_showAuthor}
				{preference name=feed_directories_homepage}
			</div>

			{preference name=feed_shoutbox}
			<div class="adminoptionboxchild" id="feed_shoutbox_childcontainer">
				<a href="tiki-shoutbox_rss.php" title="{tr}Feed for shoutbox{/tr}"> {icon _id=feed}</a>
				{preference name=feed_shoutbox_title}
				{preference name=feed_shoutbox_desc}
				{preference name=feed_shoutbox_max}
				{preference name=feed_shoutbox_showAuthor}
				{preference name=feed_shoutbox_homepage}
			</div>
		{/tab}
	{/tabset}

    <br>{* I cheated. *}
    <div class="row">
        <div class="form-group col-lg-12 text-center">
            <input type="submit" class="btn btn-default btn-sm" value="{tr}Change preferences{/tr}">
        </div>
    </div>
</form>
