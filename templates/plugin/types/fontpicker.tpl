<link rel="stylesheet" href="lib/jsfontpicker/fontpicker.css">
<input value="{$val|escape}" class="form-control{$groupClass}" id="{$inputId|escape}" type="text" name="params[{$paramName|escape}]"{$dataAttribute}>
<script type="module">
    {literal}
        import initFontPicker from "@jquery-tiki/tiki-fontpicker_init";
    {/literal}
    initFontPicker("#{$inputId|escape:'javascript'}", { systemFontsOnly: true });
</script>
