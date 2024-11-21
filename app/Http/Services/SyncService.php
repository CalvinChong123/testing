<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SynchronizeAuthToken;
use App\Exceptions\BadRequestException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncService
{
    public static function request($method, $path, $payload)
    {
        $authToken = SynchronizeAuthToken::latest()->first()->token;
        $hqUrl = config('app.hq_url');
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $authToken,
            ])->$method($hqUrl . $path, $payload);

            // Log::info($response->body());
            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('HTTP POST request failed: ' . $response->body());
                throw new BadRequestException($response->body(), $response->status());
            }
        } catch (BadRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('HTTP POST request exception: ' . $e->getMessage());
            throw new BadRequestException('HTTP POST request exception', 500);
        }
    }

    public static function syncUsers()
    {
        $path = '/api/user/sync';
        $payload = null;
        // $payload = ['only_this_outlet' => true];
        try {
            $hqResponse = self::request('get', $path, $payload);
            $members = $hqResponse['users']['data'] ?? $hqResponse['users'];

            DB::transaction(function () use ($members) {
                foreach ($members as $member) {
                    $updatedMember = User::updateOrCreate(
                        ['id' => $member['id']],
                        [
                            'outlet_id' => $member['outlet']['local_outlet_id'],
                            'first_name' => $member['first_name'],
                            'last_name' => $member['last_name'],
                            'email' => $member['email'],
                            'ic' => $member['ic'],
                            'phone_no' => $member['phone_no'],
                            'dob' => $member['dob'],
                            'member_no' => $member['member_no'],
                            'member_category' => $member['member_category'],
                            'member_tier' => $member['member_tier'],
                            'created_at' => $member['created_at'],
                            'updated_at' => $member['updated_at'],
                        ]
                    );
                }

                $memberIds = array_column($members, 'id');
                User::whereNotIn('id', $memberIds)->delete();
            });

            return true;
        } catch (BadRequestException $e) {
            return false;
        }
    }
}
