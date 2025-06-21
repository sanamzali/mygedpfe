<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Codesandbox } from 'lucide-vue-next';


defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <div class="text-2xl">
        <Codesandbox class="w-14 h-14" />
    </div>
</template>
