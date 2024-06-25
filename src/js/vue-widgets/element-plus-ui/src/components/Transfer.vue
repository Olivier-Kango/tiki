<script setup>
import { ref, onMounted } from 'vue';
import { Menu } from "@element-plus/icons-vue";
import Sortable from "sortablejs";

const props = defineProps(['data', 'fieldName', 'filterable', 'defaultValue', 'sourceListTitle', 'targetListTitle', 'filterPlaceholder', 'ordering']);
const data = typeof props.data === 'string' ? JSON.parse(props.data) : props.data;
const defaultValue = typeof props.defaultValue === 'string' ? JSON.parse(props.defaultValue) : props.defaultValue;

const selected = ref(defaultValue ?? []);

const arrayData = Object.entries(data).map(([key, value]) => ({ key, label: value }));

const elTransferContainer = ref(null);

onMounted(() => {
    if(elTransferContainer.value && JSON.parse(props.ordering)) {
        const list = elTransferContainer.value.querySelectorAll(".el-transfer-panel__list")[1];
        new Sortable(list, {
            animation: 150,
            handle: '.' + DRAG_HANDLER_CLASS,
            onSort: () => {
                const items = list.querySelectorAll(".el-transfer-panel__item");
                const sorted = [];
                items.forEach((item) => {
                    const value = item.querySelector(".el-checkbox__label > span").dataset.key;
                    sorted.push(value);
                });
                selected.value = sorted;
            },
        });
    
    }
});
</script>

<script>
const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    HIDDEN_SELECT: `hidden-select-${uniqueId}`,
};
export const DRAG_HANDLER_CLASS = "handle-drag";
</script>

<template>
    <select multiple aria-hidden="true" :name="fieldName" style="display: none;" :data-testid="DATA_TEST_ID['HIDDEN_SELECT']">
        <option v-for="key in selected" :value="key" :key="key" selected></option>
    </select>
    <div ref="elTransferContainer">
        <el-transfer v-model="selected" :data="arrayData" :filterable="JSON.parse(filterable)" :titles="[sourceListTitle, targetListTitle]" :filter-placeholder="filterPlaceholder" :target-order="JSON.parse(ordering) ? 'push': 'original'">
            <template #default="{ option }">
                <el-button v-if="selected.includes(option.key) && JSON.parse(ordering)" :class="DRAG_HANDLER_CLASS" :icon="Menu" size="small" link /> <span :data-key="option.key">{{ option.label }}</span>
            </template>
        </el-transfer>
    </div>
</template>
