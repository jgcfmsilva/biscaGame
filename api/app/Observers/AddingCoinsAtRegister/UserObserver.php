<?php

namespace App\Observers\AddingCoinsAtRegister;

use App\Models\User;
use App\Services\Transactions\TransactionService;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Este evento dispara automaticamente quando um novo User é inserido na BD.
     */
    public function created(User $user): void
    {
        if ($user->type === 'A') {
            return;
        }

        // Requisito: "Newly registered users receive a welcome bonus of 10 coins"
        // Usamos o serviço para garantir que a transação fica registada no histórico
        $service = new TransactionService();
        $service->credit($user, 'Bonus', config('constants.starting_coins_balance'));
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
