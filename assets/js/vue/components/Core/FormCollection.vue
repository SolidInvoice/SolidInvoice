<template>
    <div>
        AA
        <slot></slot>
        BB
    </div>
</template>

<script lang="ts" setup>
    defineProps({
        prototype: {
            type: String,
            required: true,
        },
        name: {
            type: String,
            required: true,
        },
        count: {
            type: Number,
            required: false,
            default: 0
        },
    });
</script>
