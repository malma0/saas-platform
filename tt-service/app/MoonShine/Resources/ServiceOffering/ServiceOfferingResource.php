<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ServiceOffering;

use App\Domain\Services\Models\ServiceOffering;
use App\MoonShine\Resources\ServiceOffering\Pages\ServiceOfferingFormPage;
use App\MoonShine\Resources\ServiceOffering\Pages\ServiceOfferingIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ModelResource<ServiceOffering, ServiceOfferingIndexPage, ServiceOfferingFormPage, null>
 */
#[Icon('clipboard-document-list')]
#[Group('Клуб', 'building-office-2')]
#[Order(3)]
class ServiceOfferingResource extends ModelResource
{
    protected string $model  = ServiceOffering::class;
    protected string $column = 'name';

    public function getTitle(): string { return 'Услуги'; }

    protected function pages(): array
    {
        return [ServiceOfferingIndexPage::class, ServiceOfferingFormPage::class];
    }

    protected function search(): array { return ['id', 'name']; }
}
