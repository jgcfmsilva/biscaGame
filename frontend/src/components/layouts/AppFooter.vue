<template>
    <footer class="border-t border-slate-800 bg-slate-950">
        <div
            class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-6 text-xs text-slate-500 md:flex-row md:items-center md:justify-between lg:pr-24">
            <p>© {{ new Date().getFullYear() }} Bisca Game Platform</p>

            <div class="flex flex-col items-center md:items-end gap-0.5 text-center md:text-right">
                <span class="font-medium text-slate-400">
                    Projeto DAD 2025/2026
                </span>
                <span>
                    ESTG - Escola Superior de Tecnologia e Gestão
                </span>
                <div class="flex flex-wrap gap-2 justify-end text-slate-600">
                    <span>&copy;</span>
                    <button 
                        v-for="(dev, index) in developers" 
                        :key="dev.name"
                        @click="openDevModal(dev)"
                        class="hover:text-blue-400 transition-colors relative group"
                    >
                        {{ dev.name }}<span v-if="index < developers.length - 1" class="text-slate-700 mx-1">,</span>
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-500 transition-all group-hover:w-full"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Developer Info Modal -->
        <Transition name="fade">
            <div v-if="selectedDev" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
                <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden transform transition-all animate-bounce-in">
                    <!-- Header with generic avatar/initials or just colorful background -->
                    <div class="h-24 bg-gradient-to-r from-blue-600 to-indigo-600 relative">
                        <button @click="closeModal" class="absolute top-3 right-3 p-1.5 bg-black/20 hover:bg-black/40 text-white rounded-full transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                        <div class="absolute -bottom-8 left-6">
                            <div class="w-16 h-16 rounded-xl bg-slate-800 border-4 border-slate-900 flex items-center justify-center shadow-lg text-2xl font-bold text-white">
                                {{ getInitials(selectedDev.name) }}
                            </div>
                        </div>
                    </div>

                    <div class="pt-10 px-6 pb-6">
                        <h3 class="text-xl font-bold text-white mb-1">{{ selectedDev.name }}</h3>
                        <p class="text-xs text-blue-400 font-medium mb-6 uppercase tracking-wider">Developer</p>

                        <div class="space-y-4">
                            <!-- Course Code -->
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-800/50 border border-slate-800">
                                <div class="p-2 rounded-md bg-purple-500/10 text-purple-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-500 uppercase font-bold">Código de Curso</p>
                                    <p class="text-sm text-slate-200 font-mono">{{ selectedDev.courseCode }}</p>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-slate-800/50 border border-slate-800">
                                <div class="p-2 rounded-md bg-emerald-500/10 text-emerald-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-slate-500 uppercase font-bold">Email</p>
                                    <a :href="`mailto:${selectedDev.email}`" class="text-sm text-slate-200 hover:text-emerald-400 transition-colors truncate block">
                                        {{ selectedDev.email }}
                                    </a>
                                </div>
                            </div>

                            <!-- User Links -->
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-800/50 border border-slate-800">
                                <div class="p-2 rounded-md bg-slate-700/30 text-slate-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-slate-500 uppercase font-bold">GitHub</p>
                                    <a v-if="selectedDev.github !== '-nd-'" 
                                       :href="selectedDev.github" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-sm text-blue-400 hover:text-blue-300 hover:underline truncate block flex items-center gap-1">
                                        {{ extractGithubHandle(selectedDev.github) }}
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    </a>
                                    <span v-else class="text-sm text-slate-500 italic">Não disponível</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </footer>
</template>

<script setup>
import { ref } from 'vue'

const selectedDev = ref(null)

const developers = [
    {
        name: 'João Batista',
        github: 'https://github.com/joaobatistaa',
        email: '2242027@my.ipleiria.pt',
        courseCode: '9885'
    },
    {
        name: 'Jorge Silva',
        github: 'https://github.com/jgcfmsilva',
        email: '2221412@my.ipleiria.pt',
        courseCode: '9885'
    },
    {
        name: 'Nuno Lopes',
        github: 'https://github.com/Saito002',
        email: '2221853@my.ipleiria.pt',
        courseCode: '9885'
    },
    {
        name: 'Paulo Rodrigues',
        github: 'https://github.com/pauloavrd',
        email: '2223323@my.ipleiria.pt',
        courseCode: '9885'
    }
]

const openDevModal = (dev) => {
    selectedDev.value = dev
}

const closeModal = () => {
    selectedDev.value = null
}

const getInitials = (name) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
}

const extractGithubHandle = (url) => {
    return url.replace('https://github.com/', '@')
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.animate-bounce-in {
    animation: bounceIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes bounceIn {
  0% { transform: scale(0.9); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}
</style>
