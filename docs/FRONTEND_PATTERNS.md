# Padrões de Arquitetura Frontend: React vs Vue.js

## Sua Pergunta

> "Reparei que no front você não usou Context, ou Services API e etc, por quê? Pois geralmente uso no React. Não sei se é o correto trabalhar dessa forma com Vue, acho que é também?"

**Resposta curta:** Depende do framework e do **tipo de aplicação**!

---

## React vs Vue.js: Diferenças Fundamentais

### React (SPA Tradicional)

```
React App (client-side)
    ↓
Gerencia TODO o estado
    ↓
Faz requests diretas para API
    ↓
Lógica de negócio no frontend
```

**Necessita:**
- ✅ Context API (estado global)
- ✅ Services/API layer (centralizar requests)
- ✅ Custom hooks (reutilizar lógica)
- ✅ Estado complexo (Redux, Zustand, etc)

---

### Vue.js + Inertia.js (SSR/Hybrid)

```
Inertia App (hybrid)
    ↓
Backend gerencia rotas e estado
    ↓
Frontend só renderiza
    ↓
Lógica de negócio no backend
```

**Não necessita (na maioria dos casos):**
- ❌ Context API (props do Inertia já são globais)
- ❌ Services/API layer (backend já faz isso)
- ❌ Estado global complexo (stateless components)

---

## Explicação Detalhada

### 1. Context API / Estado Global

#### React (SPA Tradicional)

```jsx
// AuthContext.js
const AuthContext = createContext();

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Busca usuário da API
    api.get('/me').then(setUser);
  }, []);

  return (
    <AuthContext.Provider value={{ user, loading }}>
      {children}
    </AuthContext.Provider>
  );
}

// App.js
<AuthProvider>
  <Routes>
    <Route path="/dashboard" element={<Dashboard />} />
  </Routes>
</AuthProvider>

// Dashboard.js
function Dashboard() {
  const { user } = useContext(AuthContext);  // ✅ Necessário!
  return <h1>Olá, {user.name}</h1>;
}
```

**Por quê?** React SPA não tem backend "conectado". Você precisa:
- Buscar dados da API manualmente
- Armazenar em Context para compartilhar
- Gerenciar loading states
- Lidar com erros

---

#### Vue.js + Inertia.js

```vue
<!-- Dashboard.vue -->
<script setup>
// Props vêm do BACKEND automaticamente!
const props = defineProps({
  user: Object  // ✅ Já vem populado!
});
</script>

<template>
  <h1>Olá, {{ user.name }}</h1>
</template>
```

```php
// Backend (Controller)
return Inertia::render('Dashboard', [
  'user' => auth()->user()  // ✅ Backend envia dados!
]);
```

**Por quê?** Inertia conecta backend e frontend:
- Backend busca dados
- Backend passa via props
- Frontend só renderiza
- **Não precisa Context!**

---

### 2. Services / API Layer

#### React (SPA Tradicional)

```javascript
// services/api.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://api.example.com'
});

export const tripService = {
  search: (from, to, date) =>
    api.post('/trips/search', { from, to, date }),

  getSeats: (tripId) =>
    api.get(`/trips/${tripId}/seats`),

  confirmBooking: (data) =>
    api.post('/bookings', data)
};

// Component
import { tripService } from '@/services/api';

function SearchPage() {
  const [trips, setTrips] = useState([]);
  const [loading, setLoading] = useState(false);

  const handleSearch = async (from, to, date) => {
    setLoading(true);
    try {
      const response = await tripService.search(from, to, date);
      setTrips(response.data);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  return (
    // JSX...
  );
}
```

**Por quê precisa?**
- ✅ Centraliza requests
- ✅ Evita duplicação de código
- ✅ Fácil mockar em testes
- ✅ Interceptors (auth, errors)
- ✅ TypeScript typing

---

#### Vue.js + Inertia.js

```vue
<!-- Search.vue -->
<script setup>
import { router } from '@inertiajs/vue3';

const handleSearch = () => {
  // ✅ Inertia router faz tudo!
  router.post('/search', {
    from: form.from,
    to: form.to,
    data: form.data
  });
};
</script>
```

```php
// Backend faz o request à API
class SearchController {
  public function search(SearchTripRequest $request) {
    $trips = $this->queroPassagemService->searchTrips(...);
    return Inertia::render('Trips', ['trips' => $trips]);
  }
}
```

**Por quê não precisa?**
- ❌ Backend já tem `QueroPassagemService` (Service Layer!)
- ❌ Frontend só usa `router.post()` / `router.get()`
- ❌ Lógica de API está no backend (onde deve estar)
- ❌ Não faz requests diretas à API externa

---

### 3. Custom Hooks / Composables

#### React (SPA) - NECESSÁRIO

```javascript
// hooks/useTrips.js
export function useTrips() {
  const [trips, setTrips] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const searchTrips = async (from, to, date) => {
    setLoading(true);
    setError(null);
    try {
      const data = await tripService.search(from, to, date);
      setTrips(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return { trips, loading, error, searchTrips };
}

// Component
function SearchPage() {
  const { trips, loading, error, searchTrips } = useTrips();

  return (
    // JSX...
  );
}
```

**Benefícios:**
- ✅ Reutiliza lógica entre componentes
- ✅ Separa lógica de apresentação
- ✅ Testável isoladamente

---

#### Vue.js + Inertia.js - OPCIONAL

```javascript
// composables/useFilters.js
export function useFilters(trips) {
  const selectedPeriod = ref(null);
  const sortBy = ref('time');

  const filteredTrips = computed(() => {
    let filtered = [...trips];

    if (selectedPeriod.value) {
      filtered = filtered.filter(/* ... */);
    }

    filtered.sort(/* ... */);

    return filtered;
  });

  return { selectedPeriod, sortBy, filteredTrips };
}

// Component
const props = defineProps({ trips: Array });
const { filteredTrips, selectedPeriod, sortBy } = useFilters(props.trips);
```

**Quando usar?**
- ✅ Lógica reutilizável (filtros, formatação)
- ✅ Lógica complexa (separar do componente)
- ❌ Não precisa para requests API (backend faz)

---

## Quando Usar Services no Frontend (Vue/Inertia)?

### ✅ SIM - Quando faz sentido

#### 1. APIs Client-side (não passam pelo backend)

```javascript
// services/analytics.js
export const analytics = {
  trackPageView: (page) => {
    gtag('event', 'page_view', { page });
  },

  trackClick: (element) => {
    gtag('event', 'click', { element });
  }
};

// Component
import { analytics } from '@/services/analytics';

onMounted(() => {
  analytics.trackPageView(route.current);
});
```

**Motivo:** Analytics não precisa passar pelo backend

---

#### 2. APIs Públicas (browser-only)

```javascript
// services/geolocation.js
export const geolocation = {
  getCurrentPosition: () => {
    return new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject);
    });
  }
};
```

**Motivo:** Geolocation API é do navegador

---

#### 3. APIs de Terceiros (CORS permitido)

```javascript
// services/maps.js
export const mapsService = {
  loadMap: (lat, lng) => {
    return google.maps.Map(/* ... */);
  }
};
```

**Motivo:** Google Maps pode ser chamado direto do frontend

---

### ❌ NÃO - Quando não faz sentido

#### 1. APIs Backend (Laravel/PHP)

```javascript
// ❌ NÃO FAÇA ISSO
const api = axios.create({
  baseURL: '/api'
});

export const tripService = {
  search: (data) => api.post('/trips/search', data)
};
```

**Por quê não?**
- Backend já tem Service Layer (`QueroPassagemService`)
- Lógica duplicada (frontend e backend)
- Violação de responsabilidade (frontend fazendo lógica de negócio)
- Inertia router já gerencia isso

**Faça assim:**
```javascript
// ✅ USE INERTIA ROUTER
router.post('/search', data);  // Backend processa tudo
```

---

## Comparação: React SPA vs Vue Inertia

### Arquitetura React (SPA)

```
[Frontend React] ←────────────→ [API REST]
     ↓                              ↓
- Context API                  - Controllers
- Services Layer               - Services
- Estado Global                - Database
- Lógica de Negócio            - Validação
- Validação
- Cache (React Query)
```

**Frontend faz TUDO:**
- Gerencia estado
- Valida dados
- Faz requests
- Cache
- Routing

---

### Arquitetura Vue + Inertia

```
[Frontend Vue] ←─Inertia─→ [Backend Laravel]
     ↓                          ↓
- Só renderiza            - Controllers (thin)
- Filtros client          - Services (fat)
- Formatação              - Validação (Form Requests)
                          - Cache
                          - API External
                          - Database
```

**Backend faz TUDO:**
- Gerencia estado
- Valida dados
- Faz requests à API externa
- Cache
- Routing

**Frontend só:**
- Renderiza
- Interações locais (filtros, ordenação)
- Formatação de exibição

---

## Quando Usar Cada Padrão?

### Use React SPA (com Context + Services)

✅ **Quando:**
- App 100% client-side
- API REST separada
- Múltiplos clients (web, mobile)
- Precisa funcionar offline
- Real-time (WebSockets)
- Dashboards complexos
- App type (Gmail, Trello, Figma)

**Exemplos:**
- Gmail
- Trello
- Notion
- Discord
- Spotify Web

---

### Use Vue/Inertia (sem Context + Services)

✅ **Quando:**
- App server-side rendered
- Backend e frontend integrados
- SEO importante
- Não precisa API pública
- Páginas tradicionais
- CRUDs

**Exemplos:**
- E-commerce
- CMS
- Admin panels
- Sistemas internos
- Nosso SearchBus!

---

## Nosso Projeto: Por Que Não Usamos?

### Context API - ❌ Não precisamos

```
Backend já gerencia estado:
  ↓
Props do Inertia são automaticamente disponíveis
  ↓
Não precisa Context para compartilhar dados
```

**Se precisássemos:**
```vue
<!-- ❌ Desnecessário -->
<AuthProvider>
  <Search />
</AuthProvider>

<!-- ✅ Backend já faz -->
// Controller
return Inertia::render('Search', [
  'auth' => auth()->user()  // Disponível em toda página
]);
```

---

### Services API - ❌ Não precisamos

```
Backend tem QueroPassagemService:
  ↓
Toda lógica de API está lá
  ↓
Frontend só usa router.post()/get()
  ↓
Não faz requests diretas à API externa
```

**Se precisássemos:**
```javascript
// ❌ Desnecessário (backend já faz)
const tripService = {
  search: (data) => axios.post('/api/queropassagem/search', data)
};

// ✅ Backend Service já existe
class QueroPassagemService {
  public function searchTrips() { /* ... */ }
}
```

---

## Quando DEVERIAMOS Usar no Nosso Projeto?

### Cenário 1: Adicionar Chat Real-time

```javascript
// ✅ FARIA SENTIDO
// services/chat.js
export const chatService = {
  connect: () => {
    const socket = io(process.env.VITE_CHAT_URL);
    return socket;
  },

  sendMessage: (socket, message) => {
    socket.emit('message', message);
  }
};

// composables/useChat.js
export function useChat() {
  const socket = ref(null);
  const messages = ref([]);

  onMounted(() => {
    socket.value = chatService.connect();
    socket.value.on('message', (msg) => {
      messages.value.push(msg);
    });
  });

  return { messages, sendMessage: chatService.sendMessage };
}
```

**Por quê?** WebSocket é client-side, não passa pelo Laravel routing

---

### Cenário 2: Tema Dark Mode (Context)

```javascript
// ✅ FARIA SENTIDO
// composables/useTheme.js
const theme = ref(localStorage.getItem('theme') || 'light');

export function useTheme() {
  const setTheme = (newTheme) => {
    theme.value = newTheme;
    localStorage.setItem('theme', newTheme);
    document.documentElement.classList.toggle('dark', newTheme === 'dark');
  };

  return { theme: readonly(theme), setTheme };
}

// Qualquer componente
const { theme, setTheme } = useTheme();
```

**Por quê?** Estado UI global (não vem do backend)

---

### Cenário 3: Formatação de Dados (Utility)

```javascript
// ✅ JÁ USAMOS (mas não é Service, é utility)
// utils/formatters.js
export const formatPrice = (price) => {
  return price.toFixed(2).replace('.', ',');
};

export const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('pt-BR');
};

// Component
import { formatPrice } from '@/utils/formatters';

const totalFormatted = formatPrice(total);
```

**Por quê?** Lógica de apresentação, não de negócio

---

## Refatoração: Se Fosse React SPA

Veja como ficaria se fosse React tradicional:

### Context

```javascript
// contexts/TripContext.jsx
const TripContext = createContext();

export function TripProvider({ children }) {
  const [trips, setTrips] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedTrip, setSelectedTrip] = useState(null);

  return (
    <TripContext.Provider value={{ trips, loading, selectedTrip, setTrips, setLoading, setSelectedTrip }}>
      {children}
    </TripContext.Provider>
  );
}
```

---

### Service

```javascript
// services/tripService.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://api.queropassagem.com.br/v1',
  auth: {
    username: process.env.VITE_QP_USERNAME,
    password: process.env.VITE_QP_PASSWORD
  }
});

export const tripService = {
  searchTrips: async (from, to, date) => {
    const response = await api.post('/new/search', {
      from, to, travelDate: date,
      affiliateCode: process.env.VITE_QP_AFFILIATE
    });
    return response.data;
  },

  getSeats: async (travelId) => {
    const response = await api.post('/new/seats', {
      travelId,
      orientation: 'horizontal',
      type: 'matrix'
    });
    return response.data;
  }
};
```

---

### Custom Hook

```javascript
// hooks/useTrips.js
export function useTrips() {
  const { trips, setTrips, loading, setLoading } = useContext(TripContext);

  const searchTrips = async (from, to, date) => {
    setLoading(true);
    try {
      const data = await tripService.searchTrips(from, to, date);
      setTrips(data);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  return { trips, loading, searchTrips };
}
```

---

### Component

```jsx
// pages/Search.jsx
function SearchPage() {
  const { searchTrips, loading } = useTrips();
  const [form, setForm] = useState({ from: '', to: '', date: '' });

  const handleSubmit = async (e) => {
    e.preventDefault();
    await searchTrips(form.from, form.to, form.date);
    navigate('/trips');
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* form fields */}
    </form>
  );
}
```

**Comparação de linhas de código:**

| Abordagem | Linhas de Código |
|-----------|------------------|
| React SPA | ~300 linhas (Context + Service + Hook + Component) |
| Vue Inertia | ~150 linhas (Component + Backend faz resto) |

---

## Conclusão

### React SPA

✅ **Use Context + Services quando:**
- App client-side
- Frontend faz requests diretas
- Precisa estado global
- Lógica de negócio no frontend

---

### Vue + Inertia

❌ **NÃO use Context + Services para:**
- Requests que passam pelo backend
- Estado que vem do servidor
- Lógica que o backend já faz

✅ **Use Context + Services quando:**
- Client-only (analytics, theme)
- WebSockets / Real-time
- APIs públicas (maps, geolocation)
- Utilities (formatação, validação client)

---

## Resumo para Você

**Sua pergunta:** "Por que não usou Context/Services?"

**Resposta:**

1. **Inertia é diferente de React SPA**
   - Backend gerencia estado
   - Props automáticas
   - Não precisa Context

2. **Backend já tem Services**
   - `QueroPassagemService`
   - Toda lógica de API lá
   - Frontend não faz requests diretas

3. **KISS (Keep It Simple, Stupid)**
   - Não adicione complexidade desnecessária
   - Use ferramentas quando fazem sentido
   - Vue/Inertia já resolve o problema

4. **Quando usar?**
   - Client-only features
   - Real-time
   - UI state (theme, locale)
   - Não para API backend!

---

**Você está certo em usar no React!** Mas no Vue + Inertia, o padrão é diferente (e mais simples) 🎯
