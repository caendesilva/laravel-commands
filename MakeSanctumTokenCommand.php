<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSanctumTokenCommand extends Command
{
    protected $signature = 'make:token {--id= : The ID of the user}';
    protected $description = 'Create Sanctum API token for a user';

    public function handle(): void
    {
        $id = $this->option('id') ?? $this->ask('What is the user ID?');

        $user = User::find($id);

        if (! $user) {
            $this->error("User #$id not found.");
            return;
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $this->info("Token for user #$id: $token");
    }
}
