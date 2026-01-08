<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
    uploads: Array,
});

const form = useForm({
    file: null,
    overwrite: false,
    provider_cnpj: '',
    provider_razao_social: '',
    xml_type: 'servico',
    excel_output_type: 'saida',
    provider_endereco: '',
    provider_bairro: '',
    provider_cep: '',
    provider_municipio: '',
    provider_uf: '',
    provider_fone: '',
    starting_number: 1,
    acumulador: '1', // Default
});

const showDuplicateModal = ref(false);
const duplicateInfo = ref(null);
const pendingFile = ref(null); // Store file for overwrite
const loadingCnpj = ref(false); // Loading state for CNPJ lookup

const lookupCnpj = async () => {
    if (!form.provider_cnpj) {
        alert('Por favor, informe o CNPJ');
        return;
    }

    loadingCnpj.value = true;
    try {
        const cnpj = form.provider_cnpj.replace(/\D/g, '');
        const response = await axios.get(`/api/cnpj/${cnpj}`);
        
        // Fill form with data from API
        form.provider_razao_social = response.data.razao_social;
        
        // Fill address fields
        if (response.data.endereco) {
            form.provider_endereco = response.data.endereco.logradouro + (response.data.endereco.numero ? ', ' + response.data.endereco.numero : '');
            form.provider_bairro = response.data.endereco.bairro || '';
            form.provider_cep = response.data.endereco.cep || '';
            form.provider_municipio = response.data.endereco.municipio || '';
            form.provider_uf = response.data.endereco.uf || '';
        }
        
        form.provider_fone = response.data.telefone || '';
        
        alert('Dados preenchidos com sucesso!');
    } catch (error) {
        const errorMsg = error.response?.data?.error || 'Erro ao buscar CNPJ';
        alert(errorMsg);
    } finally {
        loadingCnpj.value = false;
    }
};

const submit = () => {
    form.post(route('uploads.store'), {
        onSuccess: (page) => {
            if (page.props.flash?.duplicate_file) {
                // Store file before reset
                pendingFile.value = form.file;
                duplicateInfo.value = page.props.flash.duplicate_file;
                showDuplicateModal.value = true;
            } else {
                // Success - reset everything
                form.reset();
                pendingFile.value = null;
                
                if (page.props.flash?.download_url) {
                    window.location.href = page.props.flash.download_url;
                }
            }
        },
    });
};

const confirmOverwrite = () => {
    // Restore file and set overwrite flag
    form.file = pendingFile.value;
    form.overwrite = true;
    showDuplicateModal.value = false;
    submit();
};

const cancelOverwrite = () => {
    showDuplicateModal.value = false;
    duplicateInfo.value = null;
    pendingFile.value = null;
    form.reset('file');
    form.overwrite = false;
};

const deleteUpload = (id) => {
    if (confirm('Tem certeza que deseja apagar este arquivo?')) {
        router.delete(route('uploads.destroy', id), {
            preserveScroll: true,
            onSuccess: () => alert('Arquivo removido com sucesso!'),
            onError: (errors) => alert('Erro ao remover: ' + JSON.stringify(errors)),
        });
    }
};

// Polling logic for real-time status updates
let pollingInterval = null;

const lastUpdated = ref(new Date().toLocaleTimeString());

const startPolling = () => {
    pollingInterval = setInterval(() => {
        router.reload({ 
            preserveScroll: true,
            onSuccess: () => {
                lastUpdated.value = new Date().toLocaleTimeString();
            }
        });
    }, 3000); // Check every 3 seconds unconditionally
};

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

onMounted(() => {
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <Head title="Uploads" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Meus Arquivos</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-medium mb-4">Novo Upload</h3>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">CNPJ do Prestador *</label>
                                    <div class="flex gap-2">
                                        <input 
                                            type="text" 
                                            v-model="form.provider_cnpj" 
                                            placeholder="00.000.000/0000-00"
                                            required
                                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600" 
                                        />
                                        <button
                                            type="button"
                                            @click="lookupCnpj"
                                            :disabled="loadingCnpj || !form.provider_cnpj"
                                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap text-sm"
                                        >
                                            {{ loadingCnpj ? 'Buscando...' : 'Buscar' }}
                                        </button>
                                    </div>
                                    <div v-if="form.errors.provider_cnpj" class="text-red-500 text-xs mt-1">{{ form.errors.provider_cnpj }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Razão Social *</label>
                                    <input 
                                        type="text" 
                                        v-model="form.provider_razao_social" 
                                        placeholder="Nome da Empresa"
                                        required
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600" 
                                    />
                                    <div v-if="form.errors.provider_razao_social" class="text-red-500 text-xs mt-1">{{ form.errors.provider_razao_social }}</div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Tipo de Arquivo *</label>
                                    <select 
                                        v-model="form.xml_type" 
                                        required
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <option value="servico">Serviço (NFS-e)</option>
                                        <option value="dominio_txt">Saídas (Domínio - Txt)</option>
                                    </select>
                                    <div v-if="form.errors.xml_type" class="text-red-500 text-xs mt-1">{{ form.errors.xml_type }}</div>
                                </div>
                                
                                <!-- Acumulador Input (for Domínio TXT) -->
                                <div v-if="form.xml_type === 'dominio_txt'">
                                    <label class="block text-sm font-medium mb-2">Cód. Acumulador (Domínio) *</label>
                                    <input 
                                        v-model="form.acumulador"
                                        type="text"
                                        placeholder="Ex: 1"
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                    />
                                    <p class="text-xs text-gray-500 mt-1">Informe o código cadastrado no seu sistema.</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Número Inicial</label>
                                    <input 
                                        type="number" 
                                        v-model.number="form.starting_number" 
                                        min="1"
                                        placeholder="1"
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600" 
                                    />
                                    <div v-if="form.errors.starting_number" class="text-red-500 text-xs mt-1">{{ form.errors.starting_number }}</div>
                                    <p class="text-xs text-gray-500 mt-1">Números serão gerados em sequência</p>
                                </div>
                            </div>
                            
                            <!-- Provider Address Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Endereço</label>
                                    <input 
                                        type="text" 
                                        v-model="form.provider_endereco" 
                                        placeholder="Rua, Avenida, etc."
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600" 
                                    />
                                    <div v-if="form.errors.provider_endereco" class="text-red-500 text-xs mt-1">{{ form.errors.provider_endereco }}</div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Bairro/Distrito</label>
                                    <input 
                                        type="text" 
                                        v-model="form.provider_bairro" 
                                        placeholder="Bairro"
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600" 
                                    />
                                    <div v-if="form.errors.provider_bairro" class="text-red-500 text-xs mt-1">{{ form.errors.provider_bairro }}</div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">CEP</label>
                                    <input 
                                        type="text" 
                                        v-model="form.provider_cep" 
                                        placeholder="00000-000"
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600" 
                                    />
                                    <div v-if="form.errors.provider_cep" class="text-red-500 text-xs mt-1">{{ form.errors.provider_cep }}</div>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-2">Município</label>
                                    <input 
                                        type="text" 
                                        v-model="form.provider_municipio" 
                                        placeholder="Cidade"
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600" 
                                    />
                                    <div v-if="form.errors.provider_municipio" class="text-red-500 text-xs mt-1">{{ form.errors.provider_municipio }}</div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">UF</label>
                                    <select 
                                        v-model="form.provider_uf" 
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <option value="">Selecione</option>
                                        <option value="AC">AC</option>
                                        <option value="AL">AL</option>
                                        <option value="AP">AP</option>
                                        <option value="AM">AM</option>
                                        <option value="BA">BA</option>
                                        <option value="CE">CE</option>
                                        <option value="DF">DF</option>
                                        <option value="ES">ES</option>
                                        <option value="GO">GO</option>
                                        <option value="MA">MA</option>
                                        <option value="MT">MT</option>
                                        <option value="MS">MS</option>
                                        <option value="MG">MG</option>
                                        <option value="PA">PA</option>
                                        <option value="PB">PB</option>
                                        <option value="PR">PR</option>
                                        <option value="PE">PE</option>
                                        <option value="PI">PI</option>
                                        <option value="RJ">RJ</option>
                                        <option value="RN">RN</option>
                                        <option value="RS">RS</option>
                                        <option value="RO">RO</option>
                                        <option value="RR">RR</option>
                                        <option value="SC">SC</option>
                                        <option value="SP">SP</option>
                                        <option value="SE">SE</option>
                                        <option value="TO">TO</option>
                                    </select>
                                    <div v-if="form.errors.provider_uf" class="text-red-500 text-xs mt-1">{{ form.errors.provider_uf }}</div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Fone/Fax</label>
                                    <input 
                                        type="text" 
                                        v-model="form.provider_fone" 
                                        placeholder="(00) 0000-0000"
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:text-gray-100 focus:outline-none dark:bg-gray-700 dark:border-gray-600" 
                                    />
                                    <div v-if="form.errors.provider_fone" class="text-red-500 text-xs mt-1">{{ form.errors.provider_fone }}</div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Arquivo (Excel ou PDF) *</label>
                                <input 
                                    type="file" 
                                    @input="form.file = $event.target.files[0]" 
                                    required
                                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" 
                                    accept=".xlsx,.xls,.csv,.pdf" 
                                />
                                <div v-if="form.errors.file" class="text-red-500 text-xs mt-1">{{ form.errors.file }}</div>
                            </div>
                            <button 
                                type="submit" 
                                :disabled="form.processing" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Processando...' : 'Gerar Arquivo' }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">Histórico</h3>
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <span class="relative flex h-2 w-2 mr-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                </span>
                                Última atualização: {{ lastUpdated }}
                            </div>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Arquivo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="upload in uploads" :key="upload.id">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ upload.original_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ new Date(upload.created_at).toLocaleString() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800" v-if="upload.status === 'completed'">Concluído</span>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800" v-else-if="upload.status === 'processing'">Processando</span>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800" v-else-if="upload.status === 'pending'">Pendente</span>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800" v-else>Falha</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="upload.latest_conversion_job?.status === 'completed'">
                                            <a :href="route('conversions.download', upload.latest_conversion_job.id)" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none transition ease-in-out duration-150">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                                Download XML
                                            </a>
                                        </div>
                                        <div v-else-if="upload.status === 'failed' || upload.latest_conversion_job?.status === 'failed'" class="text-red-500 text-xs flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Falha
                                        </div>
                                        <div v-else-if="upload.latest_conversion_job?.status === 'processing'" class="text-blue-500 text-xs">
                                            Processando...
                                        </div>
                                        <span v-else class="text-gray-400 text-xs">-</span>
                                        
                                        <button 
                                            @click="deleteUpload(upload.id)"
                                            class="ml-3 text-red-600 hover:text-red-900 text-sm font-medium focus:outline-none"
                                        >
                                            Limpar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="uploads.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum arquivo enviado ainda.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Duplicate File Modal -->
        <div v-if="showDuplicateModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Arquivo Duplicado
                        </h3>
                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <p>O arquivo <strong>{{ duplicateInfo?.name }}</strong> já foi enviado anteriormente em <strong>{{ duplicateInfo?.uploaded_at }}</strong>.</p>
                            <p class="mt-2">Deseja substituir o arquivo existente?</p>
                        </div>
                        <div class="mt-4 flex gap-3">
                            <button @click="confirmOverwrite" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none">
                                Substituir
                            </button>
                            <button @click="cancelOverwrite" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
