<template>
  <DefaultLayout>
    <section class="min-h-[60vh] flex items-center justify-center px-4 py-16">
      <div class="relative w-full max-w-xl overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/80 p-8 text-center shadow-[0_25px_80px_rgba(2,6,23,0.6)]">
        <div class="pointer-events-none absolute inset-0 opacity-60"
          style="background: radial-gradient(circle at 20% 20%, rgba(16,185,129,0.12), transparent 35%), radial-gradient(circle at 80% 0%, rgba(59,130,246,0.12), transparent 30%);">
        </div>
        <div class="relative space-y-4">
          <p class="text-xs uppercase tracking-[0.35em] text-emerald-300/80">Recurso</p>
          <h1 class="text-5xl font-black text-white">404</h1>
          <p class="text-lg text-slate-200">Não encontrámos o que procuras.</p>
          <p class="text-sm text-slate-500">O jogo ou match pode ter sido removido, não existe ou não tens acesso.</p>
          <div class="mt-6 flex flex-wrap justify-center gap-3">
            <button
              class="rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-slate-200 transition hover:bg-slate-800"
              @click="$router.back()"
            >
              Voltar atrás
            </button>
            <RouterLink
              to="/"
              class="rounded-lg border border-emerald-500/30 bg-emerald-500/15 px-4 py-2 text-emerald-100 transition hover:bg-emerald-500/25"
            >
              Ir para o início
            </RouterLink>
          </div>
        </div>
      </div>
    </section>
  </DefaultLayout>
</template>

<script setup>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
</script>
