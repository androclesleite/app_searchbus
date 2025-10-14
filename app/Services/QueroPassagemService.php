<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QueroPassagemService
{
    private Client $client;
    private string $affiliateCode;

    // TTLs de cache otimizados
    private const CACHE_STOPS = 3600; // 1 hora
    private const CACHE_STOP = 3600; // 1 hora
    private const CACHE_COMPANY = 86400; // 24 horas
    private const CACHE_TRIPS = 300; // 5 minutos
    private const CACHE_SEATS = 60; // 1 minuto

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.queropassagem.base_uri'),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'auth' => [
                config('services.queropassagem.username'),
                config('services.queropassagem.password')
            ],
            'timeout' => 30,
        ]);

        $this->affiliateCode = config('services.queropassagem.affiliate_code');
    }

    // Lista todas as paradas com cache
    public function getStops(): array
    {
        return Cache::remember('queropassagem.stops', self::CACHE_STOPS, function () {
            try {
                $response = $this->client->get('stops');
                return json_decode($response->getBody()->getContents(), true);
            } catch (GuzzleException $e) {
                Log::error('Erro ao buscar paradas: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    // Busca detalhes de uma parada específica com cache
    public function getStop(string $stopId): array
    {
        return Cache::remember("queropassagem.stop.{$stopId}", self::CACHE_STOP, function () use ($stopId) {
            try {
                $response = $this->client->get("stops/{$stopId}");
                return json_decode($response->getBody()->getContents(), true);
            } catch (GuzzleException $e) {
                Log::error("Erro ao buscar parada {$stopId}: " . $e->getMessage());
                throw $e;
            }
        });
    }

    // Busca viagens disponíveis com cache
    public function searchTrips(string $from, string $to, string $travelDate): array
    {
        $cacheKey = "queropassagem.trips.{$from}.{$to}.{$travelDate}";

        return Cache::remember($cacheKey, self::CACHE_TRIPS, function () use ($from, $to, $travelDate) {
            try {
                $response = $this->client->post('new/search', [
                    'json' => [
                        'from' => $from,
                        'to' => $to,
                        'travelDate' => $travelDate,
                        'affiliateCode' => $this->affiliateCode,
                        'include-connections' => false
                    ]
                ]);

                return json_decode($response->getBody()->getContents(), true);
            } catch (GuzzleException $e) {
                Log::error('Erro ao buscar viagens: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    // Busca assentos disponíveis com cache curto
    public function getSeats(string $travelId, string $orientation = 'horizontal'): array
    {
        $cacheKey = "queropassagem.seats.{$travelId}";

        return Cache::remember($cacheKey, self::CACHE_SEATS, function () use ($travelId, $orientation) {
            try {
                $response = $this->client->post('new/seats', [
                    'json' => [
                        'travelId' => $travelId,
                        'orientation' => $orientation,
                        'type' => 'matrix'
                    ]
                ]);

                return json_decode($response->getBody()->getContents(), true);
            } catch (GuzzleException $e) {
                Log::error('Erro ao buscar assentos: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Lista todas as companhias
     */
    public function getCompanies(): array
    {
        try {
            $response = $this->client->get('companies');
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Erro ao buscar companhias: ' . $e->getMessage());
            throw $e;
        }
    }

    // Busca detalhes de uma companhia específica com cache longo
    public function getCompany(int $companyId): array
    {
        return Cache::remember("queropassagem.company.{$companyId}", self::CACHE_COMPANY, function () use ($companyId) {
            try {
                $response = $this->client->get("companies/{$companyId}");
                return json_decode($response->getBody()->getContents(), true);
            } catch (GuzzleException $e) {
                Log::error("Erro ao buscar companhia {$companyId}: " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Cria uma reserva (booking)
     */
    public function createBooking(array $bookingData): array
    {
        try {
            $response = $this->client->post('new/booking', [
                'json' => array_merge($bookingData, [
                    'affiliateCode' => $this->affiliateCode
                ])
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Erro ao criar reserva: ' . $e->getMessage());
            throw $e;
        }
    }

    // Valida se a parada é de SP ou PR
    public function validateStopState(string $stopId): bool
    {
        try {
            $stop = $this->getStop($stopId);
            return in_array($stop['state'] ?? '', ['SP', 'PR']);
        } catch (\Exception $e) {
            return false;
        }
    }

    // Busca viagens com validação completa (estados + ordenação)
    public function searchTripsWithValidation(string $from, string $to, string $travelDate): array
    {
        // Busca paradas para obter informações completas
        $fromStop = $this->getStop($from);
        $toStop = $this->getStop($to);

        // Busca viagens
        $trips = $this->searchTrips($from, $to, $travelDate);

        // Ordena por horário de embarque
        $sortedTrips = collect($trips)->sortBy(function ($trip) {
            return $trip['departure']['time'];
        })->values()->all();

        return [
            'trips' => $sortedTrips,
            'searchParams' => [
                'from' => $fromStop,
                'to' => $toStop,
                'travelDate' => $travelDate
            ]
        ];
    }

    // Busca uma viagem específica por ID dentro de um conjunto de viagens
    public function findTripById(string $from, string $to, string $travelDate, string $tripId): ?array
    {
        $trips = $this->searchTrips($from, $to, $travelDate);

        foreach ($trips as $trip) {
            if ($trip['id'] === $tripId) {
                return $trip;
            }
        }

        return null;
    }
}