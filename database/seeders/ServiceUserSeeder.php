<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class ServiceUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => env('SERVICE_USER_EMAIL', 'sial-service@samuel.local')],
            ['name' => 'Service SIAL', 'password' => bcrypt(Str::random(40))]
        );

        $token = $user->createToken("sial-api", ["read:sial","export:sial","sync:sial"])->plainTextToken;
        $this->command->info("\\nService token para SAMUEL (copiar a LUCY .env SAMUEL_TOKEN):\\n{$token}\\n");
    }
}
