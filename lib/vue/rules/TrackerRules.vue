<template>
    <div class="tracker-rules">
        <div class="card mb-3">
            <div class="card-header">
                Conditions
            </div>
            <div class="card-body conditions">
                <ui-predicate v-model="conditionsData" :columns="conditionsColumns" :ui="ui" @changed="onChangeConditions" @initialized="onChangeConditions"/>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">
                Actions
            </div>
            <div class="card-body actions">
                <ui-predicate v-model="actionsData" :columns="actionsColumns" :ui="ui" @changed="onChangeActions" @initialized="onChangeActions"/>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">
                Else
                <button class="btn btn-info btn-xs float-end tips" @click="onInvertActionsClicked" title='Set "Else" to be the opposite of "Actions"'>
                    Invert Actions
                </button>
            </div>
            <div class="card-body else">
                <ui-predicate v-if="renderElseComponent" v-model="elseData" :columns="actionsColumns" :ui="ui" @changed="onChangeElse" @initialized="onChangeElse"/>
            </div>
        </div>

        <div class="card">
            <div class="card-body tips">
                <h5 class="card-title">Tips</h5>
                <div class="card=text">
                    <p>Tips: Use <code>alt + click</code> to create a sub-group.</p>
                    <p>
                        <button class="btn btn-warning btn-sm" @click="onResetClicked">Clear All</button>
                    </p>
                </div>
            </div>
        </div>

        <div class="card d-none">
            <div class="card-header">
                Conditions Output
            </div>
            <div class="card-content">
                <textarea name="conditions" class="form-control" readonly="readonly">{{ conditionsoutput }}</textarea>
            </div>
        </div>
        <div class="card d-none">
            <div class="card-header">
                Actions Output
            </div>
            <div class="card-content">
                <textarea name="actions" class="form-control" readonly="readonly">{{ actionoutput }}</textarea>
            </div>
        </div>
        <div class="card d-none">
            <div class="card-header">
                Else Output
            </div>
            <div class="card-content">
                <textarea name="else" class="form-control" readonly="readonly">{{ elseoutput }}</textarea>
            </div>
        </div>
    </div>
</template>

<script>
    Vue.use(UIPredicate);

    /** UI PREDICATE DEFAULT COMPONENT OVERRIDES */
    import PredicateAdd from "./vue_PredicateAdd.js";
    import PredicateRemove from "./vue_PredicateRemove.js";
    import PredicateTargets from "./vue_PredicateTargets.js";
    import PredicateOperators from "./vue_PredicateOperators.js";
    import PredicateLogicalTypes from "./vue_PredicateLogicalTypes.js";

    /** END UI PREDICATE DEFAULT COMPONENT OVERRIDES */

    import DefaultArgument from "./vue_DefaultArgument.js";
    import TextArgument from "./vue_TextArgument.js";
    import NumberArgument from "./vue_NumberArgument.js";
    import DateArgument from "./vue_DateArgument.js";
    import NoArgument from "./vue_NoArgument.js";
    import BoolArgument from "./vue_BoolArgument.js";
    import CollectionArgument from "./vue_CollectionArgument.js";

    // validate the targets in case options have changed or fields deleted
    const getPredicates = function (predicates, thisvue) {
        return predicates.filter(predicate => {
            let found = thisvue.actionsColumns.targets.find(target => {
                if (predicate.target_id === target.target_id) {
                    return true;
                }
            });
            if (! found) {
                // try for partial matches - can happen if field options change (from or to a collection)
                found = thisvue.actionsColumns.targets.find(target => {
                    if (predicate.target_id.indexOf(target.target_id) > -1 || target.target_id.indexOf(predicate.target_id) > -1) {
                        return true;
                    }
                });

                if (found) {
                    if (predicate.target_id.indexOf("[]") > -1) {
                        predicate.target_id = predicate.target_id.replace("[]", "");
                    } else {
                        predicate.target_id = predicate.target_id + "[]";
                    }
                } else {
                    console.error("Tracker Field Rules: field " + predicate.target_id + " not found in predicates");
                }
            }
            return found;
        });
    };

    export default {
        name: "tracker-rules",
        components: {},
        data()
        {
            return {
                renderElseComponent: true,
                conditionsoutput: {},
                actionoutput: {},
                elseoutput: {},
                conditionsData: null,
                conditionsColumns: {
                    targets: null,
                    // besides array list names, everything else follows convention
                    // https://github.com/FGRibreau/sql-convention
                    operators: null,
                    types: null,
                    logicalTypes: [
                        {
                            logicalType_id: "any",
                            label: "Any",
                        },
                        {
                            logicalType_id: "all",
                            label: "All",
                        },
                        {
                            logicalType_id: "none",
                            label: "None",
                        },
                    ],
                    argumentTypes: [
                        {
                            argumentType_id: "DateTime",
                            component: DateArgument,
                        },
                        {
                            argumentType_id: "Text",
                            component: TextArgument,
                        },
                        {
                            argumentType_id: "Number",
                            component: NumberArgument,
                        },
                        {
                            argumentType_id: "Boolean",
                            component: BoolArgument,
                        },
                        {
                            argumentType_id: "Nothing",
                            component: NoArgument,
                        },
                        {
                            argumentType_id: "Collection",
                            component: CollectionArgument,
                        },
                    ],
                },
                actionsData: null,
                actionsColumns: {
                    targets: null,
                    operators: null,
                    types: null,
                    // TODO logicalTypes should be removed for actions
                    logicalTypes: [
                        {
                            logicalType_id: "any",
                            label: "Any",
                        },
                        {
                            logicalType_id: "all",
                            label: "All",
                        },
                        {
                            logicalType_id: "none",
                            label: "None",
                        },
                    ],
                    argumentTypes: [
                        {
                            argumentType_id: "Nothing",
                            component: NoArgument,
                        },
                    ],
                },
                elseData: null,
                // UI Predicate overrides
                ui:  {
                    ['TARGETS']: PredicateTargets,
                    ['LOGICAL_TYPES']: PredicateLogicalTypes,
                    ['OPERATORS']: PredicateOperators,
                    ['PREDICATE_ADD']: PredicateAdd,
                    ['PREDICATE_REMOVE']: PredicateRemove,
                    // If UIPredicate can't find a component related to your argumentType_id
                    // This component will be used as a fallback.
                    // By default it just an <input type="text">
                    ['ARGUMENT_DEFAULT']: DefaultArgument
                }
            };
        },
        methods: {

            onChangeConditions(diff)
            {
                this.conditionsoutput = diff;
            },
            onChangeActions(diff)
            {
                this.actionoutput = diff;
            },
            onChangeElse(diff)
            {
                this.elseoutput = diff;
            },
            onResetClicked(event)
            {
                this.conditionsoutput = "";
                this.actionoutput = "";
                this.elseoutput = "";

                $(this.$el).find(".card-body:not(.tips)").empty();

                event.preventDefault();
                return false;
            },
            onInvertActionsClicked(event)
            {
                const actions = this.actionoutput.toJSON();
                const thisvue = this;
                // clone the actions and set them to the opposites
                let oppositeActions = { ...actions };

                oppositeActions.predicates.forEach(function (pred) {
                    switch (pred.operator_id) {
                        case "Show":
                            pred.operator_id = "Hide";
                            break;
                        case "Hide":
                            pred.operator_id = "Show";
                            break;
                        case "Editable":
                            pred.operator_id = "NotEditable";
                            break;
                        case "NotEditable":
                            pred.operator_id = "Editable";
                            break;
                        case "Required":
                            pred.operator_id = "NotRequired";
                            break;
                        case "NotRequired":
                            pred.operator_id = "Required";
                            break;
                    }
                });

                // update the else actions
               const both = thisvue.elseData.predicates.concat(
                        getPredicates(oppositeActions.predicates, thisvue)
                );

                thisvue.elseData.predicates = both.filter(function (item, pos) {
                    const found = both.find((pred, i) => (
                            pred.argument === item.argument &&
                            pred.operator_id === item.operator_id &&
                            pred.target_id === item.target_id
                    ));
                    return both.indexOf(found) === pos;
                });

                // trick to make the component re-mount from https://stackoverflow.com/a/68175735/2459703
                this.renderElseComponent = false;
                this.$nextTick(() => {
                    // Add the component back in
                    this.renderElseComponent = true;
                });

                event.preventDefault();
                return false;
            }
        },
        beforeMount: function () {

            let fields = this.$parent.targetFields,
                field = {},
                thisvue = this,
                conditionsTargets = [],
                actionsTargets = [{
                    target_id: "NoTarget",
                    label: "",
                    type_id: "Nothing",

                }];

            thisvue.conditionsColumns.operators = thisvue.$parent.definitiion.operators;
            thisvue.conditionsColumns.types     = thisvue.$parent.definitiion.types;
            thisvue.actionsColumns.operators    = thisvue.$parent.definitiion.actions;
            thisvue.actionsColumns.types        = thisvue.$parent.definitiion.types;

            if (fields !== undefined) {

                fields.forEach(function (value) {
                    conditionsTargets.push({
                        target_id: value.ins_id,
                        label: value.name,
                        type_id: value.argumentType,
                    });
                    actionsTargets.push({
                        target_id: value.ins_id,
                        label: value.name,
                        type_id: "Field",
                    });

                    if (value.fieldId === thisvue.$parent.fieldId ||
                        value.argumentType === "Collection" && value.fieldId === (thisvue.$parent.fieldId + "[]")) {
                        field = value;
                    }
                });

                thisvue.conditionsColumns.targets = conditionsTargets;
                thisvue.actionsColumns.targets    = actionsTargets;
            }


            let defaultCondition = function () {
                let operatorId = "";

                if (field.argumentType === "Text") {
                    operatorId = "TextContains";
                } else if (field.argumentType === "Number") {
                    operatorId = "NumberEquals";
                } else if (field.argumentType === "Boolean") {
                    operatorId = "BooleanTrueFalse";
                } else if (field.argumentType === "DateTime") {
                    operatorId = "DateTimeOn";
                } else if (field.argumentType === "Collection") {
                    operatorId = "CollectionContains";
                }

                return {
                    logicalType_id: "any",
                    predicates: [{
                        target_id: "ins_" + field.fieldId + (field.argumentType === "Collection" ? "[]" : ""),
                        operator_id: operatorId,
                        argument: "",
                    }]
                };
            };

            // set conditions field to this one if nothing else set
            if (! thisvue.$parent.rules.conditions) {
                thisvue.conditionsData = defaultCondition();
            } else {
                thisvue.conditionsData = thisvue.$parent.rules.conditions;
                thisvue.conditionsData.predicates = getPredicates(thisvue.$parent.rules.conditions.predicates, thisvue);

                if (thisvue.conditionsData.predicates.length === 0) {
                    // we need at least one condition
                    thisvue.conditionsData = defaultCondition();
                }
            }

            if (thisvue.$parent.rules.actions) {
                thisvue.actionsData = thisvue.$parent.rules.actions;
                thisvue.actionsData.predicates = getPredicates(thisvue.$parent.rules.actions.predicates, thisvue);
            }
            if (thisvue.$parent.rules.else) {
                thisvue.elseData = thisvue.$parent.rules.else;
                thisvue.elseData.predicates = getPredicates(thisvue.$parent.rules.else.predicates, thisvue);
            }
        }
    };
</script>
