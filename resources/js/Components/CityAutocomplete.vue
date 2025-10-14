<template>
    <div class="relative">
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
            {{ label }}
        </label>
        
        <div class="relative">
            <input
                type="text"
                v-model="searchQuery"
                @input="handleInput"
                @focus="handleFocus"
                @blur="handleBlur"
                :placeholder="placeholder"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none"
                :class="{ 'border-red-500': error }"
            />

            <!-- Loading spinner -->
            <div v-if="isSearching" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <svg class="animate-spin h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown com resultados -->
        <div
            v-if="showDropdown && filteredStops.length > 0"
            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
            <div
                v-for="stop in filteredStops"
                :key="stop.id"
                @mousedown.prevent="selectStop(stop)"
                class="px-4 py-3 hover:bg-primary-50 cursor-pointer border-b border-gray-100 last:border-b-0"
            >
                <div class="font-medium text-gray-900">{{ stop.name }}</div>
                <div class="text-xs text-gray-500">{{ stop.type === 'station' ? 'Rodoviária' : 'Cidade' }}</div>
            </div>
        </div>

        <!-- Mensagem de erro -->
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
        
        <!-- Stop selecionado -->
        <div v-if="selectedStop && !searchQuery" class="mt-2 text-sm text-gray-600">
            Selecionado: <span class="font-medium">{{ selectedStop.name }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const toast = useToast();

const props = defineProps({
    label: String,
    placeholder: String,
    modelValue: String,
    error: String
});

const emit = defineEmits(['update:modelValue', 'select', 'error']);

const searchQuery = ref('');
const showDropdown = ref(false);
const stops = ref([]);
const selectedStop = ref(null);
const loading = ref(false);
const isSearching = ref(false);

// Carrega todas as paradas na montagem
const loadStops = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/stops');
        stops.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar paradas:', error);
        emit('error', 'Erro ao carregar cidades');
        toast.error('Erro ao carregar lista de cidades');
    } finally {
        loading.value = false;
    }
};

// Filtra as paradas baseado na busca
const filteredStops = computed(() => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        return [];
    }

    const query = searchQuery.value.toLowerCase();
    return stops.value.filter(stop => 
        stop.name.toLowerCase().includes(query)
    ).slice(0, 10); // Limita a 10 resultados
});

// Seleciona uma parada
const selectStop = async (stop) => {
    selectedStop.value = stop;
    searchQuery.value = stop.name;
    showDropdown.value = false;
    
    // Valida se é SP ou PR
    try {
        const response = await axios.post('/api/stops/validate', {
            stopId: stop.id
        });

        if (!response.data.allowed) {
            const errorMsg = `Apenas cidades de SP e PR são permitidas. ${stop.name} é do estado ${response.data.state}`;
            emit('error', errorMsg);
            emit('update:modelValue', null);
            toast.warning(errorMsg);
        } else {
            emit('update:modelValue', stop.id);
            emit('select', stop);
            emit('error', null);
            toast.success(`${stop.name} selecionada`);
        }
    } catch (error) {
        console.error('Erro ao validar parada:', error);
        emit('error', 'Erro ao validar a cidade selecionada');
        toast.error('Erro ao validar a cidade selecionada');
    }
};

// Função debounced para mostrar dropdown (300ms delay)
const debouncedShowDropdown = useDebounceFn(() => {
    if (searchQuery.value.length >= 2) {
        showDropdown.value = true;
        isSearching.value = false;
    }
}, 300);

// Manipula input com debounce
const handleInput = () => {
    // Limpa seleção se usuário está digitando
    if (selectedStop.value && searchQuery.value !== selectedStop.value.name) {
        selectedStop.value = null;
        emit('update:modelValue', null);
    }

    // Esconde dropdown se query muito curta
    if (searchQuery.value.length < 2) {
        showDropdown.value = false;
        isSearching.value = false;
        return;
    }

    // Mostra loading enquanto espera debounce
    isSearching.value = true;
    showDropdown.value = false;

    // Aplica debounce de 300ms antes de mostrar dropdown
    debouncedShowDropdown();
};

// Manipula focus
const handleFocus = () => {
    if (searchQuery.value.length >= 2) {
        showDropdown.value = true;
    }
};

// Manipula blur (perda de foco)
const handleBlur = () => {
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
};

// Carrega paradas ao montar
loadStops();

// Watch para limpar quando modelValue for null
watch(() => props.modelValue, (newValue) => {
    if (!newValue) {
        searchQuery.value = '';
        selectedStop.value = null;
    }
});
</script>