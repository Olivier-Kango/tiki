<?php

/**
 * Tiki modules
 * @package modules
 * @subpackage tiki
 */

if (!defined('DEBUG_MODE')) { die(); }

/**
 * Convert MimePart message parts to IMAP-compatible BODYSTRUCTURE
 * @subpackage tiki/functions
 * @param ZBateson\MailMimeParser\Message\Part\MimePart $part the mime message part
 * @param int $part_num the mime message part number
 * @return array
 */
if (!hm_exists('tiki_mime_part_to_bodystructure')) {
function tiki_mime_part_to_bodystructure($part, $part_num = '0') {
    $content_type = explode('/', $part->getContentType());
    $header = $part->getHeader('Content-Type');
    $attributes = [];
    foreach (['boundary', 'charset', 'name'] as $param) {
        if ($header->hasParameter($name)) {
            $attributes[$name] = $header->getValueFor($name);
        }
    }
    $header = $part->getHeader('Content-Disposition');
    $file_attributes = [];
    if ($header) {
        $file_attributes[$header->getValue()] = [];
        if ($header->getValueFor('filename')) {
            $file_attributes[$header->getValue()][] = 'filename';
            $file_attributes[$header->getValue()][] = $header->getValueFor('filename');
        }
    }
    $result = [$part_num => [
        'type' => $content_type[0],
        'subtype' => $content_type[1],
        'attributes' => $attributes,
        "id" => $part->getContentId(),
        'description' => false,
        'encoding' => $part->getContentTransferEncoding(),
        'size' => strlen($part->getContent()),
        'lines' => $part->isTextPart() ? substr_count($part->getContent(), "\n") : false,
        'md5' => false,
        'disposition' => $part->getContentDisposition(false),
        'file_attributes' => $file_attributes,
        'language' => false,
        'location' => false,
    ]];
    if ($part->getChildCount() > 0) {
        $result[$part_num]['subs'] = [];
        foreach ($part->getChildParts() as $i => $subpart) {
            $subpart_num = $part_num.'.'.($i+1);
            $result[$part_num]['subs'] = array_merge($result[$part_num]['subs'], tiki_mime_part_to_bodystructure($subpart, $subpart_num));
        }
    }
    return $result;
}}
