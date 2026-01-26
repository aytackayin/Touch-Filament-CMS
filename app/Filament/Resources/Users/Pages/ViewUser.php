<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.users.pages.view-user';

    public function editAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\EditAction::make()
            ->record($this->getRecord())
            ->url(UserResource::getUrl('edit', ['record' => $this->getRecord()]));
    }
}
