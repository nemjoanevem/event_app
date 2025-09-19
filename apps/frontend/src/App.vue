<script setup lang="ts"></script>

<template>
  <NavBar v-if="!$route.meta.noNav" />
  <RouterView />
  <GlobalLoader />
</template>

<style scoped></style>
<script setup lang="ts">
import GlobalLoader from './components/GlobalLoader.vue'
import PingButton from '@/components/PingButton.vue';
import NavBar from '@/components/NavBar.vue'
import { useRoute } from 'vue-router';

const route = useRoute();
</script>
