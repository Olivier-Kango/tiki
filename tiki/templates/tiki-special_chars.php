<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
<title>Special Character Input</title>
</head>
<body style="background:#101070; color:white;">
<script language="Javascript" type="text/javascript">
var target;
function handleListChange(theList) {
    var numSelected = theList.selectedIndex;
    if (numSelected != 0) {
        document.getElementById('spec').value += theList.options[numSelected].value;
        theList.selectedIndex = 0;
    }
}
</script>

<form name="characters">
<table border="0" cellspacing="0" cellpadding="2">
  <!--
  <tr>
    <td align="left">
      <p class="smallheader">Select the character then copy and paste them from the textarea.</p>
    </td>
  </tr>
  -->
  <tr>
    <td align="left">
      <table border="0" cellspacing="0"
cellpadding="0">
        <tr>
          <td align="center">
            <select name="a"
onchange="handleListChange(this)">
              <option value="a" selected> a </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
            </select>
          </td>
          <td align="center"> 
            <select name="e"
onchange="handleListChange(this)">
              <option value="e" selected> e </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
            </select>
          </td>
          <td align="center"> 
            <select name="i"
onchange="handleListChange(this)">
              <option value="i" selected> i </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
            </select>
          </td>
          <td align="center"> 
            <select name="o"
onchange="handleListChange(this)">
              <option value="o" selected> o </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
            </select>
          </td>
          <td align="center"> 
            <select name="u"
onchange="handleListChange(this)">
              <option value="u" selected> u </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
            </select>
          </td>
          <td align="center"> 
            <select name="Other"
onchange="handleListChange(this)">
              <option value="misc" selected> Other
</option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
              <option value="�"> � </option>
            </select>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td align="left" class="fixed">
      <input type="text" id='spec' />
  <input type="button" class="button"
onclick="javascript:window.opener.document.getElementById('<?php print($_REQUEST["area_name"]);?>').value=window.opener.document.getElementById('<?php print($_REQUEST["area_name"]);?>').value+getElementById('spec').value;" name="ins" value="ins" />
      <input type="button" class="button"
onclick="window.close();" name="close" value="close" />
    </td>
  </tr>
</table>
</form>
</body>

