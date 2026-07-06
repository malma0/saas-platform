<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Ktokyda\Services\KtokudaService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Вход клиента на публичном сайте.
 *
 * Сейчас — лёгкий вход по номеру телефона (без пароля): достаточно, чтобы
 * быстро показать кабинет и привязать бронь. Поля e-mail и password уже
 * принимаются и заложены под будущий полноценный вход (User + пароль),
 * но пока не проверяются — это осознанный задел, а не заглушка.
 *
 * Идентификация клиента в системе остаётся по телефону в рамках клуба —
 * та же модель, что у гостевого бронирования (BookingController).
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone'    => 'required|string|min:5|max:30',
            'name'     => 'nullable|string|max:120',
            'email'    => 'nullable|email|max:160',
            'password' => 'nullable|string|max:200',
        ]);

        $branch = Branch::query()->where('is_active', true)->first();
        $clubId = $branch?->club_id;

        $phone = trim($data['phone']);

        $client = $clubId
            ? Client::withoutGlobalScopes()
                ->where('club_id', $clubId)
                ->where('phone', $phone)
                ->first()
            : null;

        $name = $client?->fullName() ?: trim((string) ($data['name'] ?? ''));

        return response()->json([
            'ok'          => true,
            'returning'   => $client !== null,
            'name'        => $name !== '' ? $name : null,
            'phone'       => $phone,
            'account_url' => route('web.account', ['phone' => $phone]),
        ]);
    }

    /**
     * Регистрация нового клиента.
     * Создаёт Client в нашей БД и одновременно регистрирует в КтоКуда.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone'      => 'required|string|min:5|max:30',
            'first_name' => 'required|string|max:80',
            'last_name'  => 'nullable|string|max:80',
        ]);

        $branch = Branch::query()->where('is_active', true)->first();
        $clubId = $branch?->club_id;
        $phone  = trim($data['phone']);

        // Проверяем — вдруг уже существует
        $existing = $clubId
            ? Client::withoutGlobalScopes()
                ->where('club_id', $clubId)
                ->where('phone', $phone)
                ->first()
            : null;

        if ($existing) {
            return response()->json([
                'ok'          => true,
                'returning'   => true,
                'account_url' => route('web.account', ['phone' => $phone]),
            ]);
        }

        // Создаём клиента
        $client = Client::create([
            'club_id'    => $clubId,
            'phone'      => $phone,
            'first_name' => trim($data['first_name']),
            'last_name'  => trim($data['last_name'] ?? ''),
            'source'     => 'web_registration',
        ]);

        // Регистрируем в КтоКуда — они отправят SMS с паролем
        $ktokyda = app(KtokudaService::class);
        $kkResult = $ktokyda->register($phone, $client->first_name, $client->last_name ?? '');

        return response()->json([
            'ok'           => true,
            'returning'    => false,
            'kk_sent'      => $kkResult['success'] ?? false,
            'account_url'  => route('web.account', ['phone' => $phone]),
        ]);
    }
}
