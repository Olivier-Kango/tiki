<?php
// File header

include('lang/en/language.php'); // Needed for providing a sensible default text for untranslated strings with context like : "edit_C(verb)"
$lang_current = array(
/* file1, file3 */
// "First string" => "First string",
/* file2 */
// "Second string" => "Second string",
/* file3 */
"Used string" => "Another translation",
/* file5, file1 */
"Translation is the same as English string" => "Translation is the same as English string",
/* file4 */
// "etc" => "etc",
);
$lang = array_merge($lang, $lang_current);
