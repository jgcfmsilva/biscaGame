import "@/assets/styles/style.css";
import "@/assets/styles/buttons.css"

import { createApp } from "vue";
import { createPinia } from "pinia";
import piniaPersist from 'pinia-plugin-persistedstate'

import App from "./App.vue";
import router from "./router";
import { useAuthStore } from "@/stores/auth";

const app = createApp(App);

const pinia = createPinia();
pinia.use(piniaPersist);
app.use(pinia);

const auth = useAuthStore(pinia);
auth.init();
if (auth.hasToken) {
  auth.fetchUser().catch(() => {
    // falha de sessão será tratada pelo interceptor (logout), ignora aqui
  });
}

app.use(router);

app.mount("#app");
