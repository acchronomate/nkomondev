<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configuration globale des notifications Filament
        Notification::configureUsing(function (Notification $notification): void {
            $notification->duration(5000);
        });
    }

    /**
     * Helper pour envoyer une notification de nouvelle réservation.
     */
    public static function sendNewBookingNotification($booking): void
    {
        Notification::make()
            ->title('Nouvelle réservation')
            ->body("Réservation #{$booking->booking_number} reçue")
            ->actions([
                Action::make('view')
                    ->label('Voir')
                    ->url(route('filament.admin.resources.bookings.edit', $booking))
                    ->markAsRead(),
            ])
            ->sendToDatabase($booking->room->accommodation->user);
    }

    /**
     * Helper pour envoyer une notification de confirmation.
     */
    public static function sendBookingConfirmationNotification($booking): void
    {
        Notification::make()
            ->title('Réservation confirmée')
            ->body("Votre réservation #{$booking->booking_number} a été confirmée")
            ->success()
            ->sendToDatabase($booking->user);
    }
}
