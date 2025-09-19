<template>
  <transition name="fade">
    <div v-if="ui.isLoading" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-white border-t-transparent"></div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { useUiStore } from '@/stores/ui'
const ui = useUiStore()
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>
