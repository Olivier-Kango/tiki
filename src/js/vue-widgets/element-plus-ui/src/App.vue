<script setup>
import { onMounted, ref } from 'vue';
import Transfer from './components/Transfer.vue';
import Select from './components/Select.vue';
import Autocomplete from './components/Autocomplete.vue';

const components = {
    Transfer,
    Select,
    Autocomplete,
};

const props = defineProps({
    component: {
        type: String,
    },
    language: {
        type: String,
        default: 'en',
    },
});

const locale = ref(null);

const loadLocale = async (localeName) => {
    try {
        const basePath = window.location.pathname.split('/').slice(0, -1).join('/');
        const importedLocale = await import(`${basePath}/public/generated/js/vendor_dist/element-plus/dist/locale/${localeName}.min.mjs`);
        locale.value = importedLocale.default;
    } catch (error) {
        console.error('Error loading locale:', error);
    }
};

onMounted(() => {
    loadLocale(props.language);
});
</script>

<template>
    <el-config-provider :locale="locale">
        <component :is="components[component]" v-bind="{...$attrs}" data-testid="app-component" />
    </el-config-provider>
</template>
