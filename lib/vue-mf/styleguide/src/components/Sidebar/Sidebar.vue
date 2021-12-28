<template>
    <div class="grid-container">
        <div class="sidebar" :class="{ 'sidebar-small': hideSidebar }">
            <button class="btn btn-light btn-sm mt-2 float-right" @click="handleToggleSidebar">
                <i v-if="!hideSidebar" class="fas fa-chevron-left"></i>
                <i v-if="hideSidebar" class="fas fa-chevron-right"></i>
            </button>
            <div class="sidebar-content">
                <div v-if="hideSidebar" class="sidebar-content-small" @click="handleToggleSidebar"></div>
                <div v-if="!hideSidebar" class="sidebar-content-big">
                    <slot name="sidebar-content" />
                </div>
            </div>
        </div>

        <div class="main-content" :class="{ 'main-content-large': hideSidebar }">
            <slot name="default" />
        </div>
    </div>
</template>

<script>
export default {
    name: 'Sidebar',
    data: function () {
        return {
            hideSidebar: true
        }
    },
    methods: {
        handleToggleSidebar: function (event) {
            this.hideSidebar = !this.hideSidebar
        }
    },
}
</script>

<style lang="scss" scoped>
.grid-container {
    margin: 0 auto;
    display: flex;
    align-items: stretch;
    transition: 1s ease;
}

.sidebar {
    min-width: 0;
    flex-shrink: 0;
    width: 16rem;
    background-color: white;
    transition: 0.35s ease;
    position: relative;
}

.sidebar-content {
    height: 100%;
    // border-right: 1px solid #e5e4e4;
    overflow: hidden;
}

.sidebar-small, .sidebar-small .sidebar-content {
    width: 29px;
}
.sidebar-content-small {
    cursor: pointer;
    width: 29px;
    height: 100%;

    &:hover {
        background-color: #f8f9fa;
    }
}

.sidebar-content-big {
    height: 100%;
}

.main-content {
    min-width: 0;
    flex-grow: 1;
    // height: 100%;
    transition: 0.35s ease;
}

.main-content-large {
    flex-grow: 1;
}
</style>
