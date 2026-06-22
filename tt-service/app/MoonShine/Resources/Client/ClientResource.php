<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Client;

use App\Domain\Crm\Models\Client;
use App\MoonShine\Resources\Client\Pages\ClientFormPage;
use App\MoonShine\Resources\Client\Pages\ClientIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ModelResource<Client, ClientIndexPage, ClientFormPage, null>
 */
#[Icon('users')]
#[Group('CRM', 'identification')]
#[Order(0)]
class ClientResource extends ModelResource
{
    protected string $model  = Client::class;
    protected string $column = 'first_name';

    public function getTitle(): string { return 'Клиенты'; }

    protected function pages(): array
    {
        return [ClientIndexPage::class, ClientFormPage::class];
    }

    protected function search(): array
    {
        return ['id', 'first_name', 'last_name', 'phone', 'email'];
    }
}
