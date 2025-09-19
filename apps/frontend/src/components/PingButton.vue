<script setup lang="ts">
import { ref } from 'vue';

const result = ref<string>('');
async function ping() {
  result.value = '...';
  try {
    const res = await fetch(`${import.meta.env.VITE_API_URL}/api/ping`);
    const ct = res.headers.get('content-type') || '';
    const body = ct.includes('application/json') ? await res.json() : await res.text();

    // állapot kijelzés
    result.value = (body && typeof body === 'object' ? body.status : body)
                   ?? JSON.stringify(body ?? '');
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
