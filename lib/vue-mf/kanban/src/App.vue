<script setup>
// This starter template is using Vue 3 <script setup> SFCs
// Check out https://v3.vuejs.org/api/sfc-script-setup.html#sfc-script-setup
import HelloWorld from './components/HelloWorld.vue'
</script>

<template>
    <div>
        <img alt="Vue logo" src="./logo.png" />
        <h1 :class="[$style.greeting]">Hello Tiki</h1>
        <HelloWorld msg="Kanban Vue 3 + Vite" />
    </div>
</template>

<style module>
    .greeting {
        font-family: Arial, Helvetica, sans-serif;
        font-weight: bold;
    }
</style>
