<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Relation_Controller
{
    private $access;

    public function setUp()
    {
        $this->access = TikiLib::lib('access');
    }

    /**
     * Function to toggle relation. Sets relation when none set and then if there is a relation, it unsets.
     * @param $input
     * @return array with "relationId" as param. Null if relation is removed.
     * @throws Exception
     * @throws Services_Exception
     */
    public function action_toggle($input)
    {
        $relation = $input->relation->none();
        $target_type = $input->target_type->none();
        $target_id = $input->target_id->none();
        $source_type = $input->source_type->none();
        $source_id = $input->source_id->none();

        // todo - set perms mapping ability for relations to manage permissions on different types of relations.
        // currently this is only used by the multilike feature so tiki_p_post_comments permission is noted in documentation
        $this->access->check_permission('tiki_p_post_comments', 'Post a comment', $target_type, $target_id);

        // ensure the target, source, and relation info are passed to the service
        if (! $target_type || ! $target_id || ! $source_type || ! $source_id || ! $relation) {
            throw new Services_Exception(tr('Invalid input'), 400);
        }

        $relationlib = TikiLib::lib('relation');
        $tx = TikiDb::get()->begin();
        $relationId = $relationlib->get_relation_id($relation, $source_type, $source_id, $target_type, $target_id);

        // If there is not an existing relation, add the relation and trigger the add relation event.
        if (! $relationId) {
            $relationId = $relationlib->add_relation($relation, $source_type, $source_id, $target_type, $target_id);
            TikiLib::events()->trigger(
                'tiki.relation.add',
                [
                    'id' => $relationId,
                    'target_type' => $target_type,
                    'target_id' => $target_id,
                    'source_type' => $source_type,
                    'source_id' => $source_id,
                    'relation' => $relation,
                ]
            );
        } else {
            //if there is a relation, remove the relation, trigger the event, and set the relationId to null
            $relationlib->remove_relation($relationId);
            TikiLib::events()->trigger(
                'tiki.relation.remove',
                [
                    'id' => $relationId,
                    'target_type' => $target_type,
                    'target_id' => $target_id,
                    'source_type' => $source_type,
                    'source_id' => $source_id,
                    'relation' => $relation,
                ]
            );
            $relationId = null; // set the
        }

        $tx->commit();

        //return the relationId (new relation if added, null if removed)
        return [
            'relation_id' => $relationId,
        ];
    }

    /**
     * Function to toggle relation. Sets relation when none set and then if there is a relation, it unsets.
     * @param $input
     * @return array with "relationId" as param. Null if relation is removed.
     * @throws Exception
     * @throws Services_Exception
     */
    public function action_toggle_group($input)
    {
        $relation_prefix = $input->relation_prefix->none();
        $relation = $input->relation->none();
        $target_type = $input->target_type->none();
        $target_id = $input->target_id->none();
        $source_type = $input->source_type->none();
        $source_id = $input->source_id->none();

        // todo - set perms mapping ability for relations to manage permissions on different types of relations.
        $this->access->check_permission('tiki_p_post_comments', 'Post a comment', $target_type, $target_id);

        // ensure the target, source, and relation info are passed to the service
        if (! $target_type || ! $target_id || ! $source_type || ! $source_id || ! $relation_prefix) {
            throw new Services_Exception(tr('Invalid input'), 400);
        }

        $relationlib = TikiLib::lib('relation');
        $tx = TikiDb::get()->begin();
        $relations = $relationlib->get_relations_by_prefix($relation_prefix, $source_type, $source_id, $target_type, $target_id);

        // If there is not an existing relation, add the relation and trigger the add relation event.
        $relationWasSelected = false;
        if (! empty($relations)) {
            foreach ($relations as $rel) {
                if ($rel['relation'] == $relation) {
                    //sets whether the relation was previously selected and is being toggled off
                    $relationWasSelected = true;
                }
                //if there is a relation, remove the relation, trigger the event, and set the relationId to null
                $relationlib->remove_relation($rel['relationId']);
                TikiLib::events()->trigger(
                    'tiki.relation.remove',
                    [
                        'id' => $rel['relation_id'],
                        'target_type' => $target_type,
                        'target_id' => $target_id,
                        'source_type' => $source_type,
                        'source_id' => $source_id,
                        'relation' => $relation,
                    ]
                );
            }
        }

        $relationId = null; // set the return id

        //only adds relation if it hadn't previously been selected. If it was selected, then the user toggled it off.
        if (! $relationWasSelected) {
            $relationId = $relationlib->add_relation($relation, $source_type, $source_id, $target_type, $target_id);
            TikiLib::events()->trigger(
                'tiki.relation.add',
                [
                    'id' => $relationId,
                    'target_type' => $target_type,
                    'target_id' => $target_id,
                    'source_type' => $source_type,
                    'source_id' => $source_id,
                    'relation' => $relation,
                ]
            );
        }

        $tx->commit();

        //return the relationId (new relation if added, null if removed)
        return [
            'relation_id' => $relationId,
        ];
    }

    /**
     * Store or remove one side of a cross-Tiki relation.
     * The caller passes the qualifier this site's Relation field uses.
     * source_object is the local tracker item; target_object is the other site's item id, already namespaced.
     */
    public function action_mirror($input)
    {
        $relation = $input->relation->text();
        $sourceType = $input->source_type->text();
        $sourceObject = $input->source_object->text();
        $targetType = $input->target_type->text();
        $targetObject = $input->target_object->text();
        $remove = (bool) $input->remove->int();
        $title = $input->title->text();
        $href = $input->href->text();

        if (! $relation || $sourceType !== 'trackeritem' || ! $sourceObject || ! $targetType || ! $targetObject || ObjectLib::isRemoteObjectItemId($sourceObject)) {
            throw new Services_Exception(tr('Invalid input'), 400);
        }

        $item = Tracker_Item::fromId($sourceObject);
        if (! $item || ! $item->canModify()) {
            throw new Services_Exception_Denied();
        }

        $relationlib = TikiLib::lib('relation');
        if ($title !== '' && ObjectLib::isRemoteObjectItemId($targetObject)) {
            TikiLib::lib('object')->cacheObjectLabel($targetType, $targetObject, $title, $href);
        }

        $definition = $item->getDefinition();
        $fieldId = $definition ? $definition->getRelationField($relation) : null;
        if ($fieldId) {
            $trackerId = $item->getInfo()['trackerId'];
            $raw = TikiLib::lib('trk')->get_item_value($trackerId, $sourceObject, $fieldId);
            $lines = array_values(array_filter(array_map('trim', explode("\n", (string) $raw)), 'strlen'));
            $line = $targetType . ':' . $targetObject;
            if ($remove) {
                $lines = array_values(array_diff($lines, [$line]));
            } elseif (! in_array($line, $lines, true)) {
                $lines[] = $line;
            }
            TikiLib::lib('trk')->modify_field($sourceObject, $fieldId, implode("\n", $lines));
        }

        $existing = $relationlib->get_relation_id($relation, $sourceType, $sourceObject, $targetType, $targetObject);
        if ($remove) {
            if ($existing) {
                $relationlib->remove_relation($existing);
            }
        } elseif (! $existing) {
            $relationlib->add_relation($relation, $sourceType, $sourceObject, $targetType, $targetObject, false, $fieldId ?: null);
        } else {
            TikiLib::lib('tiki')->refresh_index($sourceType, $sourceObject);
        }

        return ['ok' => 1];
    }
}
