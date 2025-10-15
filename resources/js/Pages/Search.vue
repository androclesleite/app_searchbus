<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="container mx-auto px-4 py-12 max-w-5xl">
      <!-- Header -->
      <div class="text-center mb-8">
        <div
          class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-2xl mb-4"
        >
          <svg
            class="w-10 h-10 text-white"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
            ></path>
          </svg>
        </div>
        <h1 class="text-4xl font-semibold text-gray-900 mb-2">Busca de Passagens</h1>
        <p class="text-lg text-gray-600">Encontre as melhores opções de viagem</p>
      </div>

      <!-- Card de Busca -->
      <div
        class="max-w-4xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 p-8"
      >
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Informações da viagem</h2>

        <form @submit.prevent="handleSearch" class="space-y-6">
          <!-- Origem -->
          <CityAutocomplete
            label="Partindo de"
            placeholder="Digite a cidade de origem"
            v-model="form.from"
            :error="errors.from"
            @select="handleFromSelect"
            @error="handleFromError"
          />

          <!-- Destino -->
          <CityAutocomplete
            label="Indo para"
            placeholder="Digite a cidade de destino"
            v-model="form.to"
            :error="errors.to"
            @select="handleToSelect"
            @error="handleToError"
          />

          <!-- Data de Saída -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Data de Saída
            </label>
            <VueDatePicker
              v-model="form.travelDate"
              :min-date="new Date()"
              :enable-time-picker="false"
              placeholder="Selecione a data de viagem"
              format="dd/MM/yyyy"
              locale="pt-BR"
              auto-apply
              :teleport="true"
              :class="{ 'dp-error': errors.travelDate }"
            >
              <template #input-icon>
                <svg
                  class="w-5 h-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  ></path>
                </svg>
              </template>
            </VueDatePicker>
            <p v-if="errors.travelDate" class="mt-1 text-sm text-red-600">
              {{ errors.travelDate }}
            </p>
          </div>

          <!-- Aviso SP/PR -->
          <div class="bg-amber-50 border-l-4 border-amber-400 rounded-r-lg p-4">
            <div class="flex items-start">
              <svg
                class="w-5 h-5 text-amber-600 mt-0.5 mr-3 flex-shrink-0"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <p class="text-sm text-amber-800">
                <strong class="font-semibold">Atenção:</strong> Disponível apenas para
                viagens entre SP e PR
              </p>
            </div>
          </div>

          <!-- Botão de Busca -->
          <button
            type="submit"
            :disabled="loading || !isFormValid"
            class="w-full bg-primary-600 text-white py-3.5 px-6 rounded-lg font-medium text-base hover:bg-primary-700 transition-all duration-200 disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center justify-center space-x-2 shadow-sm hover:shadow-md"
          >
            <svg
              v-if="loading"
              class="animate-spin h-5 w-5 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            <svg
              v-else
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              ></path>
            </svg>
            <span>{{ loading ? "Buscando..." : "Buscar Passagens" }}</span>
          </button>
        </form>

        <!-- Mensagem de Erro Geral -->
        <div
          v-if="generalError"
          class="mt-6 bg-red-50 border-l-4 border-red-500 rounded-r-lg p-4"
        >
          <div class="flex items-start">
            <svg
              class="w-5 h-5 text-red-600 mt-0.5 mr-3 flex-shrink-0"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                clip-rule="evenodd"
              ></path>
            </svg>
            <p class="text-sm text-red-800">{{ generalError }}</p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="max-w-4xl mx-auto mt-6 text-center">
        <p class="text-xs text-gray-500">Sistema de busca de passagens - Teste técnico</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import CityAutocomplete from "../Components/CityAutocomplete.vue";
import { useSearchCache } from "../composables/useSearchCache";

const toast = useToast();
const { getFromCache, cleanExpiredCache } = useSearchCache();

// Limpa caches expirados ao montar o componente
onMounted(() => {
  cleanExpiredCache();
});

const form = ref({
  from: null,
  to: null,
  travelDate: "",
});

const errors = ref({
  from: null,
  to: null,
  travelDate: null,
});

const loading = ref(false);
const generalError = ref(null);

// Data mínima (hoje)
const minDate = computed(() => {
  const today = new Date();
  return today.toISOString().split("T")[0];
});

// Verifica se o formulário é válido
const isFormValid = computed(() => {
  return (
    form.value.from &&
    form.value.to &&
    form.value.travelDate &&
    !errors.value.from &&
    !errors.value.to &&
    !errors.value.travelDate
  );
});

// Handlers de seleção
const handleFromSelect = (stop) => {
  errors.value.from = null;
  generalError.value = null;
};

const handleToSelect = (stop) => {
  errors.value.to = null;
  generalError.value = null;
};

const handleFromError = (error) => {
  errors.value.from = error;
};

const handleToError = (error) => {
  errors.value.to = error;
};

// Formata data para YYYY-MM-DD
const formatDateToString = (date) => {
  if (!date) return "";
  if (typeof date === "string") return date;

  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
};

// Submete o formulário
const handleSearch = () => {
  // Limpa erros
  errors.value = {
    from: null,
    to: null,
    travelDate: null,
  };
  generalError.value = null;

  // Validações
  if (!form.value.from) {
    errors.value.from = "Selecione a cidade de origem";
    return;
  }

  if (!form.value.to) {
    errors.value.to = "Selecione a cidade de destino";
    return;
  }

  if (!form.value.travelDate) {
    errors.value.travelDate = "Selecione a data de viagem";
    return;
  }

  if (form.value.from === form.value.to) {
    generalError.value = "Origem e destino não podem ser iguais";
    toast.error("Origem e destino não podem ser iguais");
    return;
  }

  const dateString = formatDateToString(form.value.travelDate);

  // Verifica se existe cache válido
  const cachedData = getFromCache(form.value.from, form.value.to, dateString);

  if (cachedData) {
    // Usa dados do cache
    toast.success(`Dados do cache carregados! (salvos às ${cachedData.cachedAt})`);

    // Navega para página de resultados com dados em cache (via query params)
    router.get("/search", {
      from: form.value.from,
      to: form.value.to,
      data: dateString,
      useCache: "true",
    });
    return;
  }

  // Se não tem cache, faz requisição normal
  loading.value = true;
  toast.info("Buscando viagens disponíveis...");

  router.post(
    "/search",
    {
      from: form.value.from,
      to: form.value.to,
      data: dateString,
    },
    {
      onError: (errors) => {
        generalError.value = errors.message || "Erro ao buscar viagens";
        toast.error(errors.message || "Erro ao buscar viagens");
        loading.value = false;
      },
      onFinish: () => {
        loading.value = false;
      },
      onSuccess: () => {
        toast.success("Viagens encontradas!");
        loading.value = true;
      },
    }
  );
};
</script>

<style>
/* Customização VueDatePicker para Tailwind */
.dp__theme_light {
  --dp-primary-color: #4f46e5; /* primary-600 */
  --dp-primary-text-color: #ffffff;
  --dp-secondary-color: #cbd5e1;
  --dp-border-radius: 0.5rem;
  --dp-font-family: inherit;
}

.dp__input {
  padding: 0.75rem 2rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
}

.dp__input:hover {
  border-color: #9ca3af;
}

.dp__input:focus {
  border-color: #4f46e5; /* primary-600 */
  outline: none;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.dp-error .dp__input {
  border-color: #ef4444;
}

.dp__input_icon {
  padding-left: 0.5rem;
}
</style>
