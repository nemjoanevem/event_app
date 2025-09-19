<script setup lang="ts">
import { ref } from 'vue';

const result = ref<string>('');
async function ping() {
  result.value = '...';
  try {
    const res = await fetch(`${import.meta.env.VITE_API_URL}/api/ping`);
    const data = await res.json();
    result.value = data.status ?? JSON.stringify(data);
  } catch (e:any) {
    result.value = 'error';
    console.error(e);
  }
}
</script>

<template>
  <button @click="ping">Ping API</button>
  <p v-if="result">Server says: {{ result }}</p>
</template>
