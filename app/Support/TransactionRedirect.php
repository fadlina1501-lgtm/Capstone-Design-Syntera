<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TransactionRedirect
{
    public static function success(
        Request $request,
        string $desktopRoute,
        string $message,
        array $desktopParameters = [],
        array $mobileFlash = []
    ): RedirectResponse|JsonResponse {
        $stationKey = (string) $request->input('_mobile_station', '');
        $station = $stationKey !== '' ? config("scan_stations.stations.{$stationKey}") : null;
        $roleName = $request->user()?->role?->role_name;

        if (is_array($station) && ($station['role'] ?? null) === $roleName) {
            if ($request->expectsJson()) {
                return response()->json(array_merge([
                    'ok' => true,
                    'message' => $message,
                ], $mobileFlash));
            }

            $response = redirect()->route('mobile-station.show', ['station' => $stationKey])->with('status', $message);
            foreach ($mobileFlash as $key => $value) {
                $response->with($key, $value);
            }
            return $response;
        }

        return redirect()->route($desktopRoute, $desktopParameters)->with('status', $message);
    }
}
