<template>
  <div class="min-h-screen bg-slate-950 text-slate-50">
    <RouterView v-slot="{ Component, route }">
      <Transition name="page" mode="out-in">
        <component :is="Component" :key="route.fullPath" />
      </Transition>
    </RouterView>
    <Toaster position="top-right" theme="dark" richColors />
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'
import { Toaster } from "vue-sonner";
import 'vue-sonner/style.css'
import { onMounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useWsStore } from '@/stores/ws';

const authStore = useAuthStore();
const wsStore = useWsStore();

// Watch for authentication changes to connect/disconnect socket
watch(() => authStore.token, (newToken) => {
  if (newToken) {
    wsStore.connect(newToken);
  } else {
    // wsStore.disconnect() // If you implement a disconnect method
    // For now, reload usually handles this or ws.close in store
  }
}, { immediate: true });

onMounted(() => {
  if (authStore.token && !wsStore.connected) {
    wsStore.connect(authStore.token);
  }
});
</script>

<style scoped></style>
