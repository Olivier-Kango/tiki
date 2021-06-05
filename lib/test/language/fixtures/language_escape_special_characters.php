<?php
// File header

include('lang/en/language.php'); // Needed for providing a sensible default text for untranslated strings with context like : "edit_C(verb)"
$lang_current = array(
// "First string" => "First string",
// "Second string" => "Second string",
"Used string" => "Another translation",
"Translation is the same as English string" => "Translation is the same as English string",
// "etc" => "etc",
"Congratulations!\n\nYour server can send emails.\n\n" => "Gratulation!\n\nDein Server kann Emails senden.\n\n",
// "Handling actions of plugin \"%s\" failed" => "Handling actions of plugin \"%s\" failed",
);
$lang = array_replace($lang, $lang_current);
