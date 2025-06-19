<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('generate_monthly')
                ->label('Générer factures du mois')
                ->icon('heroicon-o-document-plus')
                ->url(fn () => InvoiceResource::getUrl('generate'))
                ->color('primary'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes')
                ->badge(fn () => $this->getModel()::count()),

            'draft' => Tab::make('Brouillons')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(fn () => $this->getModel()::where('status', 'draft')->count())
                ->badgeColor('gray'),

            'sent' => Tab::make('Envoyées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent'))
                ->badge(fn () => $this->getModel()::where('status', 'sent')->count())
                ->badgeColor('warning'),

            'overdue' => Tab::make('En retard')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue())
                ->badge(fn () => $this->getModel()::overdue()->count())
                ->badgeColor('danger'),

            'paid' => Tab::make('Payées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid'))
                ->badge(fn () => $this->getModel()::where('status', 'paid')->count())
                ->badgeColor('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvoiceResource\Widgets\InvoiceStats::class,
        ];
    }
}
