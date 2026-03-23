<template>
    <DefaultLayout>
        <section class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-md items-center px-4 py-10">
            <div
                class="w-full rounded-2xl border border-slate-800 bg-slate-950/80 backdrop-blur-xl p-6 shadow-[0_18px_45px_rgba(15,23,42,0.8)] animate-fade-in-up">
                <div class="mb-6 space-y-2 text-center">
                    <h1 class="text-xl font-semibold text-slate-50">Iniciar Sessão</h1>
                    <p class="text-xs text-slate-400">
                        Acede à tua conta para jogar multiplayer, ver moedas e histórico.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submitLogin">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-200">Email</label>
                        <Input v-model="email" type="email" placeholder="email@exemplo.com" autocomplete="email"
                            class=" bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-200">Password</label>
                        <div class="relative">
                            <Input v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••"
                                autocomplete="current-password"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500 pr-10" />
                            <button type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                                @click="showPassword = !showPassword">
                                <component :is="showPassword ? EyeOff : Eye" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <RouterLink to="/forgot-password" class="text-[11px] text-brand-400 hover:text-brand-300">
                            Esqueceste a password?
                        </RouterLink>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-300 select-none">
                        <input v-model="remember" type="checkbox"
                            class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-brand-400 focus:ring-1 focus:ring-brand-400" />
                        <span>Manter sessão iniciada</span>
                    </label>

                    <Button type="submit" class="btn btn-primary w-full mt-1 text-sm py-2.5">
                        {{ loading ? "A entrar..." : "Iniciar Sessão" }}
                    </Button>
                </form>

                <div class="mt-6 flex items-center justify-between text-xs text-slate-400">
                    <span class="h-px flex-1 bg-slate-800"></span>
                    <span class="mx-3 text-slate-500">Ainda não tens conta?</span>
                    <span class="h-px flex-1 bg-slate-800"></span>
                </div>

                <div class="mt-4 text-center text-xs text-slate-300">
                    <RouterLink to="/register"
                        class="font-medium text-brand-400 hover:text-brand-300 underline underline-offset-2">
                        Criar Conta
                    </RouterLink>
                </div>

                <p class="mt-4 text-[12px] text-slate-500 text-center">
                    Utilizadores anónimos podem jogar single-player sem iniciar sessão.
                </p>
            </div>
        </section>
    </DefaultLayout>
</template>

<script setup>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { RouterLink, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useAuthStore } from "@/stores/auth";
import { ref } from 'vue'
import { toast } from "vue-sonner";
import { Eye, EyeOff } from "lucide-vue-next";

const router = useRouter();
const auth = useAuthStore();

const email = ref("");
const password = ref("");
const loading = ref(false);
const showPassword = ref(false);
const remember = ref(false);

async function submitLogin() {
    try {
        loading.value = true;
        const result = await auth.login(email.value, password.value, remember.value);

        if (!result || result.success === false) {
            const errorMessage = result?.message || "Erro ao iniciar sessão. Verifica as tuas credenciais.";
            toast.error(errorMessage);
            return;
        }

        if (auth.isAdmin) {
            router.push({ name: "admin-dashboard" });
        } else {
            router.push("/play");
        }
    } catch (err) {
        const message =
            err?.response?.data?.message ||
            err?.message ||
            "Erro ao comunicar com o servidor. Por favor, tenta mais tarde.";
        toast.error(message, { id: "login-error" });
    } finally {
        loading.value = false;
    }
}
</script>
