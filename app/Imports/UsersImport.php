<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Department;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use App\Notifications\UserCreated;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow, ShouldQueue, WithChunkReading
{
    public function model(array $row)
    {
        // Cherche ou crée le département
        $department = Department::firstOrCreate([
            'name' => trim($row['department']),
        ]);

        // Crée ou met à jour l'utilisateur
        $user = User::updateOrCreate(
            ['email' => trim($row['email'])],
            [
                'name'          => trim($row['first_name']).' '.trim($row['last_name']),
                'password'      => bcrypt(Str::random(10)),
                'is_admin'      => false,
            ]
        );

        if($department) {
            $user->departments()->syncWithoutDetaching([$department->id]);
        }

        // Token de reset + notif
        $token = Password::broker()->createToken($user);
        $user->notify(new UserCreated($token, $user->email));

        return $user;
    }

    public function chunkSize(): int { return 1000; }
    public function batchSize(): int { return 1000; }
}
