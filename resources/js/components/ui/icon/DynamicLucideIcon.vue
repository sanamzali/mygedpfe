<script setup>
import * as icons from 'lucide-vue-next'
import { computed, defineProps } from 'vue'

const props = defineProps({
    name: {
        type: String,
        required: true
    }
})

const iconComponent = computed(() => {
    return icons[props.name] || null
})
</script>

<template>
    <component v-if="iconComponent" :is="iconComponent" v-bind="$attrs" />
</template>
