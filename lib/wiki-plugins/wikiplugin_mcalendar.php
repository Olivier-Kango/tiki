<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class MCalendar
{
   // GMT correlation initiated on August 11, 3114 BC (Gregorian).
   // For another popular GMT correlation initiated on August 13, 3114 BC
   // set MCalendar->StartLongCount = 584285 before calling function MCalendar->Maya()
    public $StartLongCount = 584283;
    public $LongCount = [
                           "Baktun" => 13,
                           "Katun" => 0,
                           "Tun" => 0,
                           "Winal" => 0,
                           "Kin" => 0
                           ];
    public $LongKin;
    public $Tzolkin = [
                        "Kin13" => 0,
                        "Kin20" => 0
                         ];
    public $TzolkinNames = [
                      "AHAU", "IMIX", "IK", "AKBAL", "KAN",
                      "CHICCHAN", "CIMI", "MANIK", "LAMAT",
                      "MULUC", "OC", "CHUEN", "EB", "BEN",
                      "IX", "MEN", "CIB", "CABAN", "ETZNAB",
                      "CAUAC"
                     ];
    public $Haab = [
                      "Winal" => 0,
                      "Kin" => 0
                      ];
    public $HaabNames = [
                     "POP", "WO", "SIP", "SOTZ",
                     "SEK", "XUL", "YAXKIN", "MOL",
                     "CHEN", "YAX", "SAC", "KEH", "MAK",
                     "KANKIN", "MUWAN", "PAX", "KAYAB",
                     "KUMKU", "WAYEB"
                     ];
    public $Gregorian = [
                           "Year" => 2008,
                           "Month" => 11,
                           "Day" => 25
                           ];
    public $Julian = 0;


    public function setLongKin()
    {
        $this->LongKin = $this->LongCount["Baktun"] * 144000
                    + $this->LongCount["Katun"] * 7200
                    + $this->LongCount["Tun"] * 360
                    + $this->LongCount["Winal"] * 20
                    + $this->LongCount["Kin"];
        return $this->LongKin;
    }


    public function setJulian()
    {

        $Year = $this->Gregorian["Year"];
        $Month = $this->Gregorian["Month"];
        $Day = $this->Gregorian["Day"];

        if ($Month < 3) {
            $Month += 12;
            $Year -= 1;
        };

        $a = floor($Year / 100);
        $b = 2 - $a + floor($a / 4);
        $j = floor(365.25 * ($Year + 4716)) + floor(30.6001 * ($Month + 1)) + $Day + $b - 1524;

        $this->Julian = $j;
        return $this->Julian;
    }


    public function Maya()
    {

        $this->setJulian();

        $days = $this->Julian - $this->StartLongCount;
        $xdays = $days;

        $baktun = floor($xdays / 144000);
        $this->LongCount["Baktun"] = $baktun;

        $xdays -= $baktun * 144000;

        $katun = floor($xdays / 7200);
        $this->LongCount["Katun"] = $katun;

        $xdays -= $katun * 7200;

        $tun = floor($xdays / 360);
        $this->LongCount["Tun"] = $tun;

        $xdays -= $tun * 360;

        $winal = floor($xdays / 20);
        $this->LongCount["Winal"] = $winal;

        $kin = $xdays - ($winal * 20);
        $this->LongCount["Kin"] = $kin;

        $xdays = $days - (260 * (floor($days / 260)));
        $tzolradical = 4 + $xdays - (13 * (floor(($xdays + 3) / 13)));
        $this->Tzolkin["Kin13"] = $tzolradical;

        $tzolkin = $xdays - (20 * (floor(($xdays) / 20)));
        $this->Tzolkin["Kin20"] = $tzolkin;

        $xdays = $days + 348 - (365 * (floor(($days + 348) / 365)));
        $haabkin = $xdays - (20 * (floor($xdays / 20)));
        $this->Haab["Kin"] = $haabkin;


        if ($xdays > 360) {
            $this->Haab["Winal"] = 18;
        }

        $haabwinal = floor($xdays / 20);
        $this->Haab["Winal"] = $haabwinal;

        $this->setLongKin();
    }
}

function wikiplugin_mcalendar_info()
{
    return [
        'name' => tra('Mayan Calendars'),
        'documentation' => 'PluginMCalendarInfo',
        'description' => tra('Convert a Gregorian date to a Mayan calendar date'),
        'prefs' => ['wikiplugin_mcalendar'],
        'iconname' => 'calendar',
        'introduced' => 4,
        'params' => [
            'template' => [
                'required' => false,
                'name' => tra('Template'),
                'since' => '4.0',
                'description' => tra('You must use the variable substitution.')
                    . '<br />'
                    . tra('LongCount: ')
                    . '~np~<code>%baktun%</code>, <code>%katun%</code>, <code>%tun%</code>, <code>%winal%</code>, <code>%kin%</code>~/np~'
                    . '<br />'
                    . tra('Tzolkin: ')
                    . '~np~<code>%tzolkin13%</code>, <code>%tzolkin20%</code>, <code>%tzolkin20name%</code>~/np~'
                    . '<br />'
                    . tra('Haab: ')
                    . '~np~<code>%haabkin%</code>, <code>%haabwinal%</code>, <code>%haabwinalname%</code>~/np~'
                    . '<br />'
                    . tra('Misc: ')
                    . '~np~<code>%longkin%</code>, <code>%julianday%</code>~/np~'
                    . '<br /><br />'
                    . tra('Example: template ')
                    . '~np~<code>"%baktun%.%katun%.%tun%.%winal%.%kin%, %tzolkin13% %tzolkin20name%, %haabkin% %haabwinalname%"</code>~/np~'
                    . tr('for %022.05.2009%1 will return 12.19.16.6.11, 8 CHUEN, 9 SIP.', '<code>', '</code>')
                    . '<br /><br />'
                    . tra('Default template: ')
                    . '~np~<code>%baktun%.%katun%.%tun%.%winal%.%kin% %tzolkin13% %tzolkin20name% %haabkin% %haabwinalname%</code>~/np~',
            ],
            'grdate' => [
                'required' => false,
                'name' => tra('Gregorian date'),
                'description' => tr(
                    'Gregorian date for convert. Format: %0DD.MM.YYYY%1. Default: Today\'s date',
                    '<code>',
                    '</code>'
                ),
                'since' => '4.0',
            ],
        ],
    ];
}

function wikiplugin_mcalendar($data, $params)
{
    global $tikilib;

    extract($params, EXTR_SKIP);

    $out = '';

    if (! isset($template)) {
        $template = '%baktun%.%katun%.%tun%.%winal%.%kin% %tzolkin13% %tzolkin20name% %haabkin% %haabwinalname%';
    }

    $template = strtolower($template);

    // Set default date to Today.
    if (! isset($grdate)) {
        $today = date('d.m.Y');
    } else {
        $today = $grdate;
    }

    // If date is not in DD.MM.YYYY format display error message
    if (! preg_match('/\d{1,2}\.\d{1,2}\.\d{4}/', $today)) {
        $error = "<span class='attention'>" . $today . tra(" is not a valid date format. should be dd.mm.yyyy") . "</span>";
        return $error;
    }

    $MCal = new MCalendar();
    list($MCal->Gregorian["Day"], $MCal->Gregorian["Month"], $MCal->Gregorian["Year"]) = explode(".", $today);

    $MCal->Maya();

    $vars = ['%baktun%', '%katun%', '%tun%', '%winal%', '%kin%',
                  '%tzolkin13%', '%tzolkin20%', '%tzolkin20name%',
                  '%haabkin%', '%haabwinal%', '%haabwinalname%',
                  '%longkin%', '%julianday%'];

    $values = [$MCal->LongCount["Baktun"], $MCal->LongCount["Katun"],
                    $MCal->LongCount["Tun"], $MCal->LongCount["Winal"], $MCal->LongCount["Kin"],
                    $MCal->Tzolkin["Kin13"], $MCal->Tzolkin["Kin20"], $MCal->TzolkinNames[$MCal->Tzolkin["Kin20"]],
                    $MCal->Haab["Kin"], $MCal->Haab["Winal"], $MCal->HaabNames[$MCal->Haab["Winal"]],
                    $MCal->LongKin, $MCal->Julian];

    $out = str_replace($vars, $values, $template);

    return $out;
}
