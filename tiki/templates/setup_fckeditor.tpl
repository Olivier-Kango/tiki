var _TikiPath = '{$tikipath}' ;
var _TikiRoot = '{$tikiroot}' ;
var _FileBrowserLanguage      = 'php' ;
var _QuickUploadLanguage      = 'php' ;
var _FileBrowserExtension     = 'php' ;

FCKConfig.BodyClass = 'wikitext';
FCKConfig.FontNames = 'sans serif;serif;monospace;Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana' ;

FCKConfig.ToolbarSets["Tiki"] = [
{foreach item=it from=$toolbar name=lines}
  {foreach item=i from=$it name=item}
  [{foreach item=m from=$i name=im}'{$m}'{if $smarty.foreach.im.index+1 ne $smarty.foreach.im.total},{/if}{/foreach}]{if $smarty.foreach.lines.index+1 ne $smarty.foreach.lines.total},{/if}

  {/foreach}
  {if $smarty.foreach.lines.index+1 ne $smarty.foreach.lines.total}'/',{/if}

{/foreach}
] ;

FCKConfig.StylesXmlPath = _TikiRoot + 'lib/fckeditor_tiki/tikistyles.xml';
FCKConfig.TemplatesXmlPath = _TikiRoot + 'lib/fckeditor_tiki/tikitemplates.xml';

FCKConfig.EditorAreaCSS = _TikiRoot + '{$fckstyle}' ;
FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/{$wysiwyg_toolbar_skin}/' ;
FCKConfig.DefaultLanguage   = '{$language}' ;
FCKConfig.AutoDetectLanguage   = {if $feature_detect_language eq 'y'}true{else}false{/if} ;
FCKConfig.ContentLangDirection = '{if $feature_bidi eq 'y'}rtl{else}ltr{/if}' ;
FCKConfig.StartupFocus = true ;
FCKConfig.FormatOutput = true ;

FCKConfig.ImageBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Type=Image&Connector=connectors/php/connector.php' ;

FCKConfig.PluginsPath = _TikiRoot + 'lib/fckeditor_tiki/plugins' ;

FCKConfig.Plugins.Add( 'tikilink' ) ;
FCKConfig.tikilinkBtn     = '{tr}Insert/Edit an internal wiki link{/tr}' ;
FCKConfig.tikilinkDlgTitle    = '{tr}Tiki Link - Insert internal link{/tr}' ;
FCKConfig.tikilinkDlgName   = '{tr}Wiki Link insert{/tr}' ;
FCKConfig.tikilinkDlgSelection    = '{tr}Please make a selection of text in order to create a link{/tr}' ;

FCKConfig.LinkBrowser = false;
FCKConfig.LinkUpload = false;

FCKConfig.Plugins.Add( 'tikiimage' ) ;
