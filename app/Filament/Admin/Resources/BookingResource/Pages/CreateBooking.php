<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use App\Models\Room;
use App\Models\Availability;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Réservation créée')
            ->body("La réservation #{$this->record->booking_number} a été créée avec succès.");
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $room = Room::find($data['room_id']);
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        // Calculer le prix total
        $totalPrice = $room->calculateTotalPrice($data['check_in'], $data['check_out']);

        $data['room_price'] = $room->base_price_per_night;
        $data['total_nights'] = $nights;
        $data['subtotal'] = $totalPrice;
        $data['commission_rate'] = 5.00;
        $data['commission_amount'] = $totalPrice * 0.05;
        $data['total_amount'] = $totalPrice;
        $data['currency_id'] = $room->accommodation->currency_id;
        $data['exchange_rate_used'] = $room->accommodation->currency->exchange_rate;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Réduire les disponibilités
        $checkIn = $this->record->check_in->copy();
        $checkOut = $this->record->check_out->copy();

        while ($checkIn->lt($checkOut)) {
            $availability = Availability::firstOrCreate(
                [
                    'room_id' => $this->record->room_id,
                    'date' => $checkIn->format('Y-m-d'),
                ],
                [
                    'available_quantity' => $this->record->room->total_quantity,
                ]
            );

            $availability->decreaseQuantity(1);
            $checkIn->addDay();
        }

        // Notifier l'hébergeur
        Notification::make()
            ->title('Nouvelle réservation')
            ->body("Nouvelle réservation #{$this->record->booking_number} pour {$this->record->room->accommodation->name}")
            ->sendToDatabase($this->record->room->accommodation->user);
    }
}
