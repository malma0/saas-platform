<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Staff\Pages;

use App\MoonShine\Resources\Staff\StaffResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\PasswordRepeat;
use MoonShine\UI\Fields\Text;

/** @extends FormPage<StaffResource> */
final class StaffFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make('Учётная запись', [
                ID::make(),
                Flex::make([
                    Text::make('Имя', 'name')->required(),
                    Text::make('Email', 'email')->required(),
                ]),
                // Текущая роль (Spatie) — только для просмотра.
                // Назначение ролей делается через раздел ролей/прав.
                Text::make('Роль', 'roles.0.name')->readonly(),
            ]),
            Box::make('Пароль', [
                Password::make('Пароль', 'password'),
                PasswordRepeat::make('Повтор пароля', 'password_confirmation'),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        $isNew = ! $item->getOriginal()?->exists ?? true;

        return [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255',
            'password'              => $isNew ? 'required|min:8|confirmed' : 'nullable|min:8|confirmed',
            'password_confirmation' => $isNew ? 'required' : 'nullable',
        ];
    }
}
