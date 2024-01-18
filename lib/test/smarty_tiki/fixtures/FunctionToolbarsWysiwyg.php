<?php

$expectedJsArray = [
    0  =>
        [
            0 => 'tiki_cookie_jar=new Object();',
            1 => 'if (! window.pickerData) { window.pickerData = {}; } window.pickerData[\'color\'] = {"~~#000:text~~":"<span style=\'background-color: #000\' title=\'#000\' />&nbsp;</span>","~~#006:text~~":"<span style=\'background-color: #006\' title=\'#006\' />&nbsp;</span>","~~#009:text~~":"<span style=\'background-color: #009\' title=\'#009\' />&nbsp;</span>","~~#00F:text~~":"<span style=\'background-color: #00F\' title=\'#00F\' />&nbsp;</span>","~~#060:text~~":"<span style=\'background-color: #060\' title=\'#060\' />&nbsp;</span>","~~#066:text~~":"<span style=\'background-color: #066\' title=\'#066\' />&nbsp;</span>","~~#069:text~~":"<span style=\'background-color: #069\' title=\'#069\' />&nbsp;</span>","~~#06F:text~~":"<span style=\'background-color: #06F\' title=\'#06F\' />&nbsp;</span>","~~#090:text~~":"<span style=\'background-color: #090\' title=\'#090\' />&nbsp;</span>","~~#096:text~~":"<span style=\'background-color: #096\' title=\'#096\' />&nbsp;</span>","~~#099:text~~":"<span style=\'background-color: #099\' title=\'#099\' />&nbsp;</span>","~~#09F:text~~":"<span style=\'background-color: #09F\' title=\'#09F\' />&nbsp;</span>","~~#0F0:text~~":"<span style=\'background-color: #0F0\' title=\'#0F0\' />&nbsp;</span>","~~#0F6:text~~":"<span style=\'background-color: #0F6\' title=\'#0F6\' />&nbsp;</span>","~~#0F9:text~~":"<span style=\'background-color: #0F9\' title=\'#0F9\' />&nbsp;</span>","~~#0FF:text~~":"<span style=\'background-color: #0FF\' title=\'#0FF\' />&nbsp;</span>","~~#600:text~~":"<span style=\'background-color: #600\' title=\'#600\' />&nbsp;</span>","~~#606:text~~":"<span style=\'background-color: #606\' title=\'#606\' />&nbsp;</span>","~~#609:text~~":"<span style=\'background-color: #609\' title=\'#609\' />&nbsp;</span>","~~#60F:text~~":"<span style=\'background-color: #60F\' title=\'#60F\' />&nbsp;</span>","~~#660:text~~":"<span style=\'background-color: #660\' title=\'#660\' />&nbsp;</span>","~~#666:text~~":"<span style=\'background-color: #666\' title=\'#666\' />&nbsp;</span>","~~#669:text~~":"<span style=\'background-color: #669\' title=\'#669\' />&nbsp;</span>","~~#66F:text~~":"<span style=\'background-color: #66F\' title=\'#66F\' />&nbsp;</span>","~~#690:text~~":"<span style=\'background-color: #690\' title=\'#690\' />&nbsp;</span>","~~#696:text~~":"<span style=\'background-color: #696\' title=\'#696\' />&nbsp;</span>","~~#699:text~~":"<span style=\'background-color: #699\' title=\'#699\' />&nbsp;</span>","~~#69F:text~~":"<span style=\'background-color: #69F\' title=\'#69F\' />&nbsp;</span>","~~#6F0:text~~":"<span style=\'background-color: #6F0\' title=\'#6F0\' />&nbsp;</span>","~~#6F6:text~~":"<span style=\'background-color: #6F6\' title=\'#6F6\' />&nbsp;</span>","~~#6F9:text~~":"<span style=\'background-color: #6F9\' title=\'#6F9\' />&nbsp;</span>","~~#6FF:text~~":"<span style=\'background-color: #6FF\' title=\'#6FF\' />&nbsp;</span>","~~#900:text~~":"<span style=\'background-color: #900\' title=\'#900\' />&nbsp;</span>","~~#906:text~~":"<span style=\'background-color: #906\' title=\'#906\' />&nbsp;</span>","~~#909:text~~":"<span style=\'background-color: #909\' title=\'#909\' />&nbsp;</span>","~~#90F:text~~":"<span style=\'background-color: #90F\' title=\'#90F\' />&nbsp;</span>","~~#960:text~~":"<span style=\'background-color: #960\' title=\'#960\' />&nbsp;</span>","~~#966:text~~":"<span style=\'background-color: #966\' title=\'#966\' />&nbsp;</span>","~~#969:text~~":"<span style=\'background-color: #969\' title=\'#969\' />&nbsp;</span>","~~#96F:text~~":"<span style=\'background-color: #96F\' title=\'#96F\' />&nbsp;</span>","~~#990:text~~":"<span style=\'background-color: #990\' title=\'#990\' />&nbsp;</span>","~~#996:text~~":"<span style=\'background-color: #996\' title=\'#996\' />&nbsp;</span>","~~#999:text~~":"<span style=\'background-color: #999\' title=\'#999\' />&nbsp;</span>","~~#99F:text~~":"<span style=\'background-color: #99F\' title=\'#99F\' />&nbsp;</span>","~~#9F0:text~~":"<span style=\'background-color: #9F0\' title=\'#9F0\' />&nbsp;</span>","~~#9F6:text~~":"<span style=\'background-color: #9F6\' title=\'#9F6\' />&nbsp;</span>","~~#9F9:text~~":"<span style=\'background-color: #9F9\' title=\'#9F9\' />&nbsp;</span>","~~#9FF:text~~":"<span style=\'background-color: #9FF\' title=\'#9FF\' />&nbsp;</span>","~~#F00:text~~":"<span style=\'background-color: #F00\' title=\'#F00\' />&nbsp;</span>","~~#F06:text~~":"<span style=\'background-color: #F06\' title=\'#F06\' />&nbsp;</span>","~~#F09:text~~":"<span style=\'background-color: #F09\' title=\'#F09\' />&nbsp;</span>","~~#F0F:text~~":"<span style=\'background-color: #F0F\' title=\'#F0F\' />&nbsp;</span>","~~#F60:text~~":"<span style=\'background-color: #F60\' title=\'#F60\' />&nbsp;</span>","~~#F66:text~~":"<span style=\'background-color: #F66\' title=\'#F66\' />&nbsp;</span>","~~#F69:text~~":"<span style=\'background-color: #F69\' title=\'#F69\' />&nbsp;</span>","~~#F6F:text~~":"<span style=\'background-color: #F6F\' title=\'#F6F\' />&nbsp;</span>","~~#F90:text~~":"<span style=\'background-color: #F90\' title=\'#F90\' />&nbsp;</span>","~~#F96:text~~":"<span style=\'background-color: #F96\' title=\'#F96\' />&nbsp;</span>","~~#F99:text~~":"<span style=\'background-color: #F99\' title=\'#F99\' />&nbsp;</span>","~~#F9F:text~~":"<span style=\'background-color: #F9F\' title=\'#F9F\' />&nbsp;</span>","~~#FF0:text~~":"<span style=\'background-color: #FF0\' title=\'#FF0\' />&nbsp;</span>","~~#FF6:text~~":"<span style=\'background-color: #FF6\' title=\'#FF6\' />&nbsp;</span>","~~#FF9:text~~":"<span style=\'background-color: #FF9\' title=\'#FF9\' />&nbsp;</span>","~~#FFF:text~~":"<span style=\'background-color: #FFF\' title=\'#FFF\' />&nbsp;</span>"};',
            2 => 'if (! window.pickerData) { window.pickerData = {}; } window.pickerData[\'specialchar\'] = {"\\u00c0":"\\u00c0","\\u00e0":"\\u00e0","\\u00c1":"\\u00c1","\\u00e1":"\\u00e1","\\u00c2":"\\u00c2","\\u00e2":"\\u00e2","\\u00c3":"\\u00c3","\\u00e3":"\\u00e3","\\u00c4":"\\u00c4","\\u00e4":"\\u00e4","\\u01cd":"\\u01cd","\\u01ce":"\\u01ce","\\u0102":"\\u0102","\\u0103":"\\u0103","\\u00c5":"\\u00c5","\\u00e5":"\\u00e5","\\u0100":"\\u0100","\\u0101":"\\u0101","\\u0104":"\\u0104","\\u0105":"\\u0105","\\u00c6":"\\u00c6","\\u00e6":"\\u00e6","\\u0106":"\\u0106","\\u0107":"\\u0107","\\u00c7":"\\u00c7","\\u00e7":"\\u00e7","\\u010c":"\\u010c","\\u010d":"\\u010d","\\u0108":"\\u0108","\\u0109":"\\u0109","\\u010a":"\\u010a","\\u010b":"\\u010b","\\u00d0":"\\u00d0","\\u0111":"\\u0111","\\u00f0":"\\u00f0","\\u010e":"\\u010e","\\u010f":"\\u010f","\\u00c8":"\\u00c8","\\u00e8":"\\u00e8","\\u00c9":"\\u00c9","\\u00e9":"\\u00e9","\\u00ca":"\\u00ca","\\u00ea":"\\u00ea","\\u00cb":"\\u00cb","\\u00eb":"\\u00eb","\\u011a":"\\u011a","\\u011b":"\\u011b","\\u0112":"\\u0112","\\u0113":"\\u0113","\\u0116":"\\u0116","\\u0117":"\\u0117","\\u0118":"\\u0118","\\u0119":"\\u0119","\\u0122":"\\u0122","\\u0123":"\\u0123","\\u011c":"\\u011c","\\u011d":"\\u011d","\\u011e":"\\u011e","\\u011f":"\\u011f","\\u0120":"\\u0120","\\u0121":"\\u0121","\\u0124":"\\u0124","\\u0125":"\\u0125","\\u00cc":"\\u00cc","\\u00ec":"\\u00ec","\\u00cd":"\\u00cd","\\u00ed":"\\u00ed","\\u00ce":"\\u00ce","\\u00ee":"\\u00ee","\\u00cf":"\\u00cf","\\u00ef":"\\u00ef","\\u01cf":"\\u01cf","\\u01d0":"\\u01d0","\\u012a":"\\u012a","\\u012b":"\\u012b","\\u0130":"\\u0130","\\u0131":"\\u0131","\\u012e":"\\u012e","\\u012f":"\\u012f","\\u0134":"\\u0134","\\u0135":"\\u0135","\\u0136":"\\u0136","\\u0137":"\\u0137","\\u0139":"\\u0139","\\u013a":"\\u013a","\\u013b":"\\u013b","\\u013c":"\\u013c","\\u013d":"\\u013d","\\u013e":"\\u013e","\\u0141":"\\u0141","\\u0142":"\\u0142","\\u013f":"\\u013f","\\u0140":"\\u0140","\\u0143":"\\u0143","\\u0144":"\\u0144","\\u00d1":"\\u00d1","\\u00f1":"\\u00f1","\\u0145":"\\u0145","\\u0146":"\\u0146","\\u0147":"\\u0147","\\u0148":"\\u0148","\\u00d2":"\\u00d2","\\u00f2":"\\u00f2","\\u00d3":"\\u00d3","\\u00f3":"\\u00f3","\\u00d4":"\\u00d4","\\u00f4":"\\u00f4","\\u00d5":"\\u00d5","\\u00f5":"\\u00f5","\\u00d6":"\\u00d6","\\u00f6":"\\u00f6","\\u01d1":"\\u01d1","\\u01d2":"\\u01d2","\\u014c":"\\u014c","\\u014d":"\\u014d","\\u0150":"\\u0150","\\u0151":"\\u0151","\\u0152":"\\u0152","\\u0153":"\\u0153","\\u00d8":"\\u00d8","\\u00f8":"\\u00f8","\\u0154":"\\u0154","\\u0155":"\\u0155","\\u0156":"\\u0156","\\u0157":"\\u0157","\\u0158":"\\u0158","\\u0159":"\\u0159","\\u015a":"\\u015a","\\u015b":"\\u015b","\\u015e":"\\u015e","\\u015f":"\\u015f","\\u0160":"\\u0160","\\u0161":"\\u0161","\\u015c":"\\u015c","\\u015d":"\\u015d","\\u0162":"\\u0162","\\u0163":"\\u0163","\\u0164":"\\u0164","\\u0165":"\\u0165","\\u00d9":"\\u00d9","\\u00f9":"\\u00f9","\\u00da":"\\u00da","\\u00fa":"\\u00fa","\\u00db":"\\u00db","\\u00fb":"\\u00fb","\\u00dc":"\\u00dc","\\u00fc":"\\u00fc","\\u01d3":"\\u01d3","\\u01d4":"\\u01d4","\\u016c":"\\u016c","\\u016d":"\\u016d","\\u016a":"\\u016a","\\u016b":"\\u016b","\\u016e":"\\u016e","\\u016f":"\\u016f","\\u01d6":"\\u01d6","\\u01d8":"\\u01d8","\\u01da":"\\u01da","\\u01dc":"\\u01dc","\\u0172":"\\u0172","\\u0173":"\\u0173","\\u0170":"\\u0170","\\u0171":"\\u0171","\\u0174":"\\u0174","\\u0175":"\\u0175","\\u00dd":"\\u00dd","\\u00fd":"\\u00fd","\\u0178":"\\u0178","\\u00ff":"\\u00ff","\\u0176":"\\u0176","\\u0177":"\\u0177","\\u0179":"\\u0179","\\u017a":"\\u017a","\\u017d":"\\u017d","\\u017e":"\\u017e","\\u017b":"\\u017b","\\u017c":"\\u017c","\\u00de":"\\u00de","\\u00fe":"\\u00fe","\\u00df":"\\u00df","\\u0126":"\\u0126","\\u0127":"\\u0127","\\u00bf":"\\u00bf","\\u00a1":"\\u00a1","\\u00a2":"\\u00a2","\\u00a3":"\\u00a3","\\u00a4":"\\u00a4","\\u00a5":"\\u00a5","\\u20ac":"\\u20ac","\\u00a6":"\\u00a6","\\u00a7":"\\u00a7","\\u00aa":"\\u00aa","\\u00ac":"\\u00ac","\\u00af":"\\u00af","\\u00b0":"\\u00b0","\\u00b1":"\\u00b1","\\u00f7":"\\u00f7","\\u2030":"\\u2030","\\u00bc":"\\u00bc","\\u00bd":"\\u00bd","\\u00be":"\\u00be","\\u00b9":"\\u00b9","\\u00b2":"\\u00b2","\\u00b3":"\\u00b3","\\u00b5":"\\u00b5","\\u00b6":"\\u00b6","\\u2020":"\\u2020","\\u2021":"\\u2021","\\u00b7":"\\u00b7","\\u2022":"\\u2022","\\u00ba":"\\u00ba","\\u2200":"\\u2200","\\u2202":"\\u2202","\\u2203":"\\u2203","\\u018f":"\\u018f","\\u0259":"\\u0259","\\u2205":"\\u2205","\\u2207":"\\u2207","\\u2208":"\\u2208","\\u2209":"\\u2209","\\u220b":"\\u220b","\\u220f":"\\u220f","\\u2211":"\\u2211","\\u203e":"\\u203e","\\u2212":"\\u2212","\\u2217":"\\u2217","\\u221a":"\\u221a","\\u221d":"\\u221d","\\u221e":"\\u221e","\\u2220":"\\u2220","\\u2227":"\\u2227","\\u2228":"\\u2228","\\u2229":"\\u2229","\\u222a":"\\u222a","\\u222b":"\\u222b","\\u2234":"\\u2234","\\u223c":"\\u223c","\\u2245":"\\u2245","\\u2248":"\\u2248","\\u2260":"\\u2260","\\u2261":"\\u2261","\\u2264":"\\u2264","\\u2265":"\\u2265","\\u2282":"\\u2282","\\u2283":"\\u2283","\\u2284":"\\u2284","\\u2286":"\\u2286","\\u2287":"\\u2287","\\u2295":"\\u2295","\\u2297":"\\u2297","\\u22a5":"\\u22a5","\\u22c5":"\\u22c5","\\u25ca":"\\u25ca","\\u2118":"\\u2118","\\u2111":"\\u2111","\\u211c":"\\u211c","\\u2135":"\\u2135","\\u2660":"\\u2660","\\u2663":"\\u2663","\\u2665":"\\u2665","\\u2666":"\\u2666","\\ud835\\udefc":"\\ud835\\udefc","\\ud835\\udefd":"\\ud835\\udefd","\\ud835\\udee4":"\\ud835\\udee4","\\ud835\\udefe":"\\ud835\\udefe","\\ud835\\udee5":"\\ud835\\udee5","\\ud835\\udeff":"\\ud835\\udeff","\\ud835\\udf00":"\\ud835\\udf00","\\ud835\\udf01":"\\ud835\\udf01","\\ud835\\udee8":"\\ud835\\udee8","\\ud835\\udf02":"\\ud835\\udf02","\\ud835\\udee9":"\\ud835\\udee9","\\ud835\\udf03":"\\ud835\\udf03","\\ud835\\udf04":"\\ud835\\udf04","\\ud835\\udf05":"\\ud835\\udf05","\\ud835\\udeec":"\\ud835\\udeec","\\ud835\\udf06":"\\ud835\\udf06","\\ud835\\udf07":"\\ud835\\udf07","\\ud835\\udf08":"\\ud835\\udf08","\\ud835\\udeef":"\\ud835\\udeef","\\ud835\\udf09":"\\ud835\\udf09","\\ud835\\udef1":"\\ud835\\udef1","\\ud835\\udf0b":"\\ud835\\udf0b","\\ud835\\udef3":"\\ud835\\udef3","\\ud835\\udf0d":"\\ud835\\udf0d","\\ud835\\udef4":"\\ud835\\udef4","\\ud835\\udf0e":"\\ud835\\udf0e","\\ud835\\udf0f":"\\ud835\\udf0f","\\ud835\\udf10":"\\ud835\\udf10","\\ud835\\udef7":"\\ud835\\udef7","\\ud835\\udf11":"\\ud835\\udf11","\\u03a7":"\\u03a7","\\ud835\\udf12":"\\ud835\\udf12","\\ud835\\udef9":"\\ud835\\udef9","\\ud835\\udf13":"\\ud835\\udf13","\\ud835\\udefa":"\\ud835\\udefa","\\ud835\\udf14":"\\ud835\\udf14","\\ud835\\udefb":"\\ud835\\udefb","\\ud835\\udf15":"\\ud835\\udf15","\\u2605":"\\u2605","\\u2606":"\\u2606","\\u260e":"\\u260e","\\u261a":"\\u261a","\\u261b":"\\u261b","\\u261c":"\\u261c","\\u261d":"\\u261d","\\u261e":"\\u261e","\\u261f":"\\u261f","\\u2639":"\\u2639","\\u263a":"\\u263a","\\u2714":"\\u2714","\\u2718":"\\u2718","\\u00d7":"\\u00d7","\\u201e":"\\u201e","\\u201c":"\\u201c","\\u201d":"\\u201d","\\u201a":"\\u201a","\\u2018":"\\u2018","\\u2019":"\\u2019","\\u00ab":"\\u00ab","\\u00bb":"\\u00bb","\\u2039":"\\u2039","\\u203a":"\\u203a","\\u2014":"\\u2014","\\u2013":"\\u2013","\\u2026":"\\u2026","\\u2190":"\\u2190","\\u2191":"\\u2191","\\u2192":"\\u2192","\\u2193":"\\u2193","\\u2194":"\\u2194","\\u21d0":"\\u21d0","\\u21d1":"\\u21d1","\\u21d2":"\\u21d2","\\u21d3":"\\u21d3","\\u21d4":"\\u21d4","\\u00a9":"\\u00a9","\\u2122":"\\u2122","\\u00ae":"\\u00ae","\\u2032":"\\u2032","\\u2033":"\\u2033","^":"^","@":"@","%":"%","~":"~","|":"|","[":"[","]":"]","{":"{","}":"}","*":"*","#":"#"};',
        ],
    2  =>
        [
            0 => 'if (! window.dialogData) { window.dialogData = {}; } window.dialogData[1] = ["Wiki Link","<label for=\\"tbWLinkDesc\\">Show this text<\\/label>","<input type=\\"text\\" id=\\"tbWLinkDesc\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","<label for=\\"tbWLinkPage\\">Link to this page<\\/label>","<input type=\\"text\\" id=\\"tbWLinkPage\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","","","","","{\\"open\\": function () { dialogInternalLinkOpen(area_id, clickedElement); },\\n                        \\"buttons\\": { \\"Cancel\\": function() { dialogSharedClose(area_id,this); },\\"Insert\\": function() { dialogInternalLinkInsert(area_id,this); }}}"];',
            1 => 'window.dialogData[1] = ["Wiki Link","<label for=\\"tbWLinkDesc\\">Show this text<\\/label>","<input type=\\"text\\" id=\\"tbWLinkDesc\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","<label for=\\"tbWLinkPage\\">Link to this page<\\/label>","<input type=\\"text\\" id=\\"tbWLinkPage\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","","","","","{\\"open\\": function () { dialogInternalLinkOpen(area_id, clickedElement); },\\n                        \\"buttons\\": { \\"Cancel\\": function() { dialogSharedClose(area_id,this); },\\"Insert\\": function() { dialogInternalLinkInsert(area_id,this); }}}"];',
        ],
    3  =>
        [
            0 => 'if (! window.dialogData) { window.dialogData = {}; } window.dialogData[2] = ["External Link","<label for=\\"tbLinkDesc\\">Show this text<\\/label>","<input type=\\"text\\" id=\\"tbLinkDesc\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","<label for=\\"tbLinkURL\\">link to this URL<\\/label>","<input type=\\"text\\" id=\\"tbLinkURL\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","<label for=\\"tbLinkRel\\">Relation:<\\/label>","<input type=\\"text\\" id=\\"tbLinkRel\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","","","{\\"width\\": 300, \\"open\\": function () { dialogExternalLinkOpen( area_id ) },\\n                        \\"buttons\\": { \\"Cancel\\": function() { dialogSharedClose(area_id,this); },\\"Insert\\": function() { dialogExternalLinkInsert(area_id,this) }}}"];',
            1 => 'window.dialogData[2] = ["External Link","<label for=\\"tbLinkDesc\\">Show this text<\\/label>","<input type=\\"text\\" id=\\"tbLinkDesc\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","<label for=\\"tbLinkURL\\">link to this URL<\\/label>","<input type=\\"text\\" id=\\"tbLinkURL\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","<label for=\\"tbLinkRel\\">Relation:<\\/label>","<input type=\\"text\\" id=\\"tbLinkRel\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","","","{\\"width\\": 300, \\"open\\": function () { dialogExternalLinkOpen( area_id ) },\\n                        \\"buttons\\": { \\"Cancel\\": function() { dialogSharedClose(area_id,this); },\\"Insert\\": function() { dialogExternalLinkInsert(area_id,this) }}}"];',
        ],
    4  =>
        [
            0 => 'if (! window.dialogData) { window.dialogData = {}; } window.dialogData[3] = ["Find Text","<label>Search:<\\/label>","<input type=\\"text\\" id=\\"tbFindSearch\\" class=\\"ui-widget-content ui-corner-all\\" \\/>","<label for=\\"tbFindCase\\" style=\\"display:inline;\\">Case Insensitivity:<\\/label>","<input type=\\"checkbox\\" id=\\"tbFindCase\\" checked=\\"checked\\" class=\\"ui-widget-content ui-corner-all\\" \\/>","<p class=\\"description\\">Note: Uses regular expressions<\\/p>","{\\"open\\": function() { dialogFindOpen(area_id); },\\"buttons\\": { \\"Close\\": function() { dialogSharedClose(area_id,this); },\\"Find\\": function() { dialogFindFind(area_id); }}}"];',
        ],
    5  =>
        [
            0 => 'if (! window.dialogData) { window.dialogData = {}; } window.dialogData[4] = ["Text Replace","<label for=\\"tbReplaceSearch\\">Search:<\\/label>","<input type=\\"text\\" id=\\"tbReplaceSearch\\" class=\\"ui-widget-content ui-corner-all\\" \\/>","<label for=\\"tbReplaceReplace\\">Replace:<\\/label>","<input type=\\"text\\" id=\\"tbReplaceReplace\\" class=\\"ui-widget-content ui-corner-all clearfix\\" \\/>","<label for=\\"tbReplaceCase\\" style=\\"display:inline;\\">Case Insensitivity:<\\/label>","<input type=\\"checkbox\\" id=\\"tbReplaceCase\\" checked=\\"checked\\" class=\\"ui-widget-content ui-corner-all\\" \\/>","<br \\/><label for=\\"tbReplaceAll\\" style=\\"display:inline;\\">Replace All:<\\/label>","<input type=\\"checkbox\\" id=\\"tbReplaceAll\\" checked=\\"checked\\" class=\\"ui-widget-content ui-corner-all\\" \\/>","<p class=\\"description\\">Note: Uses regular expressions<\\/p>","{\\"open\\": function() { dialogReplaceOpen(area_id); },\\"buttons\\": { \\"Close\\": function() { dialogSharedClose(area_id,this); },\\"Replace\\": function() { dialogReplaceReplace(area_id); }}}"];',
        ],
    6  =>
        [
            0 => 'if (! window.dialogData) { window.dialogData = {}; } window.dialogData[5] = ["Table Builder","{\\"open\\": function () { dialogTableOpen(area_id,this); },\\n                        \\"width\\": 320, \\"buttons\\": { \\"Cancel\\": function() { dialogSharedClose(area_id,this); },\\"Insert\\": function() { dialogTableInsert(area_id,this); }}}"];',
            1 => 'window.dialogData[5] = ["Table Builder","{\\"open\\": function () { dialogTableOpen(area_id,this); },\\n                        \\"width\\": 320, \\"buttons\\": { \\"Cancel\\": function() { dialogSharedClose(area_id,this); },\\"Insert\\": function() { dialogTableInsert(area_id,this); }}}"];',
        ],
    10 =>
        [
            0 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("tikiimage")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',tikiimage\' : \'tikiimage\' );
    window.CKEDITOR.plugins.add( \'tikiimage\', {
        init : function( editor ) {
            var command = editor.addCommand( \'tikiimage\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    openFgalsWindow(\'tiki-upload_file.php?galleryId=1&view=browse&filegals_manager=editwiki\', true);
                },
                canUndo: false
            }));
            editor.ui.addButton( \'tikiimage\', {
                label : \'Choose or upload images\',
                command : \'tikiimage\',
                icon: editor.config._TikiRoot + \'img/icons/pictures.png\'
            });
        }
    });
}',
            1 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("tikilink")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',tikilink\' : \'tikilink\' );
    window.CKEDITOR.plugins.add( \'tikilink\', {
        init : function( editor ) {
            var command = editor.addCommand( \'tikilink\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    displayDialog( this, 1, editor.name)
                },
                canUndo: false
            }));
            editor.ui.addButton( \'tikilink\', {
                label : \'Wiki Link\',
                command : \'tikilink\',
                icon: editor.config._TikiRoot + \'img/icons/page_link.png\'
            });
        }
    });
}',
            2 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("externallink")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',externallink\' : \'externallink\' );
    window.CKEDITOR.plugins.add( \'externallink\', {
        init : function( editor ) {
            var command = editor.addCommand( \'externallink\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    displayDialog( this, 2, editor.name)
                },
                canUndo: false
            }));
            editor.ui.addButton( \'externallink\', {
                label : \'External Link\',
                command : \'externallink\',
                icon: editor.config._TikiRoot + \'img/icons/world_link.png\'
            });
        }
    });
}',
            3 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("tikihelp")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',tikihelp\' : \'tikihelp\' );
    window.CKEDITOR.plugins.add( \'tikihelp\', {
        init : function( editor ) {
            var command = editor.addCommand( \'tikihelp\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    $.openModal({show: true, remote: "tiki-ajax_services.php?controller=edit&action=help&modal=1&wysiwyg=1&plugins=1&areaId=editwiki"});
                },
                canUndo: false
            }));
            editor.ui.addButton( \'tikihelp\', {
                label : \'WYSIWYG Help\',
                command : \'tikihelp\',
                icon: editor.config._TikiRoot + \'img/icons/help.png\'
            });
        }
    });
}',
            4 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("tikitable")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',tikitable\' : \'tikitable\' );
    window.CKEDITOR.plugins.add( \'tikitable\', {
        init : function( editor ) {
            var command = editor.addCommand( \'tikitable\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    displayDialog( this, 5, editor.name)
                },
                canUndo: false
            }));
            editor.ui.addButton( \'tikitable\', {
                label : \'Table Builder\',
                command : \'tikitable\',
                icon: editor.config._TikiRoot + \'img/icons/table.png\'
            });
        }
    });
}',
        ],
];
