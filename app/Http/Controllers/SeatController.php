<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmBookingRequest;
use App\Services\QueroPassagemService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeatController extends Controller
{
    public function __construct(
        private QueroPassagemService $queroPassagemService
    ) {}

    // Confirma seleção de assentos e exibe página de confirmação
    public function store(ConfirmBookingRequest $request)
    {
        try {
            return Inertia::render('Confirmation', [
                'success' => true,
                'message' => 'Assentos selecionados com sucesso!',
                'selectedSeats' => $request->selectedSeats,
                'trip' => $request->tripData,
                'travelId' => $request->travelId
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao confirmar seleção: ' . $e->getMessage());
        }
    }

    // Cria reserva na API (opcional - para implementação completa)
    public function createBooking(Request $request)
    {
        $validated = $request->validate([
            'travelId' => 'required|string',
            'passengers' => 'required|array|min:1',
            'passengers.*.name' => 'required|string',
            'passengers.*.travelDocument' => 'required|string',
            'passengers.*.travelDocumentType' => 'required|string',
            'passengers.*.seatNumber' => 'required|string',
            'passengers.*.birthDate' => 'required|date'
        ]);

        try {
            $bookingData = [
                'travels' => [
                    [
                        'travelId' => $validated['travelId'],
                        'passengers' => $validated['passengers']
                    ]
                ]
            ];

            $booking = $this->queroPassagemService->createBooking($bookingData);

            return response()->json([
                'success' => true,
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao criar reserva',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}