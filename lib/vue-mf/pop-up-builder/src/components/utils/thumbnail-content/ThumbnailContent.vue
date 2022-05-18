<script>
export default {
    name: 'ThumbnailContent'
}
</script>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'

const props = defineProps({
    title: String
})

const outerRef = ref(null)
const innerRef = ref(null)
const outerStyles = ref({})

onMounted(() => {
    nextTick(() => {
        let rect = innerRef.value.getBoundingClientRect()
        outerStyles.value.width = `${rect.width}px`
        outerStyles.value.height = `${rect.height}px`
    })
})

const getInnerStyles = computed(() => {
    return {
        width: `1200px`,
        // height: `700px`,
        transform: `scale(0.2375)`
    }
})
</script>

<template>
    <div>
        <div ref="outerRef" class="thumbnail-content--container" :style="outerStyles">
            <div ref="innerRef" class="thumbnail-content--container-inner" :style="getInnerStyles">
                <slot/>
            </div>
        </div>
        <div v-if="title" class="thumbnail-content--title">{{ title }}</div>
    </div>
</template>

<style lang="scss" scoped>
    .thumbnail-content--container {
        margin-left: auto;
        margin-right: auto;
        overflow: hidden;
        border: 1px solid #eee;
    }
    .thumbnail-content--container-inner {
        position: relative;
        top: 0;
        left: 0;
        z-index: 2;
        transform-origin: 0% 0%;
        overflow: hidden;
        user-select: none;
        cursor: pointer;
    }
    .thumbnail-content--title {
        font-size: 0.875rem;
        margin-top: 3px;
        text-align: center;
    }
</style>
