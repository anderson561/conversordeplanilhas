<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Props from controller
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalUploads: 0,
            totalConversions: 0,
            successRate: 0,
            recentUploads: []
        })
    }
});

// Format date helper
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Format file size
const formatFileSize = (bytes) => {
    if (!bytes) return 'N/A';
    const mb = bytes / (1024 * 1024);
    return mb.toFixed(2) + ' MB';
};

// Get status badge color
const getStatusColor = (status) => {
    const colors = {
        'completed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'processing': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'failed': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
    };
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
};

// Get status label
const getStatusLabel = (status) => {
    const labels = {
        'completed': 'Concluído',
        'processing': 'Processando',
        'failed': 'Erro',
        'pending': 'Pendente'
    };
    return labels[status] || status;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-3">
                    <!-- Total Uploads -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Total de Uploads
                                    </p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                        {{ stats.totalUploads || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Conversions -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Conversões Concluídas
                                    </p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                        {{ stats.totalConversions || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success Rate -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Taxa de Sucesso
                                    </p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                        {{ stats.successRate || 0 }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Recent Uploads -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                            Uploads Recentes
                        </h3>
                        
                        <div v-if="stats.recentUploads && stats.recentUploads.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">
                                            Arquivo
                                        </th>
                                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">
                                            Tipo
                                        </th>
                                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">
                                            Data
                                        </th>
                                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">
                                            Ações
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    <tr v-for="upload in stats.recentUploads" :key="upload.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ upload.original_filename }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 text-xs font-semibold leading-5 text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                                {{ upload.xml_type === 'saida' ? 'NF-e' : 'NFS-e' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusColor(upload.conversion_status)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                                                {{ getStatusLabel(upload.conversion_status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                            {{ formatDate(upload.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                            <Link
                                                v-if="upload.conversion_status === 'completed'"
                                                :href="route('conversions.download', upload.id)"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                Download
                                            </Link>
                                            <span v-else class="text-gray-400 dark:text-gray-600">
                                                -
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="py-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-4 text-gray-500 dark:text-gray-400">
                                Nenhum upload encontrado. Comece enviando seu primeiro arquivo!
                            </p>
                            <Link
                                :href="route('uploads.index')"
                                class="inline-flex items-center px-4 py-2 mt-4 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Novo Upload
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
