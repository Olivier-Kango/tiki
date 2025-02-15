<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
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
        <slot />
    </el-config-provider>
</template>