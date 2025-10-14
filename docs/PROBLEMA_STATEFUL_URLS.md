# Problema: URLs Stateful vs Dados Complexos

## O Problema Encontrado

### Contexto

Quando refatoramos para usar URLs stateful (com query params), nos deparamos com um dilema arquitetural:

**Página de Assentos precisa de:**
1. Matriz de assentos (`seats`) - buscar da API
2. Dados completos da viagem (`trip`) - objeto complexo com:
   - Companhia (nome, logo)
   - Horários (partida, chegada)
   - Preços (passagem, taxas)
   - Limite de passageiros
   - Classe do ônibus
   - Origem/destino completos

### Arquitetura Antiga (POST com body)

```javascript
// Frontend
router.post('/trips/select', {
  travelId: 'ABC123',
  tripData: { ...objetoComplexo }  // Tudo no body
})

// Backend
public function selectTrip(Request $request)
{
  $seats = getSeats($request->travelId);
  $trip = $request->tripData;  // Dados vêm do frontend!

  return Inertia::render('Seats', [
    'seats' => $seats,
    'trip' => $trip
  ]);
}
```

**✅ Vantagem:** Simples, dados já estão no request

**❌ Problemas:**
- F5 quebra (POST não pode ser refeito)
- Não pode compartilhar URL
- Botão voltar não funciona
- Sem histórico navegável
- Dados trafegam desnecessariamente do servidor → cliente → servidor

---

### Arquitetura Nova (GET com query params)

Queremos:
```
GET /trips/ABC123/seats?from=parada-x&to=parada-y&data=2025-01-15
```

**✅ Vantagens:**
- F5 funciona
- URL compartilhável
- Navegação completa (voltar/avançar)
- RESTful

**❌ Problema:**
Como passar o objeto complexo `trip` sem incluir tudo na URL?

---

## Opções Avaliadas

### 1️⃣ Passar trip serializado na URL

```
GET /trips/ABC123/seats?tripData={"company":{"name":"Expresso"...}}
```

**❌ RUIM:**
- URL gigantesca e feia
- Limite de tamanho de URL (navegadores ~2000 chars)
- Dados duplicados (id + objeto completo)
- Segurança (dados expostos na URL)
- Encoding complexo (JSON → URL encode)

**Veredicto:** GAMBIARRA TOTAL ❌

---

### 2️⃣ Armazenar trip na sessão

```php
// Ao listar viagens
Session::put("trip.{$tripId}", $tripData);

// Ao acessar assentos
$trip = Session::get("trip.{$tripId}");
```

**⚠️ PROBLEMAS:**
- Sessão cresce indefinidamente
- Lixo acumulado (trips antigas nunca acessadas)
- Compartilhar URL não funciona (sessão diferente)
- Múltiplas abas confundem (mesma sessão)
- Precisa limpar sessão manualmente

**Veredicto:** Gambiarra menor, mas ainda problemática ⚠️

---

### 3️⃣ Endpoint separado para buscar trip

```php
GET /api/trips/{tripId}  // Novo endpoint
```

**❌ PROBLEMAS:**
- API Quero Passagem NÃO tem esse endpoint
- Teria que fazer nova busca completa (`/new/search`)
- Cache separado ineficiente
- Lógica duplicada

**Veredicto:** Não é possível (API não suporta) ❌

---

### 4️⃣ **Buscar trip do cache de viagens (SOLUÇÃO ESCOLHIDA)** ✅

```php
// Query params: from, to, data
public function show(string $tripId, SelectSeatRequest $request)
{
  // 1. Busca assentos
  $seats = getSeats($tripId);

  // 2. Busca trip do cache de viagens
  $trip = findTripById(
    $request->from,
    $request->to,
    $request->data,
    $tripId
  );

  return Inertia::render('Seats', [...]);
}
```

**Como `findTripById()` funciona:**

```php
public function findTripById($from, $to, $date, $tripId)
{
  // Busca TODAS as viagens (USA CACHE DE 5 MIN!)
  $trips = $this->searchTrips($from, $to, $date);

  // Procura trip com id correspondente
  foreach ($trips as $trip) {
    if ($trip['id'] === $tripId) {
      return $trip;  // Encontrou!
    }
  }

  return null;  // Não encontrou
}
```

---

## Por Que Esta Solução NÃO é Gambiarra?

### ✅ Reutiliza Cache Existente

Cache de viagens já existe:
```
Key: queropassagem.trips.{from}.{to}.{date}
TTL: 5 minutos
```

Quando usuário acessa `/trips/ABC123/seats?from=X&to=Y&data=Z`:
1. Sistema busca viagens do cache (NÃO FAZ REQUEST À API!)
2. Itera e encontra trip com `id === 'ABC123'`
3. Retorna dados completos

**Performance:** Operação em memória, milissegundos!

---

### ✅ URLs Contêm Apenas IDs

```
/trips/ABC123/seats?from=parada-x&to=parada-y&data=2025-01-15
```

- IDs pequenos
- URL limpa e legível
- Compartilhável
- RESTful

---

### ✅ Single Source of Truth

Dados vêm da API (via cache), não do frontend.

**Fluxo correto:**
```
API → Backend Cache → Backend reconstrói → Frontend
```

**Fluxo ruim (anterior):**
```
API → Backend → Frontend → Backend (dados de volta)
```

---

### ✅ Funciona em Todos os Cenários

**1. Navegação Normal:**
- Usuário lista viagens
- Cache é populado
- Acessa assentos → usa cache

**2. URL Compartilhada:**
- Alguém acessa URL direto
- Cache vazio? Faz request à API
- Popula cache
- Encontra trip

**3. F5 na Página:**
- Cache ainda válido (5 min)
- Usa cache
- Sem nova request

**4. Cache Expirou:**
- Faz nova busca
- Repopula cache
- Encontra trip

---

## Comparação: Com vs Sem Gambiarra

### ❌ Gambiarra (Sessão)

```php
// Lista viagens
Session::put("trip.{$id}", $tripData);

// Assentos
$trip = Session::get("trip.{$id}");

// Problemas:
// - Sessão incha
// - URL compartilhada não funciona
// - Múltiplas abas confundem
// - Precisa limpar manualmente
```

### ✅ Solução Limpa (Cache + Query Params)

```php
// Lista viagens
Cache::remember("trips.{from}.{to}.{date}", 300, function() {
  return $api->searchTrips(...);
});

// Assentos
$trips = Cache::get("trips.{from}.{to}.{date}");
$trip = array_find($trips, fn($t) => $t['id'] === $tripId);

// Benefícios:
// - Cache compartilhado (múltiplas requests)
// - URL funciona sempre
// - Expira automaticamente (5 min)
// - Performance ótima
```

---

## Diagrama do Fluxo

```
[Usuário acessa /trips?from=X&to=Y&data=Z]
          ↓
[TripController->index()]
          ↓
[searchTrips(X, Y, Z)]
          ↓
[Cache Check] → Cache HIT?
    ↓ SIM              ↓ NÃO
[Retorna cache]   [API Request]
                       ↓
                  [Salva cache 5min]
          ↓
[Renderiza Trips.vue com array de viagens]
          ↓
[Usuário clica em viagem ABC123]
          ↓
[router.get('/trips/ABC123/seats?from=X&to=Y&data=Z')]
          ↓
[TripController->show(ABC123)]
          ↓
[findTripById(X, Y, Z, ABC123)]
          ↓
[searchTrips(X, Y, Z)] → REUTILIZA CACHE!
          ↓
[Itera array procurando id === ABC123]
          ↓
[Encontra trip completo]
          ↓
[Renderiza Seats.vue]
```

**Chave:** `searchTrips()` usa cache. Quando `findTripById()` chama `searchTrips()`, não faz request à API!

---

## Outras Linguagens/Frameworks

### Ruby on Rails

```ruby
# routes.rb
get '/trips/:id/seats', to: 'trips#show_seats'

# trips_controller.rb
def show_seats
  trip = Rails.cache.fetch("trips/#{params[:from]}/#{params[:to]}/#{params[:date]}") do
    api.search_trips(params[:from], params[:to], params[:date])
  end.find { |t| t['id'] == params[:id] }

  render inertia: 'Seats', props: { trip: trip }
end
```

Mesmo padrão!

---

### Node.js + Express

```javascript
const cache = require('node-cache');
const tripCache = new cache({ stdTTL: 300 });

app.get('/trips/:id/seats', async (req, res) => {
  const cacheKey = `trips:${req.query.from}:${req.query.to}:${req.query.date}`;

  let trips = tripCache.get(cacheKey);
  if (!trips) {
    trips = await api.searchTrips(req.query.from, req.query.to, req.query.date);
    tripCache.set(cacheKey, trips);
  }

  const trip = trips.find(t => t.id === req.params.id);

  res.render('Seats', { trip });
});
```

Padrão universal!

---

### Python + Django

```python
from django.core.cache import cache

def show_seats(request, trip_id):
    cache_key = f"trips:{request.GET['from']}:{request.GET['to']}:{request.GET['date']}"

    trips = cache.get(cache_key)
    if not trips:
        trips = api.search_trips(
            request.GET['from'],
            request.GET['to'],
            request.GET['date']
        )
        cache.set(cache_key, trips, timeout=300)

    trip = next((t for t in trips if t['id'] == trip_id), None)

    return render(request, 'Seats', {'trip': trip})
```

Mesma lógica em todas as stacks!

---

## Conclusão

### Por Que NÃO é Gambiarra?

1. **Padrão Estabelecido:** Cache-aside pattern (conhecido e testado)
2. **Performance:** Não faz requests desnecessários
3. **Escalável:** Cache compartilhado entre requests
4. **Manutenível:** Lógica clara e isolada
5. **RESTful:** URLs limpas e semânticas
6. **Stateless:** Não depende de sessão
7. **Testável:** Fácil mockar cache nos testes

### O Que SERIA Gambiarra?

- ❌ Passar JSON gigante na URL
- ❌ Sessão que nunca expira
- ❌ Duplicar requests à API
- ❌ Dados inconsistentes (frontend vs backend)
- ❌ Lógica espalhada em múltiplos lugares

### Solução Adotada

✅ **Cache inteligente + Query params mínimos + Reutilização de dados**

É assim que aplicações profissionais resolvem esse problema!
