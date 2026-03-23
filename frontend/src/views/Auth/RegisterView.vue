<template>
    <DefaultLayout>
        <section class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-lg items-center px-4 py-10">
            <div
                class="w-full rounded-2xl border border-slate-800 bg-slate-950/80 backdrop-blur-xl p-6 shadow-[0_18px_45px_rgba(15,23,42,0.8)] animate-fade-in-up">

                <div class="mb-6 space-y-2 text-center">
                    <h1 class="text-xl font-semibold text-slate-50">Criar conta</h1>
                    <p class="text-xs text-slate-400">
                        Recebes automaticamente <span class="text-amber-300 font-medium">10 coins</span> de boas-vindas.
                    </p>
                </div>

                <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submitRegister">

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-200">Nome e Apelido</label>
                        <Input v-model="name" type="text" placeholder="O teu nome e apelido"
                            class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                        <p v-if="errors.name" class="text-xs text-rose-400">{{ errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-200">Nome de Utilizador</label>
                        <Input v-model="username" type="text" placeholder="Nome visível nos jogos"
                            class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                        <p v-if="errors.username" class="text-xs text-rose-400">{{ errors.username }}</p>
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-medium text-slate-200">Email</label>
                        <Input v-model="email" type="email" placeholder="email@exemplo.com" autocomplete="email"
                            class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500" />
                        <p v-if="errors.email" class="text-xs text-rose-400">{{ errors.email }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-200">Password</label>
                        <div class="relative">
                            <Input v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••"
                                autocomplete="new-password"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500 pr-10" />
                            <button type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                                @click="showPassword = !showPassword">
                                <component :is="showPassword ? EyeOff : Eye" class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="errors.password" class="text-xs text-rose-400">{{ errors.password }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-200">Confirmar password</label>
                        <div class="relative">
                            <Input v-model="passwordConfirm" :type="showPasswordConfirm ? 'text' : 'password'"
                                placeholder="••••••"
                                autocomplete="new-password"
                                class="bg-slate-950 border-slate-700 text-sm placeholder:text-slate-500 pr-10" />
                            <button type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                                @click="showPasswordConfirm = !showPasswordConfirm">
                                <component :is="showPasswordConfirm ? EyeOff : Eye" class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="errors.passwordConfirm" class="text-xs text-rose-400">{{ errors.passwordConfirm }}</p>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-medium text-slate-200">Imagem (opcional)</label>

                        <Input type="file" accept="image/*" @change="handleFileChange" class="bg-slate-950 border-slate-700 text-sm
                                file:bg-slate-900 file:border-0 file:text-xs
                                file:text-slate-200 file:p-1 file:cursor-pointer" />

                        <Avatar class="mt-2 w-24 h-24 border border-slate-700 rounded-full overflow-hidden">
                            <AvatarImage :src="previewUrl || placeholder" class="object-cover" />
                            <AvatarFallback
                                class="bg-slate-800 text-slate-300 text-xs flex items-center justify-center">
                                Pré-visualização
                            </AvatarFallback>
                        </Avatar>
                    </div>

                    <div class="md:col-span-2">
                        <Button type="submit" class="btn btn-primary w-full text-sm py-2.5">
                            {{ loading ? "A criar..." : "Criar conta" }}
                        </Button>
                    </div>
                </form>

                <div class="mt-6 text-center text-xs text-slate-300">
                    Já tens conta?
                    <RouterLink to="/login"
                        class="font-medium text-brand-400 hover:text-brand-300 underline underline-offset-2">
                        Iniciar Sessão
                    </RouterLink>
                </div>
            </div>
        </section>

        <!-- Termos e Condições Modal -->
        <Dialog :open="showTermsModal" @update:open="(val) => { if (!val) handleDecline() }">
            <DialogContent class="sm:max-w-md bg-slate-950 border-slate-800">
                <DialogHeader>
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/10">
                        <ShieldCheck class="h-6 w-6 text-emerald-500" />
                    </div>
                    <DialogTitle class="text-center text-xl text-slate-100">Termos de Responsabilidade</DialogTitle>
                    <DialogDescription class="text-center text-slate-400">
                        Por favor confirma que cumpres os requisitos para criar conta.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="space-y-4 py-4">
                    <div class="rounded-lg border border-amber-500/20 bg-amber-500/10 p-4">
                        <div class="flex items-start gap-3">
                            <AlertTriangle class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                            <div class="text-sm text-amber-200">
                                <p class="font-medium mb-1">Jogo Responsável e Restrição de Idade</p>
                                <p class="opacity-90 leading-relaxed">
                                    Este site envolve mecânicas de moedas virtuais. Ao criar conta, declaras ter <span class="font-bold">mais de 18 anos</span> e residir numa jurisdição onde este tipo de plataforma é permitido.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-slate-500 leading-relaxed px-2 text-center">
                        Ao prosseguir, aceitas os nossos termos de serviço e política de privacidade. Joga com moderação.
                    </div>
                </div>

                <DialogFooter class="flex flex-col-reverse sm:flex-row gap-2">
                    <Button variant="ghost" @click="handleDecline" class="text-slate-400 hover:text-slate-200">
                        Voltar
                    </Button>
                    <Button @click="handleAccept" class="bg-emerald-600 hover:bg-emerald-500 text-white w-full sm:w-auto">
                        Aceitar e Prosseguir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </DefaultLayout>
</template>

<script setup>
import { ref } from 'vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { RouterLink, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar"
import { useAuthStore } from "@/stores/auth";
import { toast } from "vue-sonner";
import { Eye, EyeOff, ShieldCheck, AlertTriangle } from "lucide-vue-next";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

const auth = useAuthStore();
const router = useRouter();

const name = ref("");
const username = ref("");
const email = ref("");
const password = ref("");
const passwordConfirm = ref("");
const avatarFile = ref(null);
const showPassword = ref(false);
const showPasswordConfirm = ref(false);
const showTermsModal = ref(true);

const loading = ref(false);

const previewUrl = ref("")
const apiBase = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`)
    .replace(/\/+$/, '')
    .replace(/\/api$/, '')
const placeholder = `${apiBase || window.location.origin}/api/player/avatar/photos_avatars/anonymous.png`

const errors = ref({
    name: "",
    username: "",
    email: "",
    password: "",
    passwordConfirm: "",
});

function handleFileChange(e) {
    const file = e.target.files?.[0];

    avatarFile.value = file || null;

    previewUrl.value = file ? URL.createObjectURL(file) : "";
}

async function submitRegister() {
    errors.value = { name: "", username: "", email: "", password: "", passwordConfirm: "" };
    
    // Basic formatting validation before modal
    if (!name.value) errors.value.name = "O nome é obrigatório.";
    if (!username.value) errors.value.username = "O username é obrigatório.";
    if (!email.value) errors.value.email = "O email é obrigatório.";
    if (!password.value) errors.value.password = "A password é obrigatória.";
    if (password.value !== passwordConfirm.value) errors.value.passwordConfirm = "As passwords não coincidem.";

    if (Object.values(errors.value).some(e => e)) {
        toast.error("Por favor corrige os erros no formulário.");
        return;
    }

    const formData = new FormData();
    formData.append("name", name.value);
    formData.append("nickname", username.value);
    formData.append("email", email.value);
    formData.append("password", password.value);

    if (avatarFile.value) {
        formData.append("photo", avatarFile.value);
    }

    try {
        loading.value = true;
        const result = await auth.register(formData);

        if (result && result.success === false) {
            const firstApiError = Object.values(result?.errors || {}).flat()?.[0];
            toast.error(firstApiError || result?.message || "Erro ao criar conta.");
            return
        }

        const message = result?.message || "Conta criada. Verifica o teu email para validares a tua conta.";
        toast.success(message, { id: "register-success" });
        router.push("/login");
    } catch (err) {
        console.error(err);

        const apiErrors = err?.response?.data?.errors || {};
        Object.entries(apiErrors).forEach(([field, msgs]) => {
            if (field === 'nickname' && errors.value.username !== undefined) {
                errors.value.username = Array.isArray(msgs) ? msgs[0] : String(msgs)
                return
            }
            if (errors.value[field] !== undefined) {
                errors.value[field] = Array.isArray(msgs) ? msgs[0] : String(msgs);
            }
        });

        let message =
            err?.response?.data?.message ||
            err?.message ||
            "Erro ao criar conta.";

        if (!err?.response && err?.request) {
            message = "Erro ao comunicar com o servidor. Por favor, tenta mais tarde.";
        }

        toast.error(message, { id: "register-error" });
    } finally {
        loading.value = false;
    }
}

function handleAccept() {
    showTermsModal.value = false;
}

function handleDecline() {
    router.push('/');
}
</script>
