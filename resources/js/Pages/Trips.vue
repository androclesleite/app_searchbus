<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Header com informações da busca -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="font-medium text-gray-900">{{ searchParams.from.displayName || searchParams.from.name }}</span>
                        </div>
                        <span class="text-gray-400 font-medium">→</span>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="font-medium text-gray-900">{{ searchParams.to.displayName || searchParams.to.name }}</span>
                        </div>
                        <span class="text-gray-300">•</span>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-gray-600">{{ formatDate(searchParams.travelDate) }}</span>
                        </div>
                    </div>
                    <button
                        @click="goBack"
                        class="px-5 py-2.5 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Nova Busca
                    </button>
                </div>
            </div>

            <!-- Filtros e Ordenação -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
                <div class="flex flex-wrap gap-4 items-center justify-between">
                    <!-- Filtros de Horário -->
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="period in timePeriods"
                            :key="period.value"
                            @click="selectedPeriod = selectedPeriod === period.value ? null : period.value"
                            class="px-4 py-2 rounded-lg border transition-all duration-200 text-sm font-medium"
                            :class="selectedPeriod === period.value
                                ? 'border-blue-600 bg-blue-600 text-white shadow-sm'
                                : 'border-gray-300 text-gray-700 hover:border-gray-400 hover:bg-gray-50'"
                        >
                            {{ period.label }}
                        </button>
                    </div>

                    <!-- Ordenação -->
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 font-medium">Ordenar:</span>
                        <select
                            v-model="sortBy"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white font-medium text-gray-700"
                        >
                            <option value="time">Mais cedo</option>
                            <option value="price">Menor preço</option>
                            <option value="duration">Menor duração</option>
                        </select>
                    </div>
                </div>

                <!-- Contador de resultados -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-gray-700 text-sm">
                        <strong class="font-semibold text-gray-900">{{ filteredTrips.length }}</strong> {{ filteredTrips.length === 1 ? 'viagem encontrada' : 'viagens encontradas' }}
                    </p>
                </div>
            </div>

            <!-- Lista de viagens -->
            <div v-if="filteredTrips.length > 0" class="space-y-4">
                <div
                    v-for="trip in filteredTrips"
                    :key="trip.id"
                    class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 overflow-hidden"
                >
                    <div class="p-6">
                        <div class="flex items-start gap-6">
                            <!-- Logo da Companhia -->
                            <div class="flex-shrink-0">
                                <div class="w-24 h-16 flex items-center justify-center bg-gray-50 rounded border border-gray-200">
                                    <!-- Loading skeleton -->
                                    <div v-if="companyLogos[trip.company.id] === 'loading'" class="w-full h-full bg-gray-200 animate-pulse rounded"></div>
                                    <!-- Logo carregado -->
                                    <img
                                        v-else-if="companyLogos[trip.company.id] && companyLogos[trip.company.id] !== 'loading'"
                                        :src="companyLogos[trip.company.id]"
                                        :alt="trip.company.name"
                                        class="max-w-full max-h-full object-contain"
                                        @error="handleImageError(trip.company.id)"
                                        loading="lazy"
                                    />
                                    <!-- Fallback icon -->
                                    <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                </div>
                                <div class="text-xs text-center text-gray-500 mt-1 truncate w-24">
                                    {{ trip.company.name }}
                                </div>
                            </div>

                            <!-- Informações da viagem -->
                            <div class="flex-1 min-w-0">
                                <!-- Horários -->
                                <div class="flex items-center gap-6 mb-4">
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-gray-800">
                                            {{ formatTime(trip.departure.time) }}
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            {{ formatShortLocation(trip.from.name) }}
                                        </div>
                                    </div>

                                    <div class="flex-1 flex flex-col items-center min-w-0">
                                        <div class="text-xs text-gray-500 mb-1">
                                            Duração: {{ formatDuration(trip.travelDuration) }}
                                        </div>
                                        <div class="w-full border-t-2 border-gray-300 relative">
                                            <div class="absolute right-0 top-1/2 transform -translate-y-1/2">
                                                <!-- <span class="text-gray-400">→</span> -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-gray-800">
                                            {{ formatTime(trip.arrival.time) }}
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            {{ formatShortLocation(trip.to.name) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Detalhes -->
                                <div class="flex flex-wrap items-center gap-4 text-sm">
                                    <span class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg font-medium border border-blue-100">
                                        {{ trip.seatClass }}
                                    </span>
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <span>{{ trip.availableSeats }} disponíveis</span>
                                    </div>
                                    <span v-if="trip.withBPE" class="flex items-center gap-1.5 text-green-600 font-medium">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Embarque direto
                                    </span>
                                </div>
                            </div>

                            <!-- Preço e Ação -->
                            <div class="flex-shrink-0 text-right">
                                <div class="mb-3">
                                    <div class="text-sm text-gray-500 mb-1">por pessoa</div>
                                    <div class="text-3xl font-bold text-green-600">
                                        R$ {{ formatPrice(trip.price.price) }}
                                    </div>
                                </div>

                                <button
                                    @click="selectTrip(trip)"
                                    :disabled="trip.availableSeats === 0"
                                    class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-all duration-200 disabled:bg-gray-300 disabled:cursor-not-allowed whitespace-nowrap shadow-sm hover:shadow-md"
                                >
                                    {{ trip.availableSeats === 0 ? 'Esgotado' : 'Selecionar' }}
                                </button>

                                <div class="text-xs text-gray-500 mt-2 flex items-center justify-center gap-1">
                                    <span>+</span>
                                    <span>Taxa R$ {{ formatPrice(trip.price.taxPrice) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aviso de cancelamento -->
                    <div v-if="trip.allowCanceling" class="px-6 py-2 bg-gray-50 border-t border-gray-200">
                        <p class="text-xs text-gray-600">
                            ✓ Cancelamento gratuito até {{ formatCancellationDate(trip.travelCancellationLimitDate) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Mensagem quando não há resultados -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="text-2xl font-semibold text-gray-900 mb-2">
                    Nenhuma viagem encontrada
                </h2>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">
                    Não há viagens disponíveis para os filtros selecionados.
                </p>
                <button
                    @click="clearFilters"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md"
                >
                    Limpar filtros
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    trips: Array,
    searchParams: Object
});

const selectedPeriod = ref(null);
const sortBy = ref('time');
const companyLogos = ref({});

const timePeriods = [
    { label: '00h00 - 05h59', value: 'early', start: 0, end: 6 },
    { label: '06h00 - 11h59', value: 'morning', start: 6, end: 12 },
    { label: '12h00 - 17h59', value: 'afternoon', start: 12, end: 18 },
    { label: '18h00 - 23h59', value: 'night', start: 18, end: 24 },
];

// Lazy loading de logos com carregamento assíncrono
const loadCompanyLogo = async (companyId) => {
    if (companyLogos.value[companyId] !== undefined) {
        return; // Já carregado ou tentativa já feita
    }

    companyLogos.value[companyId] = 'loading'; // Marca como carregando

    try {
        const response = await axios.get(`/api/companies/${companyId}`);
        if (response.data.logo) {
            companyLogos.value[companyId] = response.data.logo.svg || response.data.logo.jpg;
        } else {
            companyLogos.value[companyId] = null;
        }
    } catch (error) {
        console.log(`Logo não disponível para companhia ${companyId}`);
        companyLogos.value[companyId] = null;
    }
};

// Carrega logos das companhias visíveis
onMounted(() => {
    const uniqueCompanies = [...new Set(props.trips.map(t => t.company.id))];

    // Carrega as primeiras 5 logos imediatamente
    uniqueCompanies.slice(0, 5).forEach(companyId => {
        loadCompanyLogo(companyId);
    });

    // Carrega o restante com delay
    uniqueCompanies.slice(5).forEach((companyId, index) => {
        setTimeout(() => {
            loadCompanyLogo(companyId);
        }, (index + 1) * 200);
    });
});

// Filtra e ordena viagens
const filteredTrips = computed(() => {
    let filtered = [...props.trips];

    // Filtro por período
    if (selectedPeriod.value) {
        const period = timePeriods.find(p => p.value === selectedPeriod.value);
        filtered = filtered.filter(trip => {
            const hour = parseInt(trip.departure.time.split(':')[0]);
            return hour >= period.start && hour < period.end;
        });
    }

    // Ordenação
    filtered.sort((a, b) => {
        switch (sortBy.value) {
            case 'time':
                return a.departure.time.localeCompare(b.departure.time);
            case 'price':
                return a.price.price - b.price.price;
            case 'duration':
                return a.travelDuration - b.travelDuration;
            default:
                return 0;
        }
    });

    return filtered;
});

const handleImageError = (companyId) => {
    companyLogos.value[companyId] = null;
};

const formatDate = (dateString) => {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const formatTime = (timeString) => {
    return timeString.substring(0, 5);
};

const formatDuration = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours}h ${minutes}min`;
};

const formatPrice = (price) => {
    return price.toFixed(2).replace('.', ',');
};

const formatShortLocation = (location) => {
    // Remove detalhes extras, mantém apenas cidade
    return location.split('-')[0].trim();
};

const formatCancellationDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const clearFilters = () => {
    selectedPeriod.value = null;
    sortBy.value = 'time';
};

const goBack = () => {
    router.get('/');
};

const selectTrip = (trip) => {
    // Navega para página de assentos preservando contexto via query params
    router.get(`/trips/${trip.id}/seats`, {
        from: props.searchParams.from.id,
        to: props.searchParams.to.id,
        data: props.searchParams.travelDate
    });
};
</script>