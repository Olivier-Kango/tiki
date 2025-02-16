<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

use Tracker_Definition;

/**
 * A concrete instance of this class represents a single tracker item instance stored in tiki_tracker_item_fields with keys
 *
 * Unfortunately, for legacy reasons it can ALSO take a null value
 * in the itemData parameter of it's constructor.  In which case it
 * represents the entire tracker field stored in tiki_tracker_fields.
 *
 * This is currently being separated out in the AbstractTrackerField
 * class hierarchy.  But some legacy methods remain, such as
 * getOption and getConfiguration.
 */
abstract class AbstractItemField implements ItemFieldInterface, IndexableInterface
{
    /**
     * @var string - ???
     */
    private $baseKeyPrefix = '';

    /**
     * VERY badly named, this can sometimes contain the field data to be rendered!  - benoitg -2024-09-03
    * @see AbstractTrackerField::getLegacyDefinition()
    * @deprecated Use the row, or $this->trackerField->getOption()
     */
    private array $definition;

    /**
     * @var array - complex data about an item. including itemId, trackerId and values of fields by fieldId=>value pairs
     * @deprecated, use itemFieldRow or trackerDefinition
     */
    private $itemData;

    /** Raw row from tiki_tracker_item_fields with keys
     * itemId
     * fieldId
     * value
     *
     */
    private ?array $itemFieldRow = null;

    /** This is just a convenience copy from the trackerField */
    private Tracker_Definition $trackerDefinition;

    protected AbstractTrackerField $trackerField;

    public static function getTrackerFieldClass(): string
    {
        return TrackerFieldGeneric::class;
    }

    /**
     * Initialize the instance with field- and trackerdefinition and item value(s)
     * @param array $fieldInfo - the field definition
     * @param ?array $itemData - itemId/value pair(s).  If empty you can only manipulate informations related to the field.  This seems to be legacy support, only used to call getOption().
     * One should now be able to directly create a AbstractTrackerField instead of an AbstractItemField to getOption(). so any calls to this with a null itemData should be refactored to use it - benoitg - 2024-08-05.
     * This is called with an empty $itemData from TrackerLib::get_field_handler() through Tracker_Field_Factory::getHandler())
     * In practice,
     * @param Tracker_Definition $trackerDefinition - the tracker definition.
     *
     */
    public function __construct(array $fieldInfo, ?array $trackerItemData, Tracker_Definition $trackerDefinition, ?AbstractTrackerField $trackerField = null)
    {
        $this->itemData = $trackerItemData;

        if ($trackerItemData) {
            $allowedKeys = array_flip(['itemId',
                'fieldId',
                'value']);
            //There is still legacy code that pollute the itemData parameter, so we only copy the known keys
            //Yes, the values currently arrive in the fieldInfo array!
            $this->itemFieldRow = array_intersect_key(
                $fieldInfo,
                $allowedKeys
            );
        }

        if (! $trackerField) {
            $class = static::getTrackerFieldClass();
            $trackerField = AbstractTrackerField::getInstanceFromTrackerAndRow($trackerDefinition, $fieldInfo);
        }
        $this->trackerField = $trackerField;
        //This is quite problematic, outside code put keys code relies on in this instead of the raw row from the field definition. (mostly through getConfiguration())  This is incredibly hard to spot and debug - benoitg - 2024-09-10
        //Even putting in trackerField->getLegacyDefinition(); here is not sufficient.   If we really do need tracker field but not tracker item field processing information, this or the tracker item field should get a $context argument, and an API on that context.
        $this->definition = $fieldInfo;
        //$this->definition = $this->trackerField->getLegacyDefinition();
        $this->trackerDefinition = $this->trackerField->getTrackerDefinition();
    }

    public static function filterMDArrayByColumns(array $originalArray, array $allowedKeys): array
    {
        $retVal = [];
        $keyFilter = array_flip($allowedKeys); // We need the values as keys for intersect
        foreach ($originalArray as $key => $value) {
            $retVal[$key] = array_intersect_key($value, $keyFilter);
        }
        return $retVal;
    }

    /**
     * Not implemented here. Its up to to the extending class.
     * @param array $context - ???
     * @return string $renderedContent depending on the $context
     */
    public function renderInput($context = [])
    {
        return 'Not implemented';
    }


    /**
     * Render output for this field.
     * IMPORTANT: This method uses the following $_GET args directly: 'page'
     * @TODO fix it so it does not directly access the $_GET array. Better pass it as a param.
     * @param array $context -keys:
     * <pre>
     * $context = array(
     *      // required
     *      // optional
     *      'url' => 'sefurl', // other values 'offset', 'tr_offset'
     *      'reloff' => true, // checked only if set
     *      'showpopup' => 'y', // wether to show that value in a mouseover popup
     *      'showlinks' => 'n' // NO check for 'y' but 'n'
     *      'list_mode' => 'csv' //
     *      'value' //For some classes, if present, overrides the value of the field rendered
     * );
     * </pre>
     *
     * @return string $renderedContent depending on the $context
     * @throws Exception
     */
    public function renderOutput($context = [])
    {
        // only if this field is marked as link and the is no request for a csv export
        // create the link and if required the mouseover popup
        if ($this->isLink($context)) {
            $itemId = $this->getItemId();
            $query = [];
            if (isset($_GET['page'])) {
                $query['from'] = $_GET['page'];
            }

            $classList = ['tablename'];
            $metadata = \TikiLib::lib('object')->get_metadata('trackeritem', $itemId, $classList);

            require_once('lib/smarty_tiki/modifier.sefurl.php');
            $href = smarty_modifier_sefurl($itemId, 'trackeritem');
            $href .= (strpos($href, '?') === false) ? '?' : '&';
            $href .= http_build_query($query, '', '&');
            $href = rtrim($href, '?&');

            $arguments = [
                'class' => implode(' ', $classList),
                'href' => $href,
            ];
            if (! empty($context['url'])) {
                if ($context['url'] == 'sefurl') {
                    $context['url'] = 'item' . $itemId;
                } elseif (strpos($context['url'], 'itemId') !== false) {
                    $context['url'] = preg_replace('/([&|\?])itemId=?[^&]*/', '\\1itemId=' . $itemId, $context['url']);
                } elseif (isset($context['reloff']) && strpos($context['url'], 'offset') !== false) {
                    $smarty = \TikiLib::lib('smarty');
                    $context['url'] = preg_replace('/([&|\?])tr_offset=?[^&]*/', '\\1tr_offset' . $smarty->tpl_vars['iTRACKERLIST']
                        . '=' . $context['reloff'], $context['url']);
                }
                $arguments['href'] = $context['url'];
            }

            $pre = '<a';
            foreach ($arguments as $key => $value) {
                $pre .= ' ' . $key . '="' . htmlentities($value, ENT_QUOTES, 'UTF-8') . '"';
            }

            // add the html / js for the mouseover popup
            if (isset($context['showpopup']) && $context['showpopup'] == 'y') {
                // check if trackerplugin has set popup fields using the popup parameter
                $pluginPopupFields = isset($context['popupfields']) ? $context['popupfields'] : null;
                $popup = $this->renderPopup($pluginPopupFields);

                if ($popup) {
                    $popup = preg_replace('/<\!--.*?-->/', '', $popup); // remove comments added by log_tpl
                    $popup = preg_replace('/\s+/', ' ', $popup);
                    $pre .= " $popup";
                }
            }

            $pre .= $metadata;
            $pre .= '>';
            $post = '</a>';

            return $pre . $this->renderInnerOutput($context) . $post;
        } else {
            // no link, no mouseover popup. Note: can also be a part of a csv request
            return $this->renderInnerOutput($context);
        }
    }

    /**
     * Render a diff of two values for a tracker field.
     * Will use the item value if $context['oldValue'] is not supplied
     *
     * @param array $context [value, oldValue, etc as for renderOutput]
     * @return string|string[] html, usually a table
     */
    public function renderDiff($context = [])
    {
        if ($context['oldValue']) {
            $old = $context['oldValue'];
        } else {
            $old = '';
        }
        if (! empty($context['value'])) {
            $new = $context['value'];
        } else {
            $new = $this->getValue('');
        }
        $innerOutputContext = ['list_mode' => 'csv'];
        if (isset($context['history'])) {
            $innerOutputContext['history'] = $context['history'];
        }
        if (isset($context['renderedOldValue'])) {
            $old = $context['renderedOldValue'];
        } else {
            $old_definition = $this->definition;
            $key = 'ins_' . $this->getConfiguration('fieldId');
            if ($this->getConfiguration('type') === 'e' && is_string($old)) {   // category fields need an array input
                $old = explode(',', $old);
            }
            $this->definition = array_merge($this->definition, $this->getFieldData([$key => $old]));
            $this->itemData[$this->getConfiguration('fieldId')] = $old;
            $old = $this->renderInnerOutput($innerOutputContext);
            $old = str_replace(['%%%', '<br>', '<br/>'], ["\n", ' ', ' '], $old);
            $this->definition = $old_definition;
            $this->itemData[$this->getConfiguration('fieldId')] = $new;
        }
        $new = $this->renderInnerOutput($innerOutputContext);
        $new = str_replace(['%%%', '<br>', '<br/>'], ["\n", ' ', ' '], $new);
        if (empty($context['diff_style'])) {
            $context['diff_style'] = 'inlinediff';
        }
        require_once('lib/diff/difflib.php');
        $diff = diff2($old, $new, $context['diff_style']);
        $result = '';

        if (is_array($diff)) {
            // unidiff mode
            foreach ($diff as $part) {
                if ($part["type"] == "diffdeleted") {
                    foreach ($part["data"] as $chunk) {
                        $result .= "<blockquote>- $chunk</blockquote>";
                    }
                }
                if ($part["type"] == "diffadded") {
                    foreach ($part["data"] as $chunk) {
                        $result .= "<blockquote>+ $chunk</blockquote>";
                    }
                }
            }
        } else {
            $result = strpos($diff, '<tr') === 0 ? '<table>' . $diff . '</table>' : $diff;
            $result = preg_replace('/<tr class="diffheader">.*?<\/tr>/', '', $result);
            $result = str_replace('<table>', '<table class="table">', $result);
        }
        return $result;
    }

    public function watchCompare($old, $new)
    {
        $name = $this->getConfiguration('name');
        $hidden = $this->getConfiguration('isHidden', 'n');
        $is_visible = $hidden === 'n' || $hidden === 'r';   // not hidden or only hidden from rss feeds

        if (! $is_visible) {
            return '';
        }

        if ($old) {
            // split old value by lines
            $lines = explode("\n", $old);
            // mark every old value line with standard email reply character
            $old_value_lines = '';
            foreach ($lines as $line) {
                $old_value_lines .= '> ' . $line . "\n";
            }
            return "[-[$name]-]:\n--[Old]--:\n$old_value_lines\n\n*-[New]-*:\n$new";
        } else {
            return "[-[$name]-]:\n$new";
        }
    }

    public function watchCompareList(array $old, array $new, \closure $describeListItem): string
    {
        $old = array_filter($old);
        $new = array_filter($new);

        $output = "[-[" . $this->getConfiguration('name') . "]-]:\n";

        $added = array_diff($new, $old);
        $removed = array_diff($old, $new);
        $remaining = array_diff($new, $added);

        if (count($added) > 0) {
            $output .= "  -[Added]-:\n";
            foreach ($added as $item) {
                $output .= '    ' . $describeListItem($item) . "\n";
            }
        }
        if (count($removed) > 0) {
            $output .= "  -[Removed]-:\n";
            foreach ($removed as $item) {
                $output .= '    ' . $describeListItem($item) . "\n";
            }
        }
        if (count($remaining) > 0) {
            $output .= "  -[Remaining]-:\n";
            foreach ($remaining as $item) {
                $output .= '    ' . $describeListItem($item) . "\n";
            }
        }

        return $output;
    }

    private function isLink($context = [])
    {
        $type = $this->getConfiguration('type');
        if ($type == 'x') {
            return false;
        }

        if ($this->getConfiguration('showlinks', 'y') == 'n') {
            return false;
        }

        if (isset($context['showlinks']) && $context['showlinks'] == 'n') {
            return false;
        }

        if (isset($context['list_mode']) && $context['list_mode'] == 'csv') {
            return false;
        }

        if (isset($context['linkToItems']) && $context['linkToItems'] == 'y') {
            return true;
        }

        if (! empty($context['isMain_context'])) {
            return false;
        }

        $itemId = $this->getItemId();
        if (empty($itemId)) {
            return false;
        }

        try {
            $itemObject = \Tracker_Item::fromInfo($this->itemData);
            if (
                $this->getConfiguration('isMain', 'n') == 'y'
                && ($itemObject->canView()  || $itemObject->getPerm('comment_tracker_items'))
            ) {
                return (bool) $this->getItemId();
            }
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
            return false;
        }

        return false;
    }


    /**
     * Create the html/js to show a popupwindow on mouseover when the trackeritem has a field with link enabled.
     * The formatting is done via smarty based on 'trackeroutput/popup.tpl'
     * @param array $pluginPopupFields - array with fieldids set by trackerlist plugin. if not set the tracker defaults will be used.
     * @return NULL|string $popupHtml
     */
    private function renderPopup($pluginPopupFields = null)
    {
        // support of trackerlist plugin popup field - if popup is set and has fields - show the fields as defined and in their order
        // if parameter popup is set but without fields show no popup
        // note: the popup template code in wikiplugin_trackerlist.tpl does not seem to be used at all - only the flag $showpopup
        if ($pluginPopupFields && is_array($pluginPopupFields)) {
            $fields = $pluginPopupFields;
        } else {
            // plugin trackerlist not involved
            $fields = $this->trackerDefinition->getPopupFields();
        }

        if (empty($fields)) {
            return null;
        }

        $factory = $this->trackerDefinition->getFieldFactory();

        // let's honor doNotShowEmptyField
        $tracker_info = $this->trackerDefinition->getInformation();
        $doNotShowEmptyField = $tracker_info['doNotShowEmptyField'];

        $popupFields = [];
        $item = \Tracker_Item::fromInfo($this->itemData);

        foreach ($fields as $id) {
            if (! $item->canViewField($id)) {
                continue;
            }
            $field = $this->trackerDefinition->getField($id);

            if (! (isset($field) && count($field) > 0)) {
                continue;
            }

            if (! isset($this->itemData[$field['fieldId']])) {
                if (! empty($this->itemData['field_values'])) {
                    foreach ($this->itemData['field_values'] as $fieldVal) {
                        if ($fieldVal['fieldId'] == $id) {
                            if (isset($fieldVal['value'])) {
                                $this->itemData[$field['fieldId']] = $fieldVal['value'];
                            }
                        }
                    }
                } else {
                    $this->itemData[$field['fieldId']] = \TikiLib::lib('trk')->get_item_value(
                        $this->trackerDefinition->getConfiguration('trackerId'),
                        $this->itemData['itemId'],
                        $id
                    );
                }
            }
            $handler = $factory->getHandler($field, $this->itemData);

            if ($handler && ($doNotShowEmptyField !== 'y' || ! empty($this->itemData[$field['fieldId']]))) {
                $field = array_merge($field, $handler->getFieldData());
                $popupFields[] = $field;
            }
        }

        $smarty = \TikiLib::lib('smarty');
        $smarty->assign('popupFields', $popupFields);
        $smarty->assign('popupItem', $this->itemData);
        return trim($smarty->fetch('trackeroutput/popup.tpl'));
    }

    /**
     * return the html for the output of a field without link, prepend...
     * @param array $context - key 'list_mode' defines wether to output for a list or a simple value
     * @return string $html
     */
    protected function renderInnerOutput($context = [])
    {
        $value = $this->getValue();
        $pvalue = $this->getConfiguration('pvalue', $value);

        if (isset($context['list_mode']) && $context['list_mode'] === 'csv') {
            $default = ['CR' => '%%%', 'delimitorL' => '"', 'delimitorR' => '"'];
            $context = array_merge($default, $context);
            $value = str_replace(["\r\n", "\n", '<br />', $context['delimitorL'], $context['delimitorR']], [$context['CR'], $context['CR'], $context['CR'], $context['delimitorL'] . $context['delimitorL'], $context['delimitorR'] . $context['delimitorR']], $value);
            return $value;
        } else {
            return $pvalue;
        }
    }

    /**
     * Return the field name to be used for data retrieval during item form submission
     */
    public function getInsertId()
    {
        return 'ins_' . $this->trackerField->getId();
    }

    /**
     * Return the HTML input name to be rendered in the item form for this field
     *
     * @return string
     */
    public function getHTMLFieldName()
    {
        $insertId = $this->getInsertId();

        /**
         * Postfix with [] for multivalued fields
         * Applicable for:
         * Category, CountrySelector, DynamicList, ItemLink, Kaltura, MultiSelect, UserSelector
         *
         * NOTE: While the Relation field allows multiple values as well, we don't explicitly set the [] postfix for it,
         * because it needs specific keys, notably 'meta' and 'objects' in its value.
         */
        if (in_array($this->trackerField->getOption('type'), ['e', 'y', 'kaltura', 'M', 'u']) || (in_array($this->trackerField->getOption('type'), ['r', 'w']) && $this->trackerField->getOption('selectMultipleValues') == '1')) {
            $insertId .= '[]';
        }
        return $insertId;
    }

    protected function getFilterId()
    {
        return 'filter_' . $this->trackerField->getId();
    }

    protected function getFieldId()
    {
        return $this->trackerField->getId();
    }


    /** @deprecated This is only used by the FieldController.php, and TrackerItemSource.php, and should be refactored.  The structure it exposes is very close, but not the same as the raw database row of the field definition
     * @see AbstractTrackerField::getLegacyDefinition()
     */
    public function getFieldDefinition()
    {
        return $this->definition;
    }

    /**
     * Gets data from the tracker field's configuration OR from keys passed from outside the constructor
     *
     * i.e. from the field definition in the database plus what is returned by the field's row
     *
     * @deprecated Badly named, and ambiguous (there could be key colisions between the configuration and the data).  Tormally you want to use trackerField->getOption() or trackerField->getLegacyDefinition()
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfiguration($key, $default = false)
    {
        if (isset($this->definition[$key])) {
            return $this->definition[$key];
        } else {
            //I didn't want to activate this in a stable branch, but trying to access an unknown key here probably indicates a bug - benoitg - 2024-09-10
            //trigger_error("Deprecated getConfiguration tried to access an unknown key: $key", E_USER_WARNING);
            //The following is to debug removing fieldInfo from the definition
            //throw new \Error("Unknown key: $key");
            return $default;
        }
    }

    /**
     * Return the value for this item field. Depending on fieldtype that could be the itemId of a linked field.
     * Value is looked for in:
     * $this->itemData[fieldNumber]
     * $this->definition['value']
     * $this->itemData['fields'][permName]
     * @param mixed $default the field value used if none is set
     * @return mixed field value
     */
    public function getValue($default = '')
    {
        $key = $this->getConfiguration('fieldId');

        if (isset($this->itemData[$key])) {
            $value = $this->itemData[$key];
        } elseif (isset($this->definition['value'])) {
            $value = $this->definition['value'];
        } elseif (isset($this->itemData['fields'][$this->getConfiguration('permName')])) {
            $value = $this->itemData['fields'][$this->getConfiguration('permName')];
        } else {
            $value = null;
        }

        return $value === null ? $default : $value;
    }

    protected function getItemId()
    {
        return $this->getData('itemId');
    }

    /**
     * Get the item's data
     *
     * @deprecated, searches into another opaque structure.
     * @see $itemData
     * @return array
     */
    protected function getItemData()
    {
        return $this->itemData;
    }

    /**
     *
     * @deprecated, searches in $itemData, which may or may not return the same data as getFieldData() or getValue()
     */
    protected function getData($key, $default = false)
    {
        return isset($this->itemData[$key]) ? $this->itemData[$key] : $default;
    }

    /**
     * Get the tracker field object corresponding to this tracker item field object
     */
    protected function getTrackerFieldInstance(): AbstractTrackerField
    {
        return $this->trackerField;
    }
    /**
     * Backward compatibility return option from the options array.
     * @see AbstractTrackerField::getOption
     * @deprecated Use the trackerField->getOption() instead
     */
    public function getOption(int|string $key, $default = false)
    {
        return $this->trackerField->getOption($key, $default);
    }

    /**
     * Get the tracker definition object
     */
    protected function getTrackerDefinition(): Tracker_Definition
    {
        return $this->trackerDefinition;
    }

    public function isMainField(): bool
    {
        //We use this instead of 'isMain' because there is no constraint enforced that there is only one field per tracker set 'isMain'.
        return ($this->trackerField->getId() == $this->trackerDefinition->getMainFieldId()) ? true : false;
    }

    protected function renderTemplate($file, $context = [], $data = [])
    {
        $smarty = \TikiLib::lib('smarty');

        //ensure value is set, because it may not always come from definition
        if (! isset($this->definition['value'])) {
            $this->definition['value'] = $this->getValue();
        }

        $smarty->assign('field', $this->definition);
        $smarty->assign('context', $context);
        $smarty->assign('item', $this->getItemData());
        $smarty->assign('data', $data);

        return $smarty->fetch($file, $file);
    }

    public function getDocumentPart(\Search_Type_Factory_Interface $typeFactory)
    {
        $baseKey = $this->getBaseKey();
        return [
            $baseKey => $typeFactory->sortable($this->getValue()),
        ];
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey];
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey => 'sortable'];
    }

    public function getGlobalFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey => true];
    }

    /** These need to move to AbstractTrackerField - benoitg - 2024-09-04 */
    public function getBaseKey()
    {
        global $prefs;
        $indexKey = 'permName';
        return 'tracker_field_' . $this->baseKeyPrefix . $this->getConfiguration($indexKey);
    }

    public function setBaseKeyPrefix($prefix)
    {
        $this->baseKeyPrefix = $prefix;
    }

    /**
     * Default implementation is to replace the value
     */
    public function addValue($value)
    {
        return $value;
    }

    /**
     * Default implementation is to remove the value
     */
    public function removeValue($value)
    {
        return '';
    }
}
