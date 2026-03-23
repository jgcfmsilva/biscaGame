<template>
  <div class="min-h-[70vh] flex flex-col items-center justify-center text-center space-y-6">
    <div class="relative">
      <div class="absolute -inset-8 bg-emerald-500/10 blur-3xl"></div>
      <div class="relative rounded-2xl border border-slate-800 bg-slate-950/80 px-8 py-6 shadow-[0_20px_80px_rgba(0,0,0,0.45)]">
        <p class="text-sm uppercase tracking-[0.2em] text-emerald-300">Recurso</p>
        <h1 class="text-5xl font-extrabold text-white mt-2">404</h1>
        <p class="text-lg text-slate-300 mt-2">Recurso não encontrado.</p>
        <p class="text-sm text-slate-500 mt-1">O item pode ter sido removido, renomeado ou nunca existiu.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
          <button
            class="px-4 py-2 rounded-lg border border-slate-700 bg-slate-900 text-slate-200 hover:bg-slate-800 transition"
            @click="$router.back()"
          >
            Voltar atrás
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
