<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Bem-vindo" />
    
    <div class="bg-gray-50 text-black/90 dark:bg-black dark:text-white/90 min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
        
        <!-- Header / Nav -->
        <header class="w-full max-w-7xl px-6 py-6 flex justify-between items-center absolute top-0">
            <div class="font-bold text-xl tracking-tight">
                <span class="text-[#FF2D20]">XML</span>Converter
            </div>
            
            <nav v-if="canLogin" class="flex gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-md px-4 py-2 bg-black text-white hover:bg-black/90 transition dark:bg-white dark:text-black dark:hover:bg-white/90"
                >
                    Dashboard
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="rounded-md px-4 py-2 text-black hover:text-black/70 transition dark:text-white dark:hover:text-white/80"
                    >
                        Entrar
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-md px-4 py-2 bg-[#FF2D20] text-white hover:bg-[#FF2D20]/90 transition shadow-sm"
                    >
                        Cadastrar
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Main Hero Content -->
        <main class="w-full max-w-4xl px-6 flex flex-col items-center text-center mt-20 sm:mt-0">
            
            <!-- Hero Badge -->
            <div class="mb-8 inline-flex items-center rounded-full border border-[#FF2D20]/20 bg-[#FF2D20]/10 px-3 py-1 text-sm font-medium text-[#FF2D20]">
                <span>Pronto para Domínio Sistemas & ABRASF</span>
            </div>

            <h1 class="text-4xl font-bold tracking-tight sm:text-6xl mb-6">
                Conversão de Planilhas <br class="hidden sm:block" />
                <span class="text-[#FF2D20]">Simples e Eficiente.</span>
            </h1>
            
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Transforme planilhas (Excel/PDF) em arquivos de importação contábil compatíveis com o padrão ABRASF e Domínio Sistemas em segundos.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row gap-4 w-full justify-center">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-flex items-center justify-center rounded-lg bg-[#FF2D20] px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-[#FF2D20]/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FF2D20] transition-all"
                >
                    Ir para o Painel
                    <svg class="ml-2 -mr-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </Link>
                <div v-else class="flex flex-col sm:flex-row gap-4">
                     <Link
                        :href="route('register')"
                        class="inline-flex items-center justify-center rounded-lg bg-[#FF2D20] px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-[#FF2D20]/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FF2D20] transition-all"
                    >
                        Criar Conta Grátis
                    </Link>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-base font-semibold text-gray-900 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900 dark:bg-zinc-900 dark:text-white dark:border-zinc-800 dark:hover:bg-zinc-800 transition-all"
                    >
                        Acessar Sistema
                    </Link>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="mt-24 grid grid-cols-1 gap-8 sm:grid-cols-3 text-left w-full">
                
                <!-- Feature 1 -->
                <div class="relative rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#FF2D20]/10">
                        <svg class="h-6 w-6 text-[#FF2D20]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Excel & PDF</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Suporte nativo para planilhas e arquivos PDF, mesmo sem cabeçalhos definidos (Statement).
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="relative rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#FF2D20]/10">
                        <svg class="h-6 w-6 text-[#FF2D20]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Mapeamento Inteligente</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Detecção automática de colunas (Data, Valor, Cliente, CNPJ) sem necessidade de configuração manual.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="relative rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#FF2D20]/10">
                        <svg class="h-6 w-6 text-[#FF2D20]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.43.816 1.035.816 1.73 0 .695-.32 1.3-.816 1.73" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Compatível com Domínio</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Exportação otimizada para "Saídas" e "Serviços", pronta para importação no Domínio Sistemas.
                    </p>
                </div>

            </div>
        </main>

        <footer class="mt-20 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
            &copy; {{ new Date().getFullYear() }} ABRASF Converter SaaS. Todos os direitos reservados.
        </footer>
    </div>
</template>
