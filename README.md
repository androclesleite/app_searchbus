# SearchBus - Sistema de Busca de Passagens

Sistema de busca e reserva de passagens de ônibus desenvolvido com Laravel 11, Vue.js 3 e Inertia.js.

**Desenvolvido por:** Jefferson Leite

## Tecnologias

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Vue.js 3 (Composition API), Inertia.js
- **Estilização:** Tailwind CSS
- **API:** Quero Passagem API Integration

## Arquitetura

Aplicação desenvolvida seguindo princípios de **Clean Code** e **SOLID**:

- **Form Requests** - Validação isolada (SRP)
- **Thin Controllers** - Apenas roteamento
- **Fat Service** - Lógica de negócio centralizada
- **Cache Inteligente** - TTLs otimizados por tipo de dado
- **URLs Stateful** - Query params para persistência de estado

## Funcionalidades

- Busca de viagens entre SP e PR
- Listagem de viagens com filtros e ordenação
- Seleção visual de assentos
- Cache otimizado para performance
- Interface responsiva e moderna

## Instalação

```bash
# Clone o repositório
git clone <repository-url>
cd app_searchbus

# Instale dependências PHP
composer install

# Instale dependências Node
npm install

# Configure o .env
cp .env.example .env
php artisan key:generate

# Configure as credenciais da API no .env
QUEROPASSAGEM_BASE_URI=https://api.queropassagem.com.br/v1
QUEROPASSAGEM_USERNAME=seu_usuario
QUEROPASSAGEM_PASSWORD=sua_senha
QUEROPASSAGEM_AFFILIATE_CODE=seu_codigo

# Execute as migrations (se houver)
php artisan migrate

# Compile os assets
npm run build

# Inicie o servidor
php artisan serve
```

## Desenvolvimento

```bash
# Frontend (Vite dev server)
npm run dev

# Backend (Laravel server)
php artisan serve
```

Acesse: `http://localhost:8000`

## Estrutura do Projeto

```
app/
├── Http/
│   ├── Controllers/     # Controllers (thin)
│   └── Requests/        # Form Requests (validação)
├── Services/            # Lógica de negócio
resources/
├── js/
│   ├── Pages/          # Views Inertia
│   └── Components/     # Componentes Vue
routes/
└── web.php             # Rotas RESTful
```

## Cache Strategy

- **Viagens:** 5 minutos (dados voláteis)
- **Assentos:** 1 minuto (muito voláteis)
- **Paradas:** 1 hora (estável)
- **Companhias:** 24 horas (muito estável)

## Licença

MIT License
