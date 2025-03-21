<?php

namespace TimBogdanov\Xui;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XuiService
{
    private const LOGIN_COOKIE_NAME = '3x-ui';
    private const BASE_INBOUND_ENDPOINT = 'panel/api/inbounds/';

    protected string $host;
    protected string $port;
    protected string $path;
    protected string $username;
    protected string $password;
    protected string $inboundId;

    public function __construct()
    {
        $this->host      = config('xui.host');
        $this->port      = config('xui.port');
        $this->path      = config('xui.path');
        $this->username  = config('xui.username');
        $this->password  = config('xui.password');
        $this->inboundId = config('xui.inboundId');
    }

    /**
     * Returns the full API URL for a given endpoint.
     */
    private function buildUrl(string $endpoint): string
    {
        return "https://{$this->host}:{$this->port}/{$this->path}/{$endpoint}";
    }

    /**
     * Resolves the inbound ID to use.
     */
    private function resolveInbound(?string $inboundId): string
    {
        return $inboundId ?? $this->inboundId;
    }

    /**
     * Retrieves and caches a valid session cookie.
     */
    private function getSessionCookie(): ?string
    {
        if (Cache::has('xui_session')) {
            return Cache::get('xui_session');
        }

        $response = Http::asForm()->post($this->buildUrl('login'), [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if (!$response->successful() || !$response->json('success')) {
            Log::error('XuiService: Login failed', [
                'status_code' => $response->status(),
                'response'    => $response->json(),
            ]);
            return null;
        }

        foreach ($response->cookies() as $cookie) {
            if ($cookie->getName() === self::LOGIN_COOKIE_NAME) {
                $sessionId = $cookie->getValue();
                Cache::put('xui_session', $sessionId, now()->addHour());
                return $sessionId;
            }
        }

        Log::error('XuiService: Login succeeded but cookie is missing.');
        return null;
    }

    /**
     * Makes a GET or POST request to the x‑ui API.
     */
    private function makeRequest(string $method, string $endpoint, array $payload = []): array
    {
        $sessionCookie = $this->getSessionCookie();
        if (!$sessionCookie) {
            return ['error' => 'Login failed'];
        }

        $url = $this->buildUrl($endpoint);
        $request = Http::withHeaders(['Cookie' => self::LOGIN_COOKIE_NAME . "={$sessionCookie}"]);
        $response = ($method === 'POST')
            ? $request->post($url, $payload)
            : $request->get($url);

        if (!$response->successful()) {
            return [
                'error'   => 'Request failed',
                'status'  => $response->status(),
                'details' => $response->json(),
            ];
        }

        return $response->json();
    }

    /**
     * Retrieve all clients for a given inbound.
     */
    public function getAllClients(?string $inboundId = null): array
    {
        $inbound = $this->resolveInbound($inboundId);
        return $this->makeRequest('GET', self::BASE_INBOUND_ENDPOINT . "get/{$inbound}");
    }

    /**
     * Retrieve a client by their Telegram ID.
     */
    public function getClientByTgId(int $tgId, ?string $inboundId = null)
    {
        $response = $this->getAllClients($inboundId);
        if (isset($response['obj']['settings'])) {
            $settings = json_decode($response['obj']['settings'], true);
            if (isset($settings['clients']) && is_array($settings['clients'])) {
                $clientsByTgId = array_column($settings['clients'], null, 'tgId');
                if (isset($clientsByTgId[$tgId])) {
                    return $clientsByTgId[$tgId];
                }
            }
        }
        return null;
    }

    /**
     * Get traffic data for a client based on UUID.
     */
    public function getTrafficByUuid(string $uuid): array
    {
        return $this->makeRequest('GET', self::BASE_INBOUND_ENDPOINT . "getClientTrafficsById/{$uuid}");
    }

    /**
     * Updates an existing client.
     */
    public function updateClient(array $userData, ?string $inboundId = null): array
    {
        $inbound = $this->resolveInbound($inboundId);
        $mapping = [
            'uuid'       => 'id',
            'flow'       => 'flow',
            'email'      => 'email',
            'tgId'       => 'tgId',
            'limitIp'    => 'limitIp',
            'totalGB'    => 'totalGB',
            'expiryTime' => 'expiryTime',
            'enable'     => 'enable',
            'subId'      => 'subId',
            'reset'      => 'reset',
        ];
        $client = [];
        foreach ($mapping as $inputKey => $clientKey) {
            if (isset($userData[$inputKey])) {
                $client[$clientKey] = $userData[$inputKey];
            }
        }
        $payload = [
            'id'       => (int)$inbound,
            'settings' => json_encode(['clients' => [$client]]),
        ];
        return $this->makeRequest('POST', self::BASE_INBOUND_ENDPOINT . "updateClient/{$userData['uuid']}", $payload);
    }

    /**
     * Adds a new client.
     */
    public function addClient(array $userData, ?string $inboundId = null): array
    {
        $inbound = $this->resolveInbound($inboundId);
        $client = [
            'flow'       => $userData['flow']       ?? 'xtls-rprx-vision',
            'email'      => $userData['email']      ?? Str::random(10),
            'limitIp'    => $userData['limitIp']    ?? 1,
            'totalGB'    => $userData['totalGB']    ?? 0,
            'expiryTime' => $userData['expiryTime'] ?? 0,
            'enable'     => $userData['enable']     ?? false,
            'tgId'       => $userData['tgId']       ?? null,
            'subId'      => $userData['subId']      ?? (string)Str::uuid(),
            'reset'      => $userData['reset']      ?? 0,
            'id'         => $userData['uuid'],
        ];
        $payload = [
            'id'       => (int)$inbound,
            'settings' => json_encode(['clients' => [$client]]),
        ];
        return $this->makeRequest('POST', self::BASE_INBOUND_ENDPOINT . 'addClient', $payload);
    }

    /**
     * Deletes an existing client.
     */
    public function deleteClient(string $uuid, ?string $inboundId = null): array
    {
        $inbound = $this->resolveInbound($inboundId);
        $payload = ['id' => (int)$inbound];
        return $this->makeRequest('POST', self::BASE_INBOUND_ENDPOINT . "deleteClient/{$uuid}", $payload);
    }
}
