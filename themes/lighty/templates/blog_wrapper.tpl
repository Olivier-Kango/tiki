<div class="panel postbody clearfix">
    <a id="postId{$post_info.postId}"></a>
    {include file='blog_post_postbody_title.tpl'}
    {include file='blog_post_postbody_content.tpl'}
    {if $blog_post_context neq 'excerpt' or $blog_post_context neq 'view_blog'}
        {if $blog_post_context neq 'print'}
            <footer class="postfooter card-footer clearfix">
                {* Copyright display is being turned off if being called through the "BLOG" plugin with "simple" mode turned off and a max character count supplied (preview mode). If in preview mode end user most likely is wanting to conserve space with a smaller display of information *}
                {if $blog_post_context neq 'plugin_preview'}
                    {capture name='copyright_section'}
                        {include file='show_copyright.tpl' copyright_context="blogpost"}
                    {/capture}
                    {* When copyright section is not empty show it *}
                    {if $smarty.capture.copyright_section neq ''}
                        <div class="form-text">
                            {$smarty.capture.copyright_section}
                        </div>
                    {/if}
                {/if}
                {include file='blog_post_actions.tpl'}
                {include file='blog_post_author_info.tpl'}
                {include file='freetag_list.tpl'}
                {include file='blog_post_status.tpl'}
                {include file='blog_post_navigation.tpl'}
            </footer>
        {else}
            {* Show copyright information in print view *}
            {if $blog_post_context neq 'plugin_preview'}
                {capture name='copyright_section'}
                    {include file='show_copyright.tpl' copyright_context="blogpost"}
                {/capture}
                {* When copyright section is not empty show it *}
                {if $smarty.capture.copyright_section neq ''}
                    <div class="form-text">
                        {$smarty.capture.copyright_section}
                    </div>
                {/if}
            {/if}
        {/if}
    {/if}
</div>
