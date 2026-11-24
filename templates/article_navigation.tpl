{if !empty($article_navigation.previous) or !empty($article_navigation.next)}
    <nav class="article-navigation" aria-label="{tr}Article navigation{/tr}">
        {if !empty($article_navigation.previous)}
            <a rel="prev" href="{$article_navigation.previous.articleId|sefurl:'article'|escape}">
                <span><span aria-hidden="true">←</span> {tr}Previous{/tr}</span>
                <span class="article-navigation-title">{$article_navigation.previous.title|escape}</span>
            </a>
        {/if}
        {if !empty($article_navigation.next)}
            <a rel="next" href="{$article_navigation.next.articleId|sefurl:'article'|escape}">
                <span>{tr}Next{/tr} <span aria-hidden="true">→</span></span>
                <span class="article-navigation-title">{$article_navigation.next.title|escape}</span>
            </a>
        {/if}
    </nav>
{/if}
