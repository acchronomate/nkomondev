<?php

namespace App\Filament\Admin\Resources\AvailabilityResource\Pages;

use App\Filament\Admin\Resources\AvailabilityResource;
use Filament\Resources\Pages\Page;

class Calendar extends Page
{
    protected static string $resource = AvailabilityResource::class;

    protected static string $view = 'filament.admin.resources.availability-resource.pages.calendar';

    protected static ?string $title = 'Vue calendrier';

    protected ?string $heading = 'Calendrier des disponibilités';

    /**
     * @param string|null $breadcrumb
     */
    public static function setBreadcrumb(?string $breadcrumb): void
    {
        static::$breadcrumb = $breadcrumb ?? 'Vue calendrier';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Retour à la liste')
                ->url(AvailabilityResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
