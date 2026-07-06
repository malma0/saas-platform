<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Ktokyda\Services\KtokudaService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KtokudaController extends Controller
{
    public function __construct(private readonly KtokudaService $ktokyda) {}

    // -------------------------------------------------------------------------
    // События
    // -------------------------------------------------------------------------

    /**
     * GET /ktokyda/events
     * Список ближайших ТТ-событий из КтоКуда.
     */
    public function events(Request $request): JsonResponse
    {
        $page   = (int) $request->query('page', 1);
        $result = $this->ktokyda->getEvents($page);

        return response()->json([
            'ok'     => $result['success'] ?? false,
            'events' => $result['events'] ?? [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Привязка аккаунта
    // -------------------------------------------------------------------------

    /**
     * POST /ktokyda/register
     * Регистрирует телефон в КтоКуда → отправляет SMS с паролем.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone'     => 'required|string|min:5|max:30',
            'firstName' => 'required|string|max:80',
            'lastName'  => 'nullable|string|max:80',
        ]);

        $result = $this->ktokyda->register(
            $data['phone'],
            $data['firstName'],
            $data['lastName'] ?? '',
        );

        if (! ($result['success'] ?? false)) {
            $msg = $result['errors'][0]['msg']
                ?? $result['errors']['msg']
                ?? 'Ошибка регистрации';

            // Телефон уже есть в КтоКуда — просто переходим к вводу пароля
            if (str_contains($msg, 'уже зарегистрирован') || str_contains(strtolower($msg), 'already')) {
                return response()->json(['ok' => true, 'already_exists' => true]);
            }

            return response()->json(['ok' => false, 'error' => $msg], 422);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * POST /ktokyda/link
     * Авторизует клиента в КтоКуда по паролю из SMS и сохраняет токены.
     */
    public function link(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone'    => 'required|string|min:5|max:30',
            'password' => 'required|string|max:100',
        ]);

        $result = $this->ktokyda->login($data['phone'], $data['password']);

        if (! ($result['success'] ?? false)) {
            $msg = $result['errors'][0]['msg']
                ?? $result['errors']['msg']
                ?? 'Неверный пароль';
            return response()->json(['ok' => false, 'error' => $msg], 422);
        }

        $client = $this->findClient($data['phone']);

        if ($client) {
            $this->ktokyda->storeTokens($client, $result);
        }

        return response()->json([
            'ok'   => true,
            'name' => $result['fio'] ?? null,
        ]);
    }

    /**
     * POST /ktokyda/unlink
     * Отвязывает аккаунт КтоКуда от клиента.
     */
    public function unlink(Request $request): JsonResponse
    {
        $phone  = $request->validate(['phone' => 'required|string'])['phone'];
        $client = $this->findClient($phone);

        if ($client) {
            $client->update([
                'ktokyda_user_id'          => null,
                'ktokyda_access_token'     => null,
                'ktokyda_refresh_token'    => null,
                'ktokyda_token_expires_at' => null,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Запись / отмена
    // -------------------------------------------------------------------------

    /**
     * POST /ktokyda/event/{eventId}/signup
     */
    public function signup(Request $request, int $eventId): JsonResponse
    {
        $phone  = $request->validate(['phone' => 'required|string'])['phone'];
        $client = $this->findClient($phone);

        if (! $client) {
            return response()->json(['ok' => false, 'error' => 'Клиент не найден'], 404);
        }

        $token = $this->ktokyda->getAccessToken($client);

        if (! $token) {
            return response()->json(['ok' => false, 'error' => 'linked'], 403);
        }

        $result = $this->ktokyda->signUpForEvent($eventId, $token);

        return response()->json([
            'ok'    => $result['success'] ?? false,
            'error' => $result['errors'][0]['msg'] ?? $result['errors']['msg'] ?? null,
        ]);
    }

    /**
     * POST /ktokyda/event/{eventId}/signout
     */
    public function signout(Request $request, int $eventId): JsonResponse
    {
        $phone  = $request->validate(['phone' => 'required|string'])['phone'];
        $client = $this->findClient($phone);

        if (! $client) {
            return response()->json(['ok' => false, 'error' => 'Клиент не найден'], 404);
        }

        $token = $this->ktokyda->getAccessToken($client);

        if (! $token) {
            return response()->json(['ok' => false, 'error' => 'linked'], 403);
        }

        $result = $this->ktokyda->signOutFromEvent($eventId, $token);

        return response()->json([
            'ok'    => $result['success'] ?? false,
            'error' => $result['errors'][0]['msg'] ?? $result['errors']['msg'] ?? null,
        ]);
    }

    // -------------------------------------------------------------------------

    private function findClient(string $phone): ?Client
    {
        $branch = Branch::query()->where('is_active', true)->first();

        return Client::withoutGlobalScopes()
            ->when($branch, fn ($q) => $q->where('club_id', $branch->club_id))
            ->where('phone', $phone)
            ->first();
    }
}
