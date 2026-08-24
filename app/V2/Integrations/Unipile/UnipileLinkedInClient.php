<?php

namespace App\V2\Integrations\Unipile;

use App\V2\Contracts\Providers\AccountProviderInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnipileLinkedInClient implements AccountProviderInterface
{
    private function isMock(): bool
    {
        return (bool) config('services.unipile.mock', false);
    }

    private function endpoint(string $key): string
    {
        $value = (string) config("services.unipile.endpoints.{$key}", '');
        if ($value === '') {
            throw new UnipileException("Missing messaging endpoint config for key [{$key}].", 500, ['key' => $key]);
        }

        if (! str_starts_with($value, '/')) {
            $value = '/'.$value;
        }

        return $value;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.unipile.base_url'), '/');
    }

    private function hostedApiUrl(): string
    {
        $base = $this->baseUrl();

        return (string) preg_replace('#/api/v\d+/?$#', '', $base);
    }

    private function apiKey(): string
    {
        return (string) config('services.unipile.api_key', '');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(3, 250)
            ->withHeaders([
                'X-API-KEY' => $this->apiKey(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $endpoint, array $payload = []): array
    {
        $quiet = (bool) Arr::pull($payload, '_quiet', false);

        if ($this->isMock()) {
            Log::debug('[LinkedIn messaging] MOCK request', [
                'method' => strtoupper($method),
                'endpoint' => $endpoint,
            ]);

            return [
                'mock' => true,
                'method' => strtoupper($method),
                'endpoint' => $endpoint,
                'payload' => $payload,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        if ($this->apiKey() === '') {
            throw new UnipileException('LinkedIn messaging is not configured on this server.', 500);
        }

        return $this->sendRequest($method, $endpoint, $payload, $quiet);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sendRequest(string $method, string $endpoint, array $payload, bool $quiet = false): array
    {
        $normalizedMethod = strtoupper($method);

        $options = in_array($normalizedMethod, ['GET', 'DELETE'], true)
            ? ['query' => $payload]
            : ['json' => $payload];

        $logPayload = $payload;
        if (isset($logPayload['access_token'])) {
            $logPayload['access_token'] = substr((string) $logPayload['access_token'], 0, 8).'…[redacted]';
        }

        Log::debug('[LinkedIn messaging] → '.$normalizedMethod.' '.$endpoint, [
            'payload' => $logPayload,
        ]);

        try {
            $response = $this->client()->send($normalizedMethod, $endpoint, $options)->throw();
        } catch (RequestException $exception) {
            $status = $exception->response?->status() ?? 502;
            $responseBody = $exception->response?->json();
            $responseText = $exception->response?->body() ?? $exception->getMessage();

            if ($quiet && in_array($status, [404, 422], true)) {
                Log::debug('[LinkedIn messaging] quiet lookup miss', [
                    'endpoint' => $endpoint,
                    'status' => $status,
                ]);
            } else {
                Log::error('[LinkedIn messaging] ✗ '.$normalizedMethod.' '.$endpoint.' → HTTP '.$status, [
                    'payload' => $logPayload,
                    'base_url' => $this->baseUrl(),
                    'response_body' => $responseBody,
                    'response_text' => substr((string) $responseText, 0, 500),
                ]);
            }

            $detail = '';
            if (is_array($responseBody)) {
                $title = trim((string) ($responseBody['title'] ?? ''));
                $bodyDetail = trim((string) ($responseBody['detail'] ?? $responseBody['message'] ?? $responseBody['error'] ?? ''));
                if ($bodyDetail !== '' && (str_contains($bodyDetail, '"schema"') || strlen($bodyDetail) > 200)) {
                    $bodyDetail = $title !== '' ? $title : 'Invalid request parameters';
                }
                if ($bodyDetail !== '') {
                    $detail = ': '.$bodyDetail;
                } elseif ($title !== '') {
                    $detail = ': '.$title;
                }
            } elseif ($responseText) {
                $detail = ': '.substr((string) $responseText, 0, 200);
            }

            throw new UnipileException(
                'LinkedIn messaging error (HTTP '.$status.')'.$detail,
                $status,
                [
                    'method' => $normalizedMethod,
                    'endpoint' => $endpoint,
                    'payload' => $payload,
                    'response' => $responseBody,
                    'error_code' => is_array($responseBody) ? ($responseBody['type'] ?? null) : null,
                ]
            );
        }

        $responseData = $response->json() ?? [];
        Log::debug('[LinkedIn messaging] ✓ '.$normalizedMethod.' '.$endpoint.' → HTTP '.$response->status());

        return is_array($responseData) ? $responseData : [];
    }

    public function createHostedAuthLink(array $context = []): array
    {
        $successUrl = Arr::get($context, 'success_redirect_url', config('app.url').'/social-account?connected=1');
        $failUrl = Arr::get($context, 'failure_redirect_url', config('app.url').'/social-account?error=1');
        $organizationId = Arr::get($context, 'organization_id');
        $notifyUrlBase = Arr::get($context, 'notify_url', config('app.url').'/api/v2/provider-events/unipile');
        $notifyUrl = $notifyUrlBase;
        if (! empty($organizationId) && ! str_contains($notifyUrlBase, 'organization_id=')) {
            $notifyUrl .= (str_contains($notifyUrlBase, '?') ? '&' : '?').'organization_id='.(int) $organizationId;
        }

        $provider = Arr::get($context, 'provider', 'LINKEDIN');
        $providers = $this->normalizeHostedProviders($provider);
        $reconnectAccount = trim((string) (Arr::get($context, 'reconnect_account') ?? ''));
        $type = strtolower((string) Arr::get($context, 'type', 'create'));
        if ($type === 'reconnect' && $reconnectAccount !== '') {
            $type = 'reconnect';
        } else {
            $type = 'create';
            $reconnectAccount = '';
        }

        $payload = array_filter([
            'type' => $type,
            'reconnect_account' => $reconnectAccount !== '' ? $reconnectAccount : null,
            'providers' => $providers,
            'api_url' => $this->hostedApiUrl(),
            'expiresOn' => now()->utc()->addHours(2)->format('Y-m-d\TH:i:s.v\Z'),
            'success_redirect_url' => $successUrl,
            'failure_redirect_url' => $failUrl,
            'notify_url' => $notifyUrl,
            'name' => Arr::get($context, 'name'),
            'state' => Arr::get($context, 'state'),
        ], fn ($value) => $value !== null);

        return $this->request('POST', $this->endpoint('hosted_auth_link'), $payload);
    }

    /**
     * @return string|array<int, string>
     */
    private function normalizeHostedProviders(mixed $provider): string|array
    {
        if (is_array($provider)) {
            return count($provider) === 1 ? [$provider[0]] : '*:MAILING';
        }

        $value = strtoupper(trim((string) $provider));

        if ($value === '' || $value === '*') {
            return '*';
        }

        if (str_contains($value, ':')) {
            return $value;
        }

        return [$value];
    }

    public function connectWithCookie(string $liAt, string $userAgent, array $options = []): array
    {
        $payload = array_filter([
            'provider' => 'LINKEDIN',
            'access_token' => $liAt,
            'user_agent' => $userAgent,
            'country' => Arr::get($options, 'country'),
            'proxy' => Arr::get($options, 'proxy'),
        ], fn ($v) => $v !== null && $v !== '');

        return $this->request('POST', $this->endpoint('connect_account'), $payload);
    }

    public function disconnectAccount(string $accountId): array
    {
        return $this->request('DELETE', sprintf($this->endpoint('get_account'), $accountId));
    }

    public function listAccounts(string $ownerId): array
    {
        return $this->request('GET', $this->endpoint('list_accounts'), [
            'owner_id' => $ownerId,
        ]);
    }

    public function getAccount(string $accountId): array
    {
        return $this->request('GET', $this->endpoint('list_accounts').'/'.$accountId);
    }

    public function reconnectAccount(string $accountId, array $context = []): array
    {
        return $this->request('POST', $this->endpoint('list_accounts').'/'.$accountId.'/reconnect', $context);
    }
}
