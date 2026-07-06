<?php

declare(strict_types=1);

namespace App\Domain\Ktokyda\Services;

use App\Domain\Crm\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class KtokudaService
{
    public const HOST          = 'https://ktokyda.ru';
    public const CITY_ID       = 1425;
    public const TT_CATEGORY_ID = 1678;

    // Статичный fingerprint нашего сервера — уникальный ID "устройства"
    private const FINGERPRINT = '7f3a9c12-4b8e-4d21-a5f6-tt-service-srv';

    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl(self::HOST)
            ->timeout(10)
            ->withHeaders(['Fingerprint' => self::FINGERPRINT])
            ->acceptJson();
    }

    // -------------------------------------------------------------------------
    // Аутентификация
    // -------------------------------------------------------------------------

    /**
     * Регистрация нового пользователя в КтоКуда.
     * После успеха на телефон придёт SMS с паролем.
     */
    public function register(string $phone, string $firstName, string $lastName = ''): array
    {
        $params = [
            'siteUser[login]'     => $phone,
            'siteUser[cityId]'    => self::CITY_ID,
            'siteUser[firstName]' => $firstName,
            'canVoice'            => 'true',
        ];

        if ($lastName !== '') {
            $params['siteUser[lastName]'] = $lastName;
        }

        return $this->http->get('/core/api/registration/', $params)->json() ?? ['success' => false];
    }

    /**
     * Вход по телефону + пароль из SMS.
     * Возвращает access_token, refresh_token, expires_in, userId, cityId.
     */
    public function login(string $phone, string $password): array
    {
        return $this->http->get('/core/api/login/', [
            'login'    => $phone,
            'password' => $password,
        ])->json() ?? ['success' => false];
    }

    /**
     * Обновление access_token через refresh_token.
     * TODO: уточнить точный путь эндпоинта у Сани.
     */
    public function refreshToken(string $refreshToken): array
    {
        return $this->http
            ->withHeaders(['Refresh-Token' => $refreshToken])
            ->get('/core/api/token/refresh/')
            ->json() ?? ['success' => false];
    }

    // -------------------------------------------------------------------------
    // События
    // -------------------------------------------------------------------------

    /**
     * Список активных событий по настольному теннису.
     */
    public function getEvents(int $page = 1): array
    {
        return $this->http->get('/core/api/guest/category/events/', [
            'cityId'      => self::CITY_ID,
            'categoryId'  => self::TT_CATEGORY_ID,
            'withoutChild' => 'false',
            'page'        => $page,
        ])->json() ?? ['success' => false, 'events' => []];
    }

    /**
     * Детальная информация о событии.
     */
    public function getEventDetail(int $eventId): array
    {
        return $this->http->get("/core/api/guest/event/{$eventId}/detail", [
            'cityId' => self::CITY_ID,
        ])->json() ?? ['success' => false];
    }

    // -------------------------------------------------------------------------
    // Запись / отмена
    // -------------------------------------------------------------------------

    /**
     * Записать пользователя на событие.
     */
    public function signUpForEvent(int $eventId, string $accessToken): array
    {
        return $this->http
            ->withHeaders(['Access-Token' => $accessToken])
            ->post("/core/api/event/{$eventId}/sign_up_applicant")
            ->json() ?? ['success' => false];
    }

    /**
     * Отменить запись на событие.
     */
    public function signOutFromEvent(int $eventId, string $accessToken): array
    {
        return $this->http
            ->withHeaders(['Access-Token' => $accessToken])
            ->post("/core/api/event/{$eventId}/sign_out_applicant")
            ->json() ?? ['success' => false];
    }

    // -------------------------------------------------------------------------
    // Токены клиента
    // -------------------------------------------------------------------------

    /**
     * Получить действующий access_token клиента.
     * Если истёк — пробует обновить через refresh_token.
     * Если не привязан — возвращает null.
     */
    public function getAccessToken(Client $client): ?string
    {
        $token     = $client->ktokyda_access_token;
        $expiresAt = (int) $client->ktokyda_token_expires_at;

        if (! $token) {
            return null;
        }

        // Токен ещё живой (с запасом 5 минут)
        if ($expiresAt - time() > 300) {
            return $token;
        }

        // Пробуем обновить
        $refreshToken = $client->ktokyda_refresh_token;
        if (! $refreshToken) {
            return null;
        }

        $result = $this->refreshToken($refreshToken);

        if (! ($result['success'] ?? false)) {
            return null;
        }

        $client->update([
            'ktokyda_access_token'    => $result['access_token'],
            'ktokyda_refresh_token'   => $result['refresh_token'],
            'ktokyda_token_expires_at' => $result['expires_in'],
        ]);

        return $result['access_token'];
    }

    /**
     * Сохранить токены КтоКуда в профиле клиента.
     */
    public function storeTokens(Client $client, array $loginResponse): void
    {
        $client->update([
            'ktokyda_user_id'          => $loginResponse['userId'] ?? null,
            'ktokyda_access_token'     => $loginResponse['access_token'] ?? null,
            'ktokyda_refresh_token'    => $loginResponse['refresh_token'] ?? null,
            'ktokyda_token_expires_at' => $loginResponse['expires_in'] ?? null,
        ]);
    }

    public function isLinked(Client $client): bool
    {
        return $client->ktokyda_user_id !== null;
    }
}
