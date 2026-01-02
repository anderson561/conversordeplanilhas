<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    upload: Object,
    headers: {
        type: Array,
        default: () => []
    },
});

const abrasfFields = [
    { group: 'RPS', fields: ['Rps.Numero', 'Rps.Serie', 'Rps.Tipo', 'Rps.DataEmissao', 'Rps.Competencia'] },
    { group: 'Tomador', fields: ['Tomador.CpfCnpj', 'Tomador.RazaoSocial', 'Tomador.InscricaoMunicipal'] },
    { group: 'Tomador Endereço', fields: ['Tomador.Endereco.Logradouro', 'Tomador.Endereco.Numero', 'Tomador.Endereco.Complemento', 'Tomador.Endereco.Bairro', 'Tomador.Endereco.CodigoMunicipio', 'Tomador.Endereco.Uf', 'Tomador.Endereco.Cep'] },
    { group: 'Serviço', fields: ['Servico.ValorServicos', 'Servico.ValorDeducoes', 'Servico.ValorPis', 'Servico.ValorCofins', 'Servico.ValorInss', 'Servico.ValorIr', 'Servico.ValorCsll', 'Servico.IssRetido', 'Servico.ValorIss', 'Servico.ValorIssRetido', 'Servico.OutrasRetencoes', 'Servico.BaseCalculo', 'Servico.Aliquota', 'Servico.ValorLiquidoNfse', 'Servico.DescontoIncondicionado', 'Servico.DescontoCondicionado', 'Servico.ItemListaServico', 'Servico.CodigoCnae', 'Servico.CodigoTributacaoMunicipio', 'Servico.Discriminacao', 'Servico.CodigoMunicipio'] },
];

const mapping = ref({});
const saveTemplate = ref(false);
const templateName = ref('');
const templates = ref([]);
const selectedTemplate = ref('');

// Initialize mapping with empty values and fetch templates
onMounted(async () => {
    abrasfFields.forEach(group => {
        group.fields.forEach(field => {
            if (!mapping.value[field]) {
                mapping.value[field] = '';
            }
        });
    });

    try {
        const response = await axios.get(route('templates.index'));
        templates.value = response.data;
    } catch (error) {
        console.error('Error fetching templates:', error);
    }
});

const loadTemplate = () => {
    if (!selectedTemplate.value) return;
    
    const template = templates.value.find(t => t.id === selectedTemplate.value);
    if (template && template.mapping_rules) {
        // Merge template rules into mapping
        Object.keys(template.mapping_rules).forEach(key => {
            if (mapping.value.hasOwnProperty(key)) {
                mapping.value[key] = template.mapping_rules[key];
            }
        });
        // Also add any keys from template that might be missing in initial mapping (though unlikely with current setup)
        Object.keys(template.mapping_rules).forEach(key => {
            if (!mapping.value.hasOwnProperty(key)) {
                mapping.value[key] = template.mapping_rules[key];
            }
        });
    }
};

const form = useForm({
    mapping: {},
    save_template: false,
    template_name: '',
});

const submit = () => {
    console.log('Submit called!');
    
    form.mapping = mapping.value;
    form.save_template = saveTemplate.value;
    form.template_name = templateName.value;
    
    console.log('Form data:', form);
    
    form.post(route('conversions.store', props.upload.id), {
        onSuccess: (page) => {
            console.log('Success!');
            if (page.props.flash?.download_url) {
                console.log('Downloading:', page.props.flash.download_url);
                window.location.href = page.props.flash.download_url;
            }
        },
        onError: (errors) => {
            console.error('Errors:', errors);
        }
    });
};
</script>

<template>
    <Head title="Mapeamento" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Mapear Colunas: {{ upload?.original_name }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Success/Error Messages -->
                <!-- Success/Error Messages -->
                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Debug Info (Can be removed later) -->
                <div v-if="!headers || headers.length === 0" class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative">
                    <strong>Aviso:</strong> Nenhuma coluna detectada. O arquivo pode estar vazio ou ilegível.
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        
                        <!-- Template Loader -->
                        <div v-if="templates.length > 0" class="mb-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border dark:border-gray-600">
                            <h3 class="font-bold mb-3 text-lg">Carregar Modelo Salvo</h3>
                            <div class="flex gap-4 items-end">
                                <div class="flex-grow">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Selecione um modelo</label>
                                    <select v-model="selectedTemplate" class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                                        <option value="">-- Selecione --</option>
                                        <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</option>
                                    </select>
                                </div>
                                <button @click="loadTemplate" type="button" :disabled="!selectedTemplate" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Carregar Mapeamento
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submit">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="group in abrasfFields" :key="group.group" class="border p-4 rounded-lg dark:border-gray-700">
                                    <h3 class="font-bold mb-3 text-lg border-b pb-2 dark:border-gray-700">{{ group.group }}</h3>
                                    <div v-for="field in group.fields" :key="field" class="mb-3">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ field }}</label>
                                        <select v-model="mapping[field]" class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">-- Ignorar --</option>
                                            <option v-for="header in headers" :key="header" :value="header">{{ header }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 border-t pt-6 dark:border-gray-700">
                                <div class="flex items-center mb-4">
                                    <input type="checkbox" v-model="saveTemplate" id="save_template" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <label for="save_template" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Salvar como modelo</label>
                                </div>
                                <div v-if="saveTemplate" class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Modelo</label>
                                    <input type="text" v-model="templateName" class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Ex: Cliente X Padrão">
                                </div>

                                <div class="flex justify-end gap-4">
                                    <Link :href="route('uploads.index')" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Cancelar</Link>
                                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
                                        Converter e Gerar XML
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
