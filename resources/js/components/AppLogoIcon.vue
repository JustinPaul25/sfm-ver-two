<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg width="256" height="256" viewBox="0 0 256 256" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Fish Body -->
        <path 
            d="M28 128c0 0 52-58 108-58s92 58 92 58-36 58-92 58S28 128 28 128z" 
            stroke="#0B3C51" 
            stroke-width="12" 
            stroke-linecap="round" 
            stroke-linejoin="round"
            fill="#28576F"
        />

        <!-- Fish Tail -->
        <path 
            d="M28 128l-20-22M28 128l-20 22" 
            stroke="#0B3C51" 
            stroke-width="10" 
            stroke-linecap="round"
        />

        <!-- Eye -->
        <circle cx="158" cy="128" r="8" fill="#FFFFFF"/>

        <!-- Metrics / Analytics Line -->
        <polyline 
            points="70,146 98,118 128,132 152,100 180,120" 
            stroke="#4F9AB5" 
            stroke-width="10" 
            stroke-linecap="round" 
            stroke-linejoin="round"
            fill="none"
        />
</svg>
</template>
