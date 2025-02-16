<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\File\SlideshowHelper;
use Tiki\Wiki\WikiPaginationUtils;

global $pdfStyles, $prefs, $tiki_p_view;
$section = 'wiki page';
require_once('tiki-setup.php');
$tikilib = TikiLib::lib('tiki');
$structlib = TikiLib::lib('struct');
$wikilib = TikiLib::lib('wiki');
$access = TikiLib::lib('access');
$headerlib = TikiLib::lib('header');
$smarty = TikiLib::lib('smarty');

$headerlib->add_js(
    "var fragments='y';
    var fragmentClass='grow';
    var fragmentHighlightColor='highlight-blue';"
);
include_once('lib/wiki-plugins/wikiplugin_slideshow.php');

$access->check_feature('feature_wiki');
$access->check_feature('feature_slideshow');

//make the other things know we are loading a slideshow
$tikilib->is_slideshow = true;
$smarty->assign('is_slideshow', 'y');

// Create the HomePage if it doesn't exist
if (! $tikilib->page_exists($prefs['wikiHomePage'])) {
    $tikilib->create_page(
        $prefs['wikiHomePage'],
        0,
        '',
        date("U"),
        'Tiki initialization'
    );
}

if (! isset($_SESSION["thedate"])) {
    $thedate = date("U");
} else {
    $thedate = $_SESSION["thedate"];
}

// Get the page from the request var or default it to HomePage
if (! isset($_REQUEST["page"])) {
    $_REQUEST["page"] = $wikilib->get_default_wiki_page();
}
$page = $wikilib->get_page_by_slug($_REQUEST['page']);
$smarty->assign('page', $page);

// If the page doesn't exist then display an error
if (! ($info = $tikilib->page_exists($page))) {
    include_once('tiki-index.php');
    die;
}

if (isset($_REQUEST['theme'])) {
    $theme = $_REQUEST['theme'];
} else {
    $theme = "black";
}

// Now check permissions to access this page
$tikilib->get_perm_object($page, 'wiki page', $info);
if ($tiki_p_view != 'y') {
    $smarty->assign('errortype', 401);
    $smarty->assign(
        'msg',
        tra("Permission denied. You cannot view this page.")
    );

    $smarty->display("error_raw.tpl");
    die;
}

// BreadCrumbNavigation here
// Remember to reverse the array when posting the array

if (! isset($_SESSION["breadCrumb"])) {
    $_SESSION["breadCrumb"] = [];
}

if (! in_array($page, $_SESSION["breadCrumb"])) {
    if (count($_SESSION["breadCrumb"]) > $prefs['userbreadCrumb']) {
        array_shift($_SESSION["breadCrumb"]);
    }

    array_push($_SESSION["breadCrumb"], $page);
} else {
    // If the page is in the array move to the last position
    $pos = array_search($page, $_SESSION["breadCrumb"]);

    unset($_SESSION["breadCrumb"][$pos]);
    array_push($_SESSION["breadCrumb"], $page);
}

// Now increment page hits since we are visiting this page
$tikilib->add_hit($page);

// Get page data
$parserlib = TikiLib::lib('parser');
$info = $tikilib->get_page_info($page);
$pdata = $parserlib->parse_data_raw($info["data"]);

if (! isset($_REQUEST['pagenum'])) {
    $_REQUEST['pagenum'] = 1;
}

//tags need to be removed from data before data formatting
$tagsArr = [["div", "icon_edit_section", "class"], ["a", "editplugin", "class"],
            ["a", "show-errors-button", "id"], ["a", "heading-link", "class"]];


$pages = WikiPaginationUtils::getNumberOfPages($pdata);
$pdata = WikiPaginationUtils::getPage($pdata, $_REQUEST['pagenum']);
// Put ~pp~, ~np~ and <pre> back. --rlpowell, 24 May 2004
$parserlib->replace_preparse($info["data"], $preparsed, $noparsed);
$parserlib->replace_preparse($pdata, $preparsed, $noparsed);

$slidePluginData = $parserlib->getPlugins($info["data"], ['slideshow']);

$slidePluginHeadingLevelSlideSeparator = 3;
if (! empty($slidePluginData)) {
    if (array_key_exists('headingLevelSlideSeparator', $slidePluginData[0]['arguments'])) {
        $slidePluginHeadingLevelSlideSeparator = $slidePluginData[0]['arguments']['headingLevelSlideSeparator'];
        // Check if variable $headingLevelSlideSeparator is empty or a string or if inferior to 1, then assign default value
        if (empty($slidePluginHeadingLevelSlideSeparator) || (filter_var($slidePluginHeadingLevelSlideSeparator, FILTER_VALIDATE_INT) === false) || ($slidePluginHeadingLevelSlideSeparator < 1)) {
            $slidePluginHeadingLevelSlideSeparator = 3;
        }
    }
}

$pdata = formatContent($pdata, $tagsArr, $slidePluginHeadingLevelSlideSeparator);

if (isset($_REQUEST['pdf'])) {
    $access->check_feature("feature_slideshow_pdfexport");
    set_time_limit(777);

    $_POST["html"] = urldecode($_POST["html"] ?? '');

    if (isset($_POST["html"])) {
        $generator = new PdfGenerator(PdfGenerator::MPDF);
        if (! empty($generator->getError())) {
            Feedback::error(
                tr(
                    'Exporting slideshow as PDF requires a working installation of mPDF.'
                )
                . "<br \>"
                . tr('Export to PDF error: %0', $generator->getError())
            );
            $access = TikiLib::lib('access');
            $access->redirect(
                str_replace(
                    'tiki-slideshow.php?',
                    'tiki-index.php?',
                    $_SERVER['HTTP_REFERER']
                )
            );
        }

        $params = [
            'orientation' => isset($_REQUEST['landscape']) ? 'L' : 'P',
        ];
        $filename = TikiLib::lib('tiki')
            ->remove_non_word_characters_and_accents($_REQUEST['page']);

        $pdfSettings = $_REQUEST['pdfSettings'] ?? null;
        if ($pdfSettings !== null) {
            $_POST['html'] = '<' . $_REQUEST['pdfSettings'] . ' />'
                . $_POST['html'];
        }

        // initialize background image
        $imgBackgroundCSS = '';

        //checking if to export slideshow
        if (isset($_REQUEST['printslides'])) {
            $customCSS
                = "<style type='text/css'>img{max-height:300px;width:auto;} body{font-size:1em} h1{font-size:1.5em;text-transform:none !important;}  section{height:300px;border:1px solid #000;margin-bottom:1%;padding:1%;}</style> ";
            $pdata = $customCSS . '<pdfsettings printFriendly="y" header="off" footer="off"></pdfsettings>' . $pdata;
        } else {
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($pdata, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            $sections = $doc->getElementsByTagName('section');

            foreach ($sections as $key => $section) {
                // Remove saveHTML call
                $color = $section->getAttribute('data-background-color');
                $imgBackground = $section->getAttribute('data-background-image');

                if (! empty($imgBackground)) {
                    $class = $section->getAttribute('class');
                    $section->setAttribute("class", 'section_bg_image_' . $key . ' ' . $class);

                    $imgBackgroundCSS .= ' .section_bg_image_' . $key . '{
                        background-image: url("' . $imgBackground . '");
                        background-position: top left;
                        background-repeat: no-repeat;
                        background-image-resize: 4;
                        background-image-resolution: from-image;
                    }';
                }

                if (! empty($color)) {
                    $style = $section->getAttribute('style');
                    $section->setAttribute("style", "background-color:" . $color . ";" . $style);
                }
            }

            $pdata = $doc->saveHTML();

            //getting css
            $customCSS = file_get_contents(
                REVEALJS_DIST_PATH . '/reveal.css'
            );
            $customCSS .= file_get_contents(
                REVEALJS_DIST_PATH . '/' . 'theme/' . $theme . '.css'
            );

            $customCSS .= '.reveal section{width:100%; height:100%;text-align:center;margin:auto;} section{text-align:center;margin: auto;width:100%;} .ss-heading{line-height:2.5em,padding-bottom:20px;} ' . $imgBackgroundCSS;
            $pdata = '<pdfsettings header="off" footer="off" margin_top="0" margin_bottom="0" margin_left="0" margin_right="0" printfriendly="n"></pdfsettings><div class="reveal">' . $pdata . '</div>';
            $pdata = str_replace(
                "</section><section",
                "</section><pagebreak /><section",
                $pdata . '<style>' . str_replace([".reveal {","vertical-align: baseline;"], [".reveal,.reveal table{ ","vertical-align:top;"], $customCSS) . ' div.reveal, .reveal li{font-size:1.3em;font-weight:normal;line-height:1.5;height:auto !important; } img{max-height:400px;}  .reveal h1 {font-size: 2.8em; text-transform:none !important;} .reveal li ul li {font-size: 0.95em !important;margin: 0em !important;}</style>'
            ) . $pdfStyles;
        }

        $pdf = $generator->getPdf(
            $filename,
            $params,
            preg_replace('/%u([a-fA-F0-9]{4})/', '&#x\\1;', $pdata)
        );
        $length = strlen($pdf);
        header('Cache-Control: private, must-revalidate');
        header('Pragma: private');
        header('Content-disposition: inline; filename="' . $filename . '.pdf"');
        header("Content-Type: application/pdf");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . $length);
        echo $pdf;
        exit(0);
    }
    die;
}
$smarty->assign('pages', $pages);
$smarty->assign_by_ref('parsed', $pdata);
$smarty->assign_by_ref('lastModif', $info["lastModif"]);

if (empty($info["user"])) {
    $info["user"] = 'anonymous';
}

$smarty->assign_by_ref('lastUser', $info["user"]);

include_once('tiki-section_options.php');


$headerlib->add_jsfile(
    REVEALJS_DIST_PATH . '/reveal.js'
);
$headerlib->add_cssfile(
    REVEALJS_DIST_PATH . '/reveal.css'
);
$headerlib->add_cssfile(
    REVEALJS_DIST_PATH . '/' . 'theme/' . $theme
                . '.css'
);
$headerlib->add_css(
    '.reveal span{font-family: "Font Awesome 5 Free";font-style: normal;font-weight:900} .reveal #ss-options span.select2-selection__choice__value { font-family: "Source Sans Pro", Helvetica, sans-serif } .reveal .controls{z-index:103;}#ss-settings-holder {position:fixed;top:10px;left:0px;width:10%;height:30px;text-align:left;padding-left:15px;cursor:pointer;z-index:102;line-height:1.5rem}#ss-options{position:fixed;top:50px;left:-2000px;width:230px;background-color:rgba(00,00,00,0.8);font-size:1.1rem;line-height:2.2rem;color:#fff;z-index:101;padding: 10px;border-top-right-radius: 25px;border-bottom-right-radius: 25px;} #ss-options a{color:#999} #ss-options a:hover{color:#fff} #page-bar,.icon_edit_section,.editplugin, #show-errors-button, .wikitext, .icon_edit_section, #toc,.heading-link {display:none} .fade:not(.show) { opacity: 1;} @media all and (orientation: portrait){.reveal section div,.reveal span,.reveal p,.reveal blockquote,.reveal pre,.reveal ol,.reveal ul,.reveal article,.reveal section {font-size:100% !important}} #reveal-controls span,#listSlides{cursor:pointer;color:#999;padding:0.15em} #reveal-controls span:hover,#listSlides:hover{color:#fff} footer{visibility:hidden} .scale-1{transform:scale(0.9);transform-origin:top center} .scale-2{transform:scale(0.8);transform-origin:top center} .scale-3{transform:scale(0.7);transform-origin:top center} .scale-4{transform:scale(0.6);transform-origin:top center} .scale-5{transform:scale(0.5);transform-origin:top center} .scale-6{transform:scale(0.45);transform-origin:top center}'
);

$headerlib->add_jq_onready(
    '$("<link/>", {rel: "stylesheet",type: "text/css",href: "", id:"themeCSS"}).appendTo("head");
    $("body").append("<style type=\"text/css\">.reveal li,.reveal section p { font-size: 1.3em; line-height:1.4em } .reveal li{margin:0.1em 0.5em 0.1em 0.5em} .reveal li ul li{font-size:0.9em !important; margin:0em !important}.reveal section pre code { font-size: 0.7em !important;} .reveal h1 {font-size: 2.8em; text-transform:none !important;margin-bottom:0 !important;} .reveal  {font-size: 1.4em;}.reveal .slides section .fragment.grow.visible {transform: scale(1.03);}.reveal table {overflow: hidden;} .reveal section img {border:0px;background:none;box-shadow:none} .reveal table th, .reveal table td{text-align:center;vertical-align:top !important} .reveal ul{vertical-align:top !important}</style>");
    var extraElements=["#page-bar",".icon_edit_section",".icon-link-external",".editplugin","#show-errors-button",".wikitext",".icon_edit_section","#toc","footer",".heading-link"];
    jQuery.each( extraElements, function( i, val ) {
        $( val ).remove();
    });


    if(fragments=="y") {
        $( "li" ).addClass( "fragment "+fragmentClass+" "+fragmentHighlightColor );
    }

    $("#ss-settings").on("click", function () {
        var position = $("#ss-options").position();
        if(position.left==0){
            $("#ss-settings").switchClass("fa-times","fa-cogs");
            $("#ss-options").animate({left: \'-2000px\'});
        }
        else {
            $("#ss-settings").switchClass("fa-cogs","fa-times");
            $("#ss-options").animate({left: \'0px\'});}
        });
        Reveal.addEventListener( \'slidechanged\', function( event ) {

            var position = $("#ss-options").position();
            if(position.left==0){
                $("#ss-settings").switchClass("fa-times","fa-cogs");
                $("#ss-options").animate({left: \'-2000px\'});
            }
        });

        //reveal controls
        $("body").on("click", "#play", function () {
            if($("#play").hasClass("fa-play-circle")) {
                $("#play").switchClass("fa-play-circle","fa-pause-circle", 1000, "easeInOutQuad");
                Reveal.configure({ autoSlide:10000 });
                $(this).attr("style","color:#fff");

            }
            else {
                $("#play").switchClass("fa-pause-circle","fa-play-circle", 1000, "easeInOutQuad");
                Reveal.configure({ autoSlide: 0 });
                $(this).attr("style","");
            }
        });
        $("body").on("click", "#firstSlide", function () {
            Reveal.slide( 0, 0,0 ); //Reveal.slide( indexh, indexv, indexf );
        });
        $("body").on("click", "#lastSlide", function () {
            Reveal.slide( Reveal.getTotalSlides()-1, 0,0 ); //Reveal.slide( indexh, indexv, indexf );
        });
        $("body").on("click", "#nextSlide", function () {
            var currentSlide=Reveal.getIndices().h;
            Reveal.slide(currentSlide+1,0,0); //Reveal.slide( indexh, indexv, indexf );
        });
        $("body").on("click", "#prevSlide", function () {
            var currentSlide=Reveal.getIndices().h;
            if(currentSlide>0) {
            Reveal.slide(currentSlide-1,0,0);
             } //Reveal.slide( indexh, indexv, indexf );
        });
        $("body").on("click", "#listSlides", function () {
            Reveal.toggleOverview();
        });
        $("body").on("click", "#loop", function () {
            if($("#loop").hasClass("icon-inactive")){
                $("#loop").switchClass("icon-inactive","icon-active");
                Reveal.configure({loop: true});
                $(this).attr("style","color:#fff");
            }
            else{
                $("#loop").switchClass("icon-active","icon-inactive");
                Reveal.configure({loop: false});
                $(this).attr("style","");
            }

        });
        //end of controls

        $( "#showtheme" ).on("change", function() {
            var selectedCSS=$("#showtheme" ).val();
            $("#themeCSS").attr("href","' . REVEALJS_DIST_PATH . '/theme/"+selectedCSS+".css");
        });
        $( "#showtransition" ).on("change", function() {
            var selectedTransition=$("#showtransition" ).val();
            Reveal.configure({ transition: selectedTransition });
        });
        $("body").on("click", "#exportPDF", function () {
            if($("#showtheme" ).val()!="") {
                var pdfURL= $( "#exportPDF" ).attr("href")+"&theme="+$("#showtheme" ).val();
                $( "#exportPDF" ).attr("href",pdfURL);
            }
        });
        //Append slide title with URL on slide change
        Reveal.addEventListener( "slidechanged", function( event ) { location.hash = "!_"+$(".present table tr td").children("h1").attr("id");});
        Reveal.initialize({ width: "98%",height: "100%",center: false});
        $(window).on("load", function() {
            //loop to scale contents
            $( "section" ).each(function( index ) {
                var overflow=$(this).innerHeight()-$(document).innerHeight();
                var scalePercent=Math.round(100-((overflow/$(document).innerHeight())*100));
                if(overflow>30){
                    if(scalePercent>70){ scalePercent>85?scalePercent=1:scalePercent=2;}
                    else if(scalePercent>30) {scalePercent>45?scalePercent=3:scalePercent=4;}
                    else if(scalePercent>25){scalePercent=5;}
                    else{scalePercent=6;}
                    $(this).addClass("scale-"+scalePercent);
                }
            });
            //end of loop
        });

        Reveal.addEventListener( \'ready\', function( event ) {

            var found=0;
            if(location.hash && found==0){
                 var goToSlide = location.hash.replace("#!_","");
                 var slideCount=0;
                 $( "section table tr td" ).each(function( index ) {
                    if(found==1){return;}
                    if($(this).children("h1").attr("id")==goToSlide && found==0){
                         Reveal.slide(slideCount);
                         found=1;
                    }
                    if($(this).children("h1").attr("id")){
                        slideCount++;
                    }
                 });
             }
        });
        '
);

$params = [];

foreach ($_GET as $key => $value) {
    $key = htmlentities($key, ENT_QUOTES);
    $params[$key] = htmlentities($value, ENT_QUOTES);
}

if (empty($parserlib->getPlugins($info["data"], ['slideshow']))) {
    // If {slideshow} is not in the page, inject necessary Reveal related configurations
    $params = array_merge(SlideshowHelper::getDefaultPluginValues(), $params);
    $revealSettings = SlideshowHelper::getRevealSettingsAsString($params);
    $headerlib->add_js(
        "Reveal.configure({" . $revealSettings . "});"
    );
}


$themesArr = [['black','Black'],
            ['blood','Blood'],
            ['beige','Beige'],
            ['league','League'],
            ['moon','Moon'],
            ['night','Night'],
            ['serif','Serif'],
            ['simple','Simple'],
            ['sky','Sky'],
            ['solarized','Solarized']];

$themeOptions = '';

foreach ($themesArr as $themeOption) {
    $themeOption[0] == $theme ? $selected = 'selected="selected"' : $selected = '';
    $themeOptions .= '<option value="' . $themeOption[0] . '" ' . $selected . '>' . tra($themeOption[1]) . '</option>';
}

// disallow robots to index page
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('themeOptions', $themeOptions);
// Display the Index Template
$smarty->assign('mid', 'tiki-show_page_raw.tpl');

// use tiki_full to include include CSS and JavaScript
$smarty->display("tiki_full.tpl");


//new function for data cleaning and foramtting

function formatContent($content, $tagArr, $slidePluginHeadingLevelSlideSeparator)
{
    $doc = new DOMDocument();

    // set error level
    $internalErrors = libxml_use_internal_errors(true);

    $doc->loadHTML('<html lang="en"><body>' . $content . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXpath($doc);
    $expression = '(//sslide//h1|//sslide//h2|//sslide//h3)';
    $elements = $xpath->query($expression);

    foreach ($elements as $index => $element) {
        dom_rename_element($element, 'sheading');
    }

    foreach ($tagArr as $tag) {
        $list = $xpath->query(
            '//' . $tag[0] . '[contains(concat(\' \', normalize-space(@'
            . $tag[2] . '), \' \'), "' . $tag[1] . '")]'
        );
        for ($i = 0; $i < $list->length; $i++) {
            $p = $list->item($i);
            if (isset($tag[3]) && $tag[3] == 1) { //the parameter checks if content of tag has to be preserved
                $attributes = $p->attributes;
                while ($attributes->length) {
                    //preserving href

                    if ($attributes->item(0)->name == "href") {
                        $hrefValue = $attributes->item(0)->value;
                    }
                    $p->removeAttribute($attributes->item(0)->name);
                }
                if ($hrefValue) {
                    $p->setAttribute("href", $hrefValue);
                }
            } else {
                $p->parentNode->removeChild($p);
            }
        }
    }


    $content = str_replace(['<html>', '</html>'], '', $doc->saveHTML());

    // restore error level
    libxml_use_internal_errors($internalErrors);
    $headingsTagsExpression = headings_tags_separator_expression($slidePluginHeadingLevelSlideSeparator);
    $headingsTags = preg_split($headingsTagsExpression, $content);
    $firstSlide = 0;
    if (isset($_REQUEST['pdf'])) {
        $headingStart = '<div style="border-bottom:0px;" class="ss-heading">';
        $slideStart = '</div><div>';
        $slideEnd = "</div>";
    } else {
        $headingStart = '<table width="100%" cellpadding="0" cellspace="0"><tr><td colspan="2" style="border-bottom:0px;" class="ss-heading">';
        $slideStart = '</td></tr><tr><td>';
        $slideEnd = "</td></tr></table>";
    }
    $slideContent = '';
    // Check if $headingsTags is an array before processing
    // This condition ensures that preg_split didn't fail, resulting in an array.
    if (is_array($headingsTags)) {
        // Checking if $headingsTags has more than one element, skip the first, as the first slide is empty, otherwise keep the first element
        if (count($headingsTags) > 1) {
            unset($headingsTags[0]);
        }


        foreach ($headingsTags as $slide) {
            if ($firstSlide == 0) {
                //checking if first slide has pluginSlideShowSlide instance, then concat with main text, otherwise ignore
                $sectionCheck = strpos($slide, '<sslide');
                if ($sectionCheck == true) {
                    $slideContent .= str_replace("sslide", "section", $slide);
                } else {
                    $slideContent .= slideshow_section($slide, $headingStart, $slideStart, $slideEnd, $slidePluginHeadingLevelSlideSeparator);
                }
                $firstSlide = 1;
            } else {
                $slideContent .= slideshow_section($slide, $headingStart, $slideStart, $slideEnd, $slidePluginHeadingLevelSlideSeparator);
            }
        }
        //images alignment left or right
        //replacment for slideshowslide
    }


    return html_entity_decode(str_replace(
        ['<sslide', '<sheading', '</sheading>', '</sslide>'],
        [$slideEnd . '</section><section', $headingStart . '<h1', '</h1>' . $slideStart],
        $slideContent ?: $content // Changed to use $content instead of $slideContent
    ));
}

function slideshow_section($slide, $headingStart, $slideStart, $slideEnd, $headingLevelSlideSeparator)
{
    $endHeadingTags = [];
    for ($i = 1; $i <= $headingLevelSlideSeparator; $i++) {
        $endHeadingTags [] = '</h' . $i . '>';
    }
    return '<section>' . $headingStart . '<h1' . str_replace(
        $endHeadingTags,
        '</h1>' . $slideStart,
        $slide
    ) . $slideEnd . '</section>';
}

function dom_rename_element(DOMElement $node, $name)
{
    $renamed = $node->ownerDocument->createElement($name);

    foreach ($node->attributes as $attribute) {
        $renamed->setAttribute($attribute->nodeName, $attribute->nodeValue);
    }

    while ($node->firstChild) {
        $renamed->appendChild($node->firstChild);
    }

    return $node->parentNode->replaceChild($renamed, $node);
}

function headings_tags_separator_expression($headingLevelSlideSeparator)
{
    $levels = '';

    for ($i = 1; $i <= $headingLevelSlideSeparator; $i++) {
        $levels .= $i;
    }

    $expression = '/<h[' . trim($levels) . ']/';

    return $expression;
}
