<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Selecione seus assentos</h1>
            <div class="flex items-center gap-3 flex-wrap">
              <span class="text-gray-600">🚌 {{ trip.company.name }}</span>
              <span class="text-gray-400">•</span>
              <span class="text-gray-600">{{ trip.seatClass }}</span>
              <span class="text-gray-400">•</span>
              <span class="text-gray-600"
                >{{ formatTime(trip.departure.time) }} →
                {{ formatTime(trip.arrival.time) }}</span
              >
            </div>
          </div>
          <button
            @click="goBack"
            class="px-5 py-2.5 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Voltar
          </button>
        </div>
      </div>

      <!-- Aviso de seleção -->
      <div
        v-if="maxPassengers"
        class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6"
      >
        <p class="text-sm text-yellow-800">
          ⚠️ Selecione a quantidade de passageiros marcando as poltronas desejadas (No
          máximo {{ maxPassengers }}
          {{ maxPassengers === 1 ? "passageiro" : "passageiros" }})
        </p>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- Mapa de Assentos -->
        <div class="xl:col-span-3">
          <div class="bg-white rounded-lg shadow-md p-8 overflow-hidden">
            <!-- Legenda -->
            <div
              class="flex items-center justify-center gap-6 mb-8 pb-6 border-b flex-wrap"
            >
              <div class="flex items-center gap-2">
                <div
                  class="w-10 h-10 bg-white border-2 border-gray-300 rounded"
                ></div>
                <span class="text-sm text-gray-700">Livre</span>
              </div>
              <div class="flex items-center gap-2">
                <div
                  class="w-10 h-10 bg-blue-600 border-2 border-blue-700 rounded"
                ></div>
                <span class="text-sm text-gray-700">Selecionado</span>
              </div>
              <div class="flex items-center gap-2">
                <div
                  class="w-10 h-10 bg-gray-300 border-2 border-gray-400 rounded flex items-center justify-center"
                >
                  <span class="text-gray-600 text-sm">✕</span>
                </div>
                <span class="text-sm text-gray-700">Ocupado</span>
              </div>
            </div>

            <!-- Grid de Assentos com scroll -->
            <div v-if="seats.length > 0" class="space-y-8">
              <div v-for="floor in seats" :key="floor.floor">
                <div
                  v-if="floor.seats.length > 0"
                  class="border-2 border-gray-200 rounded-lg p-6 bg-gray-50 overflow-x-auto"
                >
                  <!-- Container com Motorista + Grid de Poltronas -->
                  <div class="flex gap-3 min-w-max">
                    <!-- Coluna do Motorista - APENAS no andar térreo -->
                    <div v-if="floor.floor === 0" class="flex flex-col justify-end">
                      <!-- MOTORISTA -->
                      <div
                        class="w-12 h-12 bg-gray-800 rounded-lg flex flex-col items-center justify-center border-2 border-yellow-500 shadow-sm"
                        title="Motorista (Frente do Ônibus)"
                      >
                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="text-[7px] text-yellow-400 font-bold mt-0.5">MOT</span>
                      </div>
                    </div>

                    <!-- Grid de Poltronas -->
                    <div class="flex flex-col gap-3">
                      <div
                        v-for="(row, rowIndex) in floor.seats"
                        :key="rowIndex"
                        class="flex gap-3"
                      >
                        <button
                          v-for="(seat, colIndex) in row"
                          :key="colIndex"
                          @click="toggleSeat(seat)"
                          :disabled="!isSeatSelectable(seat)"
                          class="w-12 h-12 rounded-lg transition-all duration-200 flex items-center justify-center shadow-sm relative"
                          :class="getSeatClass(seat)"
                          :title="getSeatTooltip(seat)"
                        >
                          <span v-if="seat.type === 'seat' && seat.seat && isOccupied(seat)" class="text-gray-600">✕</span>
                          <span v-else-if="seat.type === 'seat' && seat.seat" class="text-[10px] font-medium text-gray-400 absolute bottom-0.5 right-1">{{ seat.seat }}</span>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Info do andar -->
                  <div v-if="seats.length > 1" class="mt-6 text-center">
                    <span
                      class="inline-block bg-gray-200 px-4 py-2 rounded-full text-sm font-medium text-gray-700"
                    >
                      {{ floor.floor === 0 ? "Andar Térreo" : `${floor.floor}º Andar` }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Loader -->
            <div v-else class="text-center py-12">
              <div
                class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"
              ></div>
              <p class="mt-4 text-gray-600">Carregando assentos...</p>
            </div>
          </div>
        </div>

        <!-- Resumo lateral -->
        <div class="xl:col-span-1">
          <div class="bg-white rounded-lg shadow-md p-6 sticky top-6">
            <!-- Contador de seleção -->
            <div class="text-center mb-6 pb-6 border-b">
              <div class="text-4xl font-bold text-blue-600 mb-2">
                {{ selectedSeats.length }}/{{ maxPassengers || trip.availableSeats }}
              </div>
              <div class="text-sm text-gray-600">
                {{
                  selectedSeats.length === 0
                    ? "Nenhuma poltrona"
                    : selectedSeats.length === 1
                    ? "Poltrona selecionada"
                    : "Poltronas selecionadas"
                }}
              </div>
            </div>

            <!-- Lista de assentos selecionados -->
            <div class="mb-6">
              <h4 class="font-semibold text-gray-800 mb-3">Poltronas</h4>
              <div
                v-if="selectedSeats.length > 0"
                class="space-y-2 max-h-40 overflow-y-auto"
              >
                <div
                  v-for="seat in selectedSeats"
                  :key="seat"
                  class="flex items-center justify-between bg-blue-50 px-3 py-2 rounded"
                >
                  <span class="font-semibold text-blue-700 text-sm">
                    Poltrona {{ seat }}
                  </span>
                  <button
                    @click="removeSeat(seat)"
                    class="text-red-500 hover:text-red-700 text-lg leading-none"
                    title="Remover"
                  >
                    ✕
                  </button>
                </div>
              </div>
              <div
                v-else
                class="text-gray-400 text-sm text-center py-6 bg-gray-50 rounded"
              >
                Selecione os assentos no mapa
              </div>
            </div>

            <!-- Preços -->
            <div class="space-y-2 mb-6 pb-6 border-b text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Passagem ({{ selectedSeats.length }}x):</span>
                <span class="font-medium"
                  >R$ {{ formatPrice(trip.price.seatPrice * selectedSeats.length) }}</span
                >
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Taxas:</span>
                <span class="font-medium"
                  >R$ {{ formatPrice(trip.price.taxPrice * selectedSeats.length) }}</span
                >
              </div>
            </div>

            <!-- Total -->
            <div class="mb-6">
              <div class="flex justify-between items-center">
                <span class="text-gray-700 font-semibold">Total:</span>
                <div class="text-right">
                  <div class="text-2xl font-bold text-green-600">
                    R$ {{ formatPrice(totalPrice) }}
                  </div>
                  <div class="text-xs text-gray-500">por pessoa</div>
                </div>
              </div>
            </div>

            <!-- Botão -->
            <button
              @click="confirmSelection"
              :disabled="selectedSeats.length === 0 || loading"
              class="w-full py-4 bg-blue-600 text-white rounded-lg font-bold text-lg hover:bg-blue-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
            >
              {{ loading ? "PROCESSANDO..." : "FECHAR" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  seats: Array,
  trip: Object,
  company: Object,
});

const selectedSeats = ref([]);
const loading = ref(false);

const maxPassengers = computed(() => props.trip.maximumAllowedPassengers);

const totalPrice = computed(() => {
  return props.trip.price.price * selectedSeats.value.length;
});

const isSeatSelectable = (seat) => {
  if (seat.type !== "seat" || !seat.seat) return false;
  if (isOccupied(seat)) return false;

  // Se tem limite e já atingiu, só permite desselecionar
  if (maxPassengers.value && selectedSeats.value.length >= maxPassengers.value) {
    return selectedSeats.value.includes(seat.seat);
  }

  return true;
};

const isOccupied = (seat) => {
  return seat.occupied || !seat.seat || seat.seat === "";
};

const getSeatClass = (seat) => {
  if (seat.type !== "seat") {
    return "invisible";
  }

  if (selectedSeats.value.includes(seat.seat)) {
    return "bg-blue-600 border-2 border-blue-700 hover:bg-blue-700 cursor-pointer transform hover:scale-105";
  }

  if (isOccupied(seat)) {
    return "bg-gray-300 border-2 border-gray-400 cursor-not-allowed";
  }

  return "bg-white border-2 border-gray-300 hover:bg-gray-50 hover:border-blue-400 cursor-pointer transform hover:scale-105";
};

const getSeatTooltip = (seat) => {
  if (seat.type !== "seat" || !seat.seat) return "";
  if (isOccupied(seat)) return "Assento ocupado";
  if (selectedSeats.value.includes(seat.seat)) return "Clique para desselecionar";
  return `Selecionar poltrona ${seat.seat}`;
};

const toggleSeat = (seat) => {
  if (!isSeatSelectable(seat)) return;

  const seatNumber = seat.seat;
  const index = selectedSeats.value.indexOf(seatNumber);

  if (index > -1) {
    selectedSeats.value.splice(index, 1);
  } else {
    if (!maxPassengers.value || selectedSeats.value.length < maxPassengers.value) {
      selectedSeats.value.push(seatNumber);
    }
  }
};

const removeSeat = (seatNumber) => {
  const index = selectedSeats.value.indexOf(seatNumber);
  if (index > -1) {
    selectedSeats.value.splice(index, 1);
  }
};

const formatTime = (timeString) => {
  return timeString.substring(0, 5);
};

const formatPrice = (price) => {
  return price.toFixed(2).replace(".", ",");
};

const goBack = () => {
  window.history.back();
};

const confirmSelection = () => {
  if (selectedSeats.value.length === 0) return;

  loading.value = true;

  router.post(
    "/seats",
    {
      travelId: props.trip.id,
      selectedSeats: selectedSeats.value,
      tripData: props.trip,
    },
    {
      onFinish: () => {
        loading.value = false;
      },
    }
  );
};
</script>