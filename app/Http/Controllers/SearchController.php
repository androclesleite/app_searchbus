<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchTripRequest;
use App\Services\QueroPassagemService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private QueroPassagemService $queroPassagemService
    ) {}

    // Exibe a página inicial de busca
    public function index(): Response
    {
        return Inertia::render('Search');
    }

    // Retorna todas as paradas para o autocomplete
    public function getStops()
    {
        try {
            $stops = $this->queroPassagemService->getStops();

            $formattedStops = collect($stops)->map(fn($stop) => [
                'id' => $stop['id'],
                'name' => $stop['name'],
                'type' => $stop['type'],
                'url' => $stop['url'] ?? null,
                'substops' => $stop['substops'] ?? []
            ]);

            return response()->json($formattedStops);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao carregar cidades',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Valida se uma parada é de SP ou PR
    public function validateStop(Request $request)
    {
        $request->validate(['stopId' => 'required|string']);

        try {
            $stop = $this->queroPassagemService->getStop($request->stopId);
            $state = $stop['state'] ?? null;

            return response()->json([
                'allowed' => in_array($state, ['SP', 'PR']),
                'state' => $state,
                'name' => $stop['displayName'] ?? $stop['name']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao validar parada',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Processa busca e redireciona para resultados com query params
    public function search(SearchTripRequest $request)
    {
        // Valida estados SP/PR
        if (!$this->queroPassagemService->validateStopState($request->from)) {
            return back()->withErrors(['from' => 'Origem deve ser uma cidade de SP ou PR']);
        }

        if (!$this->queroPassagemService->validateStopState($request->to)) {
            return back()->withErrors(['to' => 'Destino deve ser uma cidade de SP ou PR']);
        }

        // Redireciona para rota GET com query params
        return redirect()->route('trips.index', [
            'from' => $request->from,
            'to' => $request->to,
            'data' => $request->data
        ]);
    }
}