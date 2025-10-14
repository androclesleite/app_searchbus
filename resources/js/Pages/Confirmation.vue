<template>
  <div
    class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center px-4 py-12"
  >
    <div class="max-w-2xl w-full bg-white rounded-xl shadow-sm border border-gray-200 p-8">
      <!-- Ícone de Sucesso -->
      <div class="text-center mb-8">
        <div
          class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-2xl mb-4"
        >
          <svg
            class="w-10 h-10 text-green-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
            ></path>
          </svg>
        </div>
        <h1 class="text-3xl font-semibold text-gray-900 mb-2">Seleção Confirmada!</h1>
        <p class="text-gray-600">Seus assentos foram selecionados com sucesso</p>
      </div>

      <!-- Resumo da Seleção -->
      <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="font-semibold text-gray-900 mb-4 text-lg">Resumo da Viagem</h2>

        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-gray-600">Companhia:</span>
            <span class="font-semibold">{{ trip.company.name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Origem:</span>
            <span class="font-semibold">{{ trip.from.name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Destino:</span>
            <span class="font-semibold">{{ trip.to.name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Data/Hora:</span>
            <span class="font-semibold">{{ formatDateTime(trip.departure) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Tipo:</span>
            <span class="font-semibold">{{ trip.seatClass }}</span>
          </div>
        </div>

        <div class="border-t mt-4 pt-4">
          <div class="flex justify-between items-center mb-2">
            <span class="text-gray-600">Assentos Selecionados:</span>
            <span class="font-bold text-lg text-blue-600">
              {{ selectedSeats.join(", ") }}
            </span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Quantidade:</span>
            <span class="font-semibold"
              >{{ selectedSeats.length }}
              {{ selectedSeats.length === 1 ? "assento" : "assentos" }}</span
            >
          </div>
        </div>
      </div>

      <!-- Informação sobre o Teste -->
      <div class="bg-amber-50 border-l-4 border-amber-400 rounded-r-xl p-4 mb-6">
        <div class="flex items-start">
          <svg class="w-5 h-5 text-amber-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
          </svg>
          <p class="text-sm text-amber-800">
            <strong class="font-semibold">Nota:</strong> Este é um teste técnico. Em produção, aqui seria exibido o código de reserva e opções de pagamento.
          </p>
        </div>
      </div>

      <!-- Botões de Ação -->
      <div class="space-y-3">
        <button
          @click="goHome"
          class="w-full py-3.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-all duration-200 flex items-center justify-center gap-2 shadow-sm hover:shadow-md"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          Fazer Nova Busca
        </button>
        <button
          @click="printDetails"
          class="w-full py-3.5 bg-white text-gray-700 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-all duration-200 flex items-center justify-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
          </svg>
          Imprimir Detalhes
        </button>
      </div>

      <!-- Footer -->
      <div class="mt-6 text-center text-xs text-gray-500">
        <p>
          ID da Viagem: <code class="bg-slate-100 px-2 py-1 rounded font-mono text-xs">{{ travelId }}</code>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
  success: Boolean,
  message: String,
  selectedSeats: Array,
  trip: Object,
  travelId: String,
});

// Formata data e hora
const formatDateTime = (dateTimeObj) => {
  const date = new Date(dateTimeObj.date + "T" + dateTimeObj.time);
  return date.toLocaleString("pt-BR", {
    day: "2-digit",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

// Volta para home
const goHome = () => {
  router.get("/");
};

// Imprime detalhes
const printDetails = () => {
  window.print();
};
</script>

<style scoped>
@media print {
  body {
    background: white;
  }
  button {
    display: none;
  }
}
</style>
