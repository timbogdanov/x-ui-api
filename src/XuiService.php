<?php

namespace TimBogdanov\Xui;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XuiService
{
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
     * Get a valid session cookie for the x‑ui API.
     *
     * @return string|null
     */
    private function getSessionCookie(): ?string
    {
        if (Cache::has('xui_session')) {
            return Cache::get('xui_session');
        }

        $url = "https://{$this->host}:{$this->port}/{$this->path}/login";
        $response = Http::asForm()->post($url, [
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
            if ($cookie->getName() === '3x-ui') {
                $sessionId = $cookie->getValue();
                Cache::put('xui_session', $sessionId, now()->addHour());
                return $sessionId;
            }
        }

        Log::error('XuiService: Login successful but 3x-ui cookie is missing.');
        return null;
    }

    /**
     * Make a GET or POST request to the x‑ui API.
     *
     * @param string $method (GET or POST)
     * @param string $endpoint
     * @param array $payload
     * @return array
     */
    private function makeRequest(string $method, string $endpoint, array $payload = []): array
    {
        $sessionCookie = $this->getSessionCookie();
        if (!$sessionCookie) {
            return ['error' => 'Login failed'];
        }

        $url = "https://{$this->host}:{$this->port}/{$this->path}/{$endpoint}";
        $request = Http::withHeaders(['Cookie' => "3x-ui={$sessionCookie}"]);

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
     *
     * @param string|null $inboundId
     * @return array
     */
    public function getAllClients(?string $inboundId = null): array
    {
        $inboundId = $inboundId ?? $this->inboundId;
        return $this->makeRequest('GET', 'panel/api/inbounds/get/' . $inboundId);
    }

    /**
     * Retrieve a user by their Telegram ID.
     *
     * @param int $tgId
     * @param string|null $inboundId
     * @return array|null
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
     * Get traffic data for a specific user by UUID.
     *
     * @param string $uuid
     * @return array
     */
    public function getTrafficByUuid(string $uuid): array
    {
        return $this->makeRequest('GET', "panel/api/inbounds/getClientTrafficsById/{$uuid}");
    }

    /**
     * Update an existing user based on UUID.
     *
     * @param array $userData
     * @param string|null $inboundId
     * @return array
     */
    public function updateClient(array $userData, ?string $inboundId = null): array
    {
        $inboundId = $inboundId ?? $this->inboundId;

        $client = [];
        if (isset($userData['uuid']))     $client['id']        = $userData['uuid'];
        if (isset($userData['flow']))     $client['flow']      = $userData['flow'];
        if (isset($userData['email']))    $client['email']     = $userData['email'];
        if (isset($userData['tgId']))     $client['tgId']      = $userData['tgId'];
        if (isset($userData['limitIp']))  $client['limitIp']   = $userData['limitIp'];
        if (isset($userData['totalGB']))  $client['totalGB']   = $userData['totalGB'];
        if (isset($userData['expiryTime'])) $client['expiryTime'] = $userData['expiryTime'];
        if (isset($userData['enable']))   $client['enable']    = $userData['enable'];
        if (isset($userData['subId']))    $client['subId']     = $userData['subId'];
        if (isset($userData['reset']))    $client['reset']     = $userData['reset'];

        $payload = [
            'id'       => (int)$inboundId,
            'settings' => json_encode([
                'clients' => [$client]
            ])
        ];

        return $this->makeRequest('POST', "panel/api/inbounds/updateClient/{$userData['uuid']}", $payload);
    }

    /**
     * Add a new user.
     *
     * @param array $userData
     * @param string|null $inboundId
     * @return array
     */
    public function addClient(array $userData, ?string $inboundId = null): array
    {
        $inboundId = $inboundId ?? $this->inboundId;

        $client = [];
        $client['flow']        = $userData['flow']       ?? 'xtls-rprx-vision';
        $client['email']       = $userData['email']      ?? Str::random(10);
        $client['limitIp']     = $userData['limitIp']    ?? 1;
        $client['totalGB']     = $userData['totalGB']    ?? 0;
        $client['expiryTime']  = $userData['expiryTime'] ?? 0;
        $client['enable']      = $userData['enable']     ?? false;
        if (isset($userData['tgId'])) {
            $client['tgId'] = $userData['tgId'];
        }
        $client['subId'] = $userData['subId'] ?? (string) Str::uuid();
        $client['reset'] = $userData['reset'] ?? 0;
        $client['id']    = $userData['uuid'];

        $payload = [
            'id'       => (int)$inboundId,
            'settings' => json_encode([
                'clients' => [$client]
            ])
        ];

        return $this->makeRequest('POST', 'panel/api/inbounds/addClient', $payload);
    }

    /**
     * Delete an existing user.
     *
     * @param string $uuid
     * @param string|null $inboundId
     * @return array
     */
    public function deleteClient(string $uuid, ?string $inboundId = null): array
    {
        $inboundId = $inboundId ?? $this->inboundId;

        // Assuming the API expects a POST request for deletion.
        $payload = [
            'id' => (int)$inboundId,
        ];

        return $this->makeRequest('POST', "panel/api/inbounds/deleteClient/{$uuid}", $payload);
    }

    /**
     * Re-synchronize (create or update) a user based on their Telegram ID.
     *
     * @param object $user
     * @param string|null $inboundId
     * @return void
     */
    public function reSync($user, ?string $inboundId = null): void
    {
        $inboundId = $inboundId ?? $this->inboundId;
        $ends_at = $user->ends_at ? Carbon::parse($user->ends_at)->timestamp * 1000 : 0;

        $userData = [
            'uuid'       => $user->uuid,
            'flow'       => 'xtls-rprx-vision',
            'email'      => $user->email,
            'tgId'       => $user->telegram_id,
            'limitIp'    => $user->limitIp ?? 1,
            'totalGB'    => $user->totalGB ?? 0,
            'expiryTime' => $ends_at,
            'enable'     => $user->is_enabled ?? true,
            'subId'      => $user->subscription_link,
            'reset'      => $user->reset ?? 0,
        ];

        $existsInXui = $this->getClientByTgId($user->telegram_id, $inboundId);

        if ($existsInXui) {
            Log::info('Updating existing user -> ' . $user->name);
            $response = $this->updateClient($userData, $inboundId);
        } else {
            Log::info('Creating new user -> ' . $user->name);
            $response = $this->addClient($userData, $inboundId);
        }

        // You can log or handle the $response as needed.
        Log::info('x‑ui response', $response);
    }
}
