<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchTripRequest;
use App\Http\Requests\SelectSeatRequest;
use App\Services\QueroPassagemService;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function __construct(
        private QueroPassagemService $queroPassagemService
    ) {}

    // Lista viagens disponíveis com base nos parâmetros de busca
    public function index(SearchTripRequest $request): Response
    {
        try {
            $result = $this->queroPassagemService->searchTripsWithValidation(
                $request->from,
                $request->to,
                $request->data
            );

            return Inertia::render('Trips', [
                'trips' => $result['trips'],
                'searchParams' => $result['searchParams'],
                'cacheInfo' => [
                    'shouldCache' => true,
                    'from' => $request->from,
                    'to' => $request->to,
                    'date' => $request->data
                ]
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Trips', [
                'trips' => [],
                'searchParams' => [
                    'from' => ['id' => $request->from],
                    'to' => ['id' => $request->to],
                    'travelDate' => $request->data
                ],
                'error' => 'Erro ao buscar viagens: ' . $e->getMessage(),
                'cacheInfo' => ['shouldCache' => false]
            ]);
        }
    }

    // Exibe assentos disponíveis da viagem selecionada
    public function show(string $tripId, SelectSeatRequest $request)
    {
        try {
            // Busca assentos disponíveis
            $seats = $this->queroPassagemService->getSeats($tripId);

            // Busca dados completos da viagem
            $trip = null;
            if ($request->from && $request->to && $request->data) {
                $trip = $this->queroPassagemService->findTripById(
                    $request->from,
                    $request->to,
                    $request->data,
                    $tripId
                );
            }

            // Se não encontrou a viagem, retorna erro
            if (!$trip) {
                return back()->with('error', 'Viagem não encontrada');
            }

            return Inertia::render('Seats', [
                'seats' => $seats,
                'trip' => $trip,
                'searchParams' => [
                    'from' => $request->from,
                    'to' => $request->to,
                    'data' => $request->data
                ]
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao carregar assentos: ' . $e->getMessage());
        }
    }

    // Retorna informações de uma companhia específica
    public function getCompany(int $companyId)
    {
        try {
            $company = $this->queroPassagemService->getCompany($companyId);
            return response()->json($company);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar informações da companhia',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}