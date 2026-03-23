import axios from "axios";
import { useAuthStore } from "@/stores/auth";

const OFFLINE_PLAYER_KEY = "offlinePlayerKey";

const getOfflinePlayerKey = () => {
  if (typeof window === "undefined") return null;
  try {
    const existing = window.localStorage.getItem(OFFLINE_PLAYER_KEY);
    if (existing) return existing;
    const generated =
      typeof crypto !== "undefined" && crypto.randomUUID
        ? crypto.randomUUID()
        : `guest-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    window.localStorage.setItem(OFFLINE_PLAYER_KEY, generated);
    return generated;
  } catch (err) {
    return null;
  }
};

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  timeout: 8000,
  withCredentials: true,
});

api.defaults.headers["Content-Type"] = "application/json";

api.interceptors.request.use(
  (config) => {
    const auth = useAuthStore();

    if (auth?.token) {
      config.headers.Authorization = `Bearer ${auth.token}`;
    }

    const offlineKey = getOfflinePlayerKey();
    if (offlineKey) {
      config.headers["X-Offline-Player"] = offlineKey;
    }

    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const auth = useAuthStore();

    if (error.response?.status === 401 && auth.isAuthenticated) {
      auth.logout();
    }

    return Promise.reject(error);
  }
);

export default api;
