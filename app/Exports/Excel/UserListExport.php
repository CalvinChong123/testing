<?php

namespace App\Exports\Excel;

use App\Models\User;
use App\Queriplex\UserQueriplex;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserListExport implements FromCollection, WithHeadings
{
    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function collection()
    {
        $payload = $this->payload;
        $query = UserQueriplex::make(User::query(), $payload);
        $query->whereHas('roles', function ($q) use ($payload) {
            $q->where('classification_level', '<', $payload['highestClassificationLevel']);

            if (isset($payload['role_id'])) {
                $q->where('id', $payload['role_id']);
            }
        });
        $users = $query->get();

        $users->load([
            'roles',
        ]);

        $result = $users->map(function (User $item) {
            $roleNames = $item->roles->map(function ($role) {
                return $role->name;
            });

            return [
                'id' => $item->id,
                'name' => $item->name,
                'role' => implode(', ', $roleNames->toArray()),
                'email' => $item->email,
                'email_verified_at' => $item->email_verified_at,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'deleted_at' => $item->deleted_at,
            ];
        });

        return $result;
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'role',
            'email',
            'email_verified_at',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }
}
