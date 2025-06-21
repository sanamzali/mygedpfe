<script setup lang="ts">
import { ref, defineProps, defineEmits, watchEffect } from 'vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(props.modelValue)

watchEffect(() => {
    isOpen.value = props.modelValue
})

const close = () => {
    emit('update:modelValue', false)
}
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-md">
        <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-lg">
            <slot></slot>
        </div>
    </div>
</template>


<style scoped>
/* You can style the modal as per your requirement */
</style>
