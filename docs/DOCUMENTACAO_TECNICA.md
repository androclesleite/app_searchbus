# Documentação Técnica - SearchBus

## O que o Sistema Faz

Sistema de busca e reserva de passagens de ônibus que integra com a API Quero Passagem. Permite ao usuário:

1. Buscar viagens entre cidades de SP e PR
2. Visualizar lista de viagens disponíveis com filtros
3. Selecionar assentos visualmente em um mapa
4. Confirmar a seleção de assentos

## Fluxo da Aplicação

```
1. Usuário acessa página inicial (/)
   ↓
2. Preenche origem, destino e data
   ↓
3. Sistema busca viagens disponíveis
   ↓
4. Usuário visualiza lista de viagens com filtros
   ↓
5. Seleciona uma viagem
   ↓
6. Sistema exibe mapa de assentos
   ↓
7. Usuário seleciona assentos
   ↓
8. Confirma seleção
   ↓
9. Sistema exibe confirmação
```

---

## BACKEND

### Arquitetura

**Padrão:** Thin Controllers + Fat Service + Form Requests

**Princípios:** Clean Code, SOLID (especialmente SRP - Single Responsibility Principle)

---

### 1. FORM REQUESTS (Validação)

#### `SearchTripRequest.php`
**Localização:** `app/Http/Requests/SearchTripRequest.php`

**Responsabilidade:** Valida dados de busca de viagens

**Campos validados:**
- `from` (string, obrigatório) - ID da cidade origem
- `to` (string, obrigatório, diferente de `from`) - ID da cidade destino
- `data` (date, obrigatório, hoje ou futuro) - Data da viagem

**Regras especiais:**
- Origem e destino não podem ser iguais
- Data não pode ser no passado

---

#### `SelectSeatRequest.php`
**Localização:** `app/Http/Requests/SelectSeatRequest.php`

**Responsabilidade:** Valida contexto ao acessar página de assentos

**Campos validados:**
- `from` (string, opcional) - ID da cidade origem
- `to` (string, opcional) - ID da cidade destino
- `data` (date, opcional) - Data da viagem

**Nota:** Campos opcionais porque vêm via query params para preservar estado

---

#### `ConfirmBookingRequest.php`
**Localização:** `app/Http/Requests/ConfirmBookingRequest.php`

**Responsabilidade:** Valida confirmação de seleção de assentos

**Campos validados:**
- `travelId` (string, obrigatório) - ID da viagem
- `selectedSeats` (array, obrigatório, min:1) - Array de assentos selecionados
- `tripData` (array, obrigatório) - Dados completos da viagem

---

### 2. CONTROLLERS (Thin - apenas roteamento)

#### `SearchController.php`
**Localização:** `app/Http/Controllers/SearchController.php`

**Métodos:**

**`index()`**
- **Rota:** `GET /`
- **O que faz:** Renderiza página inicial de busca
- **Retorna:** View Inertia `Search`

**`getStops()`**
- **Rota:** `GET /api/stops`
- **O que faz:** Retorna lista de todas as paradas para autocomplete
- **Chama:** `QueroPassagemService->getStops()`
- **Retorna:** JSON com paradas formatadas

**`validateStop(Request $request)`**
- **Rota:** `POST /api/stops/validate`
- **O que faz:** Valida se uma parada é de SP ou PR
- **Chama:** `QueroPassagemService->getStop()`
- **Retorna:** JSON com `allowed` (bool), `state` e `name`

**`search(SearchTripRequest $request)`**
- **Rota:** `POST /search`
- **O que faz:** Processa busca e redireciona para lista de viagens
- **Validações:**
  1. Usa `SearchTripRequest` (validação automática)
  2. Verifica se origem é SP/PR via `validateStopState()`
  3. Verifica se destino é SP/PR via `validateStopState()`
- **Retorna:** Redirect para `GET /trips?from=X&to=Y&data=Z`

---

#### `TripController.php`
**Localização:** `app/Http/Controllers/TripController.php`

**Métodos:**

**`index(SearchTripRequest $request)`**
- **Rota:** `GET /trips?from=X&to=Y&data=Z`
- **O que faz:** Lista viagens disponíveis
- **Chama:** `QueroPassagemService->searchTripsWithValidation()`
- **Retorna:** View Inertia `Trips` com viagens e parâmetros de busca
- **Cache:** Utiliza cache de 5 minutos

**`show(string $tripId, SelectSeatRequest $request)`**
- **Rota:** `GET /trips/{tripId}/seats?from=X&to=Y&data=Z`
- **O que faz:** Exibe mapa de assentos da viagem
- **Processo:**
  1. Busca assentos via `getSeats($tripId)`
  2. Busca dados completos da viagem via `findTripById()`
  3. Valida se viagem foi encontrada
- **Retorna:** View Inertia `Seats` com assentos, trip e searchParams
- **Cache:** Assentos (1 min), Viagens (5 min)

**`getCompany(int $companyId)`**
- **Rota:** `GET /api/companies/{id}`
- **O que faz:** Retorna informações de uma companhia (logo, nome, etc)
- **Chama:** `QueroPassagemService->getCompany()`
- **Retorna:** JSON com dados da companhia
- **Cache:** 24 horas

---

#### `SeatController.php`
**Localização:** `app/Http/Controllers/SeatController.php`

**Métodos:**

**`store(ConfirmBookingRequest $request)`**
- **Rota:** `POST /seats`
- **O que faz:** Confirma seleção de assentos
- **Retorna:** View Inertia `Confirmation` com resumo da seleção
- **Nota:** Em produção, aqui seria criada a reserva via API

**`createBooking(Request $request)`**
- **Rota:** `POST /bookings`
- **O que faz:** Cria reserva na API Quero Passagem (opcional/futuro)
- **Chama:** `QueroPassagemService->createBooking()`
- **Retorna:** JSON com dados da reserva

---

### 3. SERVICE (Fat - lógica de negócio)

#### `QueroPassagemService.php`
**Localização:** `app/Services/QueroPassagemService.php`

**Responsabilidade:** Centraliza toda integração com API Quero Passagem e lógica de negócio

**Configuração:**
- Base URI via config
- Autenticação Basic Auth (username + password)
- Timeout: 30 segundos

**Cache Strategy:**
```php
CACHE_STOPS = 3600     // 1 hora (dados estáveis)
CACHE_STOP = 3600      // 1 hora (dados estáveis)
CACHE_COMPANY = 86400  // 24 horas (muito estável)
CACHE_TRIPS = 300      // 5 minutos (volátil)
CACHE_SEATS = 60       // 1 minuto (muito volátil)
```

---

**Métodos Principais:**

**`getStops(): array`**
- **O que faz:** Lista todas as paradas disponíveis
- **Cache:** 1 hora
- **Endpoint API:** `GET /stops`
- **Retorna:** Array de paradas

**`getStop(string $stopId): array`**
- **O que faz:** Busca detalhes de uma parada específica
- **Cache:** 1 hora (key: `queropassagem.stop.{$stopId}`)
- **Endpoint API:** `GET /stops/{stopId}`
- **Retorna:** Array com dados da parada (name, displayName, state, etc)

**`searchTrips(string $from, string $to, string $travelDate): array`**
- **O que faz:** Busca viagens disponíveis
- **Cache:** 5 minutos (key: `queropassagem.trips.{$from}.{$to}.{$travelDate}`)
- **Endpoint API:** `POST /new/search`
- **Payload:**
  ```json
  {
    "from": "id-parada-origem",
    "to": "id-parada-destino",
    "travelDate": "2025-01-15",
    "affiliateCode": "seu_codigo",
    "include-connections": false
  }
  ```
- **Retorna:** Array de viagens

**`getSeats(string $travelId, string $orientation = 'horizontal'): array`**
- **O que faz:** Busca assentos disponíveis de uma viagem
- **Cache:** 1 minuto (key: `queropassagem.seats.{$travelId}`)
- **Endpoint API:** `POST /new/seats`
- **Payload:**
  ```json
  {
    "travelId": "id-viagem",
    "orientation": "horizontal",
    "type": "matrix"
  }
  ```
- **Retorna:** Array com matriz de assentos por andar

**`getCompanies(): array`**
- **O que faz:** Lista todas as companhias
- **Cache:** Não utiliza (raramente usado)
- **Endpoint API:** `GET /companies`
- **Retorna:** Array de companhias

**`getCompany(int $companyId): array`**
- **O que faz:** Busca detalhes de uma companhia (logo, nome, etc)
- **Cache:** 24 horas (key: `queropassagem.company.{$companyId}`)
- **Endpoint API:** `GET /companies/{companyId}`
- **Retorna:** Array com dados da companhia

**`createBooking(array $bookingData): array`**
- **O que faz:** Cria uma reserva na API
- **Cache:** Não utiliza (operação de escrita)
- **Endpoint API:** `POST /new/booking`
- **Retorna:** Array com dados da reserva criada

---

**Métodos de Validação e Orquestração:**

**`validateStopState(string $stopId): bool`**
- **O que faz:** Valida se parada é de SP ou PR
- **Processo:**
  1. Busca parada via `getStop()`
  2. Verifica se `state` está em `['SP', 'PR']`
- **Retorna:** `true` se permitido, `false` caso contrário

**`searchTripsWithValidation(string $from, string $to, string $travelDate): array`**
- **O que faz:** Busca viagens com validação completa e dados enriquecidos
- **Processo:**
  1. Busca paradas completas (origem e destino)
  2. Busca viagens
  3. Ordena viagens por horário de partida
- **Retorna:**
  ```php
  [
    'trips' => [...],           // Array de viagens ordenadas
    'searchParams' => [
      'from' => [...],          // Objeto completo da parada origem
      'to' => [...],            // Objeto completo da parada destino
      'travelDate' => '2025-01-15'
    ]
  ]
  ```

**`findTripById(string $from, string $to, string $travelDate, string $tripId): ?array`**
- **O que faz:** Busca uma viagem específica por ID
- **Processo:**
  1. Busca todas as viagens via `searchTrips()` (usa cache!)
  2. Itera procurando pelo `id` correspondente
- **Retorna:** Array da viagem ou `null` se não encontrado
- **Importante:** Reutiliza cache de viagens, não faz nova requisição à API

---

### 4. ROTAS

**Localização:** `routes/web.php`

```php
// Página inicial
GET  /                              -> SearchController@index

// Busca
POST /search                        -> SearchController@search

// Viagens
GET  /trips?from=X&to=Y&data=Z     -> TripController@index

// Assentos
GET  /trips/{tripId}/seats?...      -> TripController@show

// Confirmação
POST /seats                         -> SeatController@store

// Reserva (opcional)
POST /bookings                      -> SeatController@createBooking

// API Endpoints
GET  /api/stops                     -> SearchController@getStops
POST /api/stops/validate            -> SearchController@validateStop
GET  /api/companies/{id}            -> TripController@getCompany
```

**Padrão RESTful:** URLs com query params para estado stateful (F5 funciona!)

---

## FRONTEND

### Arquitetura

**Stack:** Vue.js 3 (Composition API) + Inertia.js + Tailwind CSS

**Padrão:** SPA (Single Page Application) com server-side routing via Inertia

---

### 1. PÁGINAS (Views Inertia)

#### `Search.vue`
**Localização:** `resources/js/Pages/Search.vue`

**Responsabilidade:** Página inicial de busca

**Props:** Nenhuma

**Estado Local:**
```javascript
form = {
  from: null,           // ID da parada origem
  to: null,             // ID da parada destino
  travelDate: ''        // Data selecionada
}
errors = {
  from: null,
  to: null,
  travelDate: null
}
loading = false
generalError = null
```

**Computed:**
- `minDate` - Data mínima (hoje)
- `isFormValid` - Valida se form está completo

**Métodos:**

**`handleFromSelect(stop)`**
- **O que faz:** Callback quando origem é selecionada
- **Ações:** Limpa erros de origem

**`handleToSelect(stop)`**
- **O que faz:** Callback quando destino é selecionado
- **Ações:** Limpa erros de destino

**`handleFromError(error)`**
- **O que faz:** Callback quando há erro na validação de origem
- **Ações:** Define `errors.from`

**`handleToError(error)`**
- **O que faz:** Callback quando há erro na validação de destino
- **Ações:** Define `errors.to`

**`handleSearch()`**
- **O que faz:** Submete formulário de busca
- **Validações client-side:**
  1. Campos obrigatórios preenchidos
  2. Origem ≠ Destino
- **Ação:** `router.post('/search', { from, to, data })`
- **Nota:** Backend redireciona para `GET /trips?...`

**Componentes utilizados:**
- `CityAutocomplete` - Autocomplete de cidades

---

#### `Trips.vue`
**Localização:** `resources/js/Pages/Trips.vue`

**Responsabilidade:** Lista viagens disponíveis com filtros

**Props:**
```javascript
trips: Array           // Lista de viagens
searchParams: Object   // { from, to, travelDate }
```

**Estado Local:**
```javascript
selectedPeriod = null    // Filtro de período (early, morning, afternoon, night)
sortBy = 'time'          // Ordenação (time, price, duration)
companyLogos = {}        // Cache de logos { companyId: logoUrl }
```

**Computed:**

**`filteredTrips`**
- **O que faz:** Filtra e ordena viagens
- **Filtros:**
  1. Por período do dia (se selecionado)
  2. Ordenação (por horário, preço ou duração)
- **Retorna:** Array de viagens filtradas/ordenadas

**Métodos:**

**`loadCompanyLogo(companyId)`**
- **O que faz:** Carrega logo da companhia via API
- **Processo:**
  1. Marca como "loading"
  2. Faz request `GET /api/companies/{id}`
  3. Armazena URL do logo (SVG ou JPG)
- **Lazy loading:** Primeiras 5 imediatamente, resto com delay de 200ms

**`handleImageError(companyId)`**
- **O que faz:** Fallback quando logo não carrega
- **Ação:** Define logo como `null` (exibe ícone genérico)

**`formatDate(dateString)`**
- **O que faz:** Formata data para português
- **Formato:** "15 de janeiro de 2025"

**`formatTime(timeString)`**
- **O que faz:** Formata hora
- **Formato:** "14:30" (primeiros 5 caracteres)

**`formatDuration(seconds)`**
- **O que faz:** Formata duração em segundos
- **Formato:** "5h 30min"

**`formatPrice(price)`**
- **O que faz:** Formata preço
- **Formato:** "129,90" (vírgula decimal)

**`formatShortLocation(location)`**
- **O que faz:** Exibe apenas cidade (remove detalhes)
- **Exemplo:** "São Paulo - Tietê" → "São Paulo"

**`formatCancellationDate(dateString)`**
- **O que faz:** Formata data limite de cancelamento
- **Formato:** "15/01 14:30"

**`clearFilters()`**
- **O que faz:** Limpa filtros aplicados
- **Ação:** Reseta `selectedPeriod` e `sortBy`

**`goBack()`**
- **O que faz:** Volta para página inicial
- **Ação:** `router.get('/')`

**`selectTrip(trip)`**
- **O que faz:** Seleciona viagem e vai para assentos
- **Ação:** `router.get(`/trips/${trip.id}/seats`, { from, to, data })`
- **Importante:** Preserva contexto via query params!

**Lifecycle:**
- `onMounted()` - Inicia carregamento lazy de logos

---

#### `Seats.vue`
**Localização:** `resources/js/Pages/Seats.vue`

**Responsabilidade:** Mapa visual de assentos para seleção

**Props:**
```javascript
seats: Array         // Matriz de assentos por andar
trip: Object         // Dados completos da viagem
searchParams: Object // Contexto da busca (opcional)
```

**Estado Local:**
```javascript
selectedSeats = []   // Array de números de assentos selecionados
loading = false      // Estado de loading ao confirmar
```

**Computed:**

**`maxPassengers`**
- **O que faz:** Limite máximo de passageiros
- **Fonte:** `trip.maximumAllowedPassengers`

**`totalPrice`**
- **O que faz:** Calcula preço total
- **Cálculo:** `trip.price.price * selectedSeats.length`

**Métodos:**

**`isSeatSelectable(seat): bool`**
- **O que faz:** Verifica se assento pode ser selecionado
- **Regras:**
  1. Deve ser tipo "seat" e ter número
  2. Não pode estar ocupado
  3. Se atingiu limite, só permite desselecionar
- **Retorna:** `true` se selecionável

**`isOccupied(seat): bool`**
- **O que faz:** Verifica se assento está ocupado
- **Regras:** `seat.occupied === true` ou sem número
- **Retorna:** `true` se ocupado

**`getSeatClass(seat): string`**
- **O que faz:** Retorna classes CSS para estilo do assento
- **Estados:**
  - Invisível (não é assento)
  - Azul (selecionado)
  - Cinza (ocupado)
  - Branco com borda (disponível)

**`getSeatTooltip(seat): string`**
- **O que faz:** Retorna texto do tooltip
- **Variações:**
  - "Assento ocupado"
  - "Clique para desselecionar"
  - "Selecionar poltrona X"

**`toggleSeat(seat)`**
- **O que faz:** Alterna seleção de assento
- **Processo:**
  1. Verifica se é selecionável
  2. Se já selecionado, remove
  3. Se não, adiciona (respeitando limite)

**`removeSeat(seatNumber)`**
- **O que faz:** Remove assento da seleção
- **Uso:** Botão "X" na lista lateral

**`formatTime(timeString)`**
- **O que faz:** Formata hora (primeiros 5 chars)

**`formatPrice(price)`**
- **O que faz:** Formata preço com vírgula

**`goBack()`**
- **O que faz:** Volta para lista de viagens
- **Ação:** `window.history.back()`
- **Alternativa:** Poderia usar query params para reconstruir URL

**`confirmSelection()`**
- **O que faz:** Confirma seleção de assentos
- **Validação:** Pelo menos 1 assento selecionado
- **Ação:** `router.post('/seats', { travelId, selectedSeats, tripData })`

**Layout:**
- Grid de assentos com scroll horizontal (overflow-x-auto)
- Motorista posicionado na primeira linha (andar 0)
- Legenda: Livre (branco), Selecionado (azul), Ocupado (cinza com X)
- Sidebar com resumo e botão de confirmação

---

#### `Confirmation.vue`
**Localização:** `resources/js/Pages/Confirmation.vue`

**Responsabilidade:** Página de confirmação da seleção

**Props:**
```javascript
success: Boolean
message: String
selectedSeats: Array
trip: Object
travelId: String
```

**Métodos:**

**`formatDateTime(dateTimeObj)`**
- **O que faz:** Formata data e hora completa
- **Formato:** "15 de janeiro de 2025, 14:30"

**`goHome()`**
- **O que faz:** Volta para página inicial
- **Ação:** `router.get('/')`

**`printDetails()`**
- **O que faz:** Imprime página de confirmação
- **Ação:** `window.print()`

**Layout:**
- Card de sucesso com ícone verde
- Resumo completo da viagem
- Lista de assentos selecionados
- Nota sobre ambiente de teste
- Botões: "Fazer Nova Busca" e "Imprimir Detalhes"

---

### 2. COMPONENTES

#### `CityAutocomplete.vue`
**Localização:** `resources/js/Components/CityAutocomplete.vue`

**Responsabilidade:** Autocomplete para seleção de cidades

**Props:**
```javascript
label: String
placeholder: String
modelValue: String
error: String
```

**Estado Local:**
```javascript
stops = []              // Lista completa de paradas
filteredStops = []      // Paradas filtradas pela busca
searchTerm = ''         // Termo de busca
selectedStop = null     // Parada selecionada
showDropdown = false    // Controle do dropdown
loading = false         // Loading ao buscar paradas
```

**Métodos:**

**`loadStops()`**
- **O que faz:** Carrega lista de paradas da API
- **Ação:** `GET /api/stops`
- **Cache:** Client-side (carrega 1x por sessão)

**`filterStops()`**
- **O que faz:** Filtra paradas pelo termo de busca
- **Processo:**
  1. Normaliza texto (lowercase, remove acentos)
  2. Busca em `name` e `displayName`
  3. Limita a 10 resultados

**`selectStop(stop)`**
- **O que faz:** Seleciona uma parada
- **Ações:**
  1. Valida via `POST /api/stops/validate`
  2. Se permitido (SP/PR), emite evento `@select`
  3. Se não, emite evento `@error`

**`clearSelection()`**
- **O que faz:** Limpa seleção
- **Ações:** Reseta campos e fecha dropdown

**Events:**
- `@select` - Parada válida selecionada
- `@error` - Erro de validação (não é SP/PR)

---

## Fluxo de Dados Completo

### 1. Busca de Viagens

```
[Frontend - Search.vue]
  ↓ router.post('/search', { from, to, data })
[Backend - SearchController@search]
  ↓ Valida com SearchTripRequest
  ↓ validateStopState(from) → QueroPassagemService
  ↓ validateStopState(to) → QueroPassagemService
  ↓ redirect()->route('trips.index', [...])
[Backend - TripController@index]
  ↓ searchTripsWithValidation() → QueroPassagemService
    ↓ getStop(from) [Cache: 1h]
    ↓ getStop(to) [Cache: 1h]
    ↓ searchTrips() [Cache: 5min]
    ↓ Ordena por horário
  ↓ Inertia::render('Trips', [...])
[Frontend - Trips.vue]
  ↓ Renderiza lista
  ↓ Aplica filtros client-side
  ↓ Carrega logos (lazy)
```

### 2. Seleção de Assentos

```
[Frontend - Trips.vue]
  ↓ router.get(`/trips/${tripId}/seats`, { from, to, data })
[Backend - TripController@show]
  ↓ Valida com SelectSeatRequest
  ↓ getSeats(tripId) → QueroPassagemService [Cache: 1min]
  ↓ findTripById(from, to, data, tripId) → QueroPassagemService
    ↓ searchTrips() [Cache: 5min - REUTILIZA!]
    ↓ Busca trip com id === tripId
  ↓ Inertia::render('Seats', [...])
[Frontend - Seats.vue]
  ↓ Renderiza mapa
  ↓ Usuário seleciona assentos
  ↓ router.post('/seats', { travelId, selectedSeats, tripData })
[Backend - SeatController@store]
  ↓ Valida com ConfirmBookingRequest
  ↓ Inertia::render('Confirmation', [...])
[Frontend - Confirmation.vue]
  ↓ Exibe confirmação
```

---

## Estratégia de Cache

### Por que Cache?

API externa pode ser lenta. Cache reduz latência e custos.

### TTLs Escolhidos

| Recurso | TTL | Justificativa |
|---------|-----|---------------|
| Paradas | 1 hora | Cidades raramente mudam |
| Companhias | 24 horas | Dados muito estáveis |
| Viagens | 5 minutos | Preços/disponibilidade mudam |
| Assentos | 1 minuto | Altamente volátil (outros usuários) |

### Cache Keys

```
queropassagem.stops                                 // Lista de paradas
queropassagem.stop.{stopId}                         // Parada específica
queropassagem.company.{companyId}                   // Companhia específica
queropassagem.trips.{from}.{to}.{travelDate}       // Viagens
queropassagem.seats.{travelId}                      // Assentos
```

### Reutilização Inteligente

**Exemplo:** Ao acessar `/trips/ABC123/seats?from=X&to=Y&data=Z`:
1. Busca assentos (nova requisição - cache 1min)
2. Busca viagem `findTripById()` → usa cache de `searchTrips()` (5min)
3. **Não faz nova requisição!** Reutiliza dados já em cache

---

## Boas Práticas Aplicadas

### Backend

✅ **SRP:** Cada classe tem uma responsabilidade
✅ **Thin Controllers:** Apenas roteamento
✅ **Fat Service:** Lógica centralizada
✅ **Form Requests:** Validação isolada
✅ **Cache Inteligente:** TTLs otimizados
✅ **Error Handling:** Try-catch em todos os métodos
✅ **Logging:** Log de erros da API
✅ **Type Hints:** Todos os métodos tipados

### Frontend

✅ **Composition API:** Código mais organizado
✅ **Component Reusability:** CityAutocomplete reutilizável
✅ **Computed Properties:** Lógica derivada otimizada
✅ **Lazy Loading:** Logos carregadas sob demanda
✅ **Client-side Filtering:** Performance melhorada
✅ **Responsive Design:** Mobile-friendly
✅ **Loading States:** Feedback visual ao usuário
✅ **Error Handling:** Mensagens amigáveis

---

## URLs Stateful (Query Params)

### Problema Resolvido

Antes: `POST /trips/select` com dados no body
- ❌ F5 quebra a página
- ❌ Não pode compartilhar URL
- ❌ Botão voltar não funciona
- ❌ Sem histórico navegável

Depois: `GET /trips/{id}/seats?from=X&to=Y&data=Z`
- ✅ F5 funciona perfeitamente
- ✅ URL compartilhável
- ✅ Botão voltar/avançar funciona
- ✅ Histórico completo no browser

### Como Funciona

1. Busca inicial: `POST /search` (dados sensíveis)
2. Backend redireciona: `GET /trips?from=X&to=Y&data=Z`
3. Seleção de viagem: `GET /trips/{id}/seats?from=X&to=Y&data=Z`
4. Backend reconstrói contexto usando query params + cache

**Benefício:** Estado preservado na URL, mas dados completos no backend (cache)
