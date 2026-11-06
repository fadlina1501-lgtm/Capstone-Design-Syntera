<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RemoteScannerController extends Controller
{
    /** Create a short-lived, user-bound session for the phone scanner. */
    public function create(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:single,multiple'],
        ]);

        $token = Str::random(48);
        $expiresAt = now()->addMinutes(15);
        $now = now();

        DB::table('remote_scan_sessions')->insert([
            'user_id' => $request->user()->user_id,
            'token_hash' => hash('sha256', $token),
            'mode' => $data['mode'],
            'status' => 'active',
            'latest_barcode' => null,
            'sequence' => 0,
            'consumed_sequence' => 0,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'token' => $token,
            'mode' => $data['mode'],
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    /** Show the focused scan-only interface on the phone. */
    public function phone(string $token)
    {
        $session = $this->findByToken($token);
        abort_unless($session && $session->status === 'active' && now()->lt($session->expires_at), 410, 'Sesi scanner sudah berakhir. Buat sesi baru dari laptop.');

        return response()
            ->view('remote-scanner.phone', [
                'token' => $token,
                'mode' => $session->mode,
                'expiresAt' => $session->expires_at,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /** Receive a code read by the phone camera and queue it for the laptop. */
    public function storeScan(Request $request, string $token)
    {
        $data = $request->validate([
            'barcode' => ['required', 'string', 'max:120'],
        ]);

        return DB::transaction(function () use ($data, $token) {
            $session = DB::table('remote_scan_sessions')
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (!$session || $session->status !== 'active' || now()->gte($session->expires_at)) {
                return response()->json(['message' => 'Sesi scanner sudah berakhir. Hubungkan ulang dari laptop.'], 410);
            }

            if ($session->mode === 'single' && (int) $session->sequence >= 1) {
                return response()->json(['message' => 'Sesi ini hanya menerima satu barcode. Silakan submit dari HP atau buat sesi baru.'], 409);
            }

            $nextSequence = (int) $session->sequence + 1;
            $barcode = trim($data['barcode']);

            DB::table('remote_scan_sessions')
                ->where('scan_session_id', $session->scan_session_id)
                ->update([
                    'latest_barcode' => $barcode,
                    'sequence' => $nextSequence,
                    'submit_result' => null,
                    'submit_message' => null,
                    'updated_at' => now(),
                ]);

            DB::table('remote_scan_events')->insert([
                'scan_session_id' => $session->scan_session_id,
                'sequence' => $nextSequence,
                'barcode' => $barcode,
                'scanned_at' => now(),
                'consumed_at' => null,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Barcode terkirim ke laptop.',
                'sequence' => $nextSequence,
                'status' => 'active',
            ]);
        });
    }

    /** The phone requests submission after the operator has checked the laptop form. */
    public function requestSubmit(string $token)
    {
        return DB::transaction(function () use ($token) {
            $session = DB::table('remote_scan_sessions')
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (!$session || $session->status !== 'active' || now()->gte($session->expires_at)) {
                return response()->json(['message' => 'Sesi pairing sudah berakhir. Hubungkan ulang dari laptop.'], 410);
            }

            if ((int) $session->sequence < 1) {
                return response()->json(['message' => 'Pindai minimal satu barcode sebelum submit transaksi.'], 422);
            }

            if ($session->submit_result === 'pending') {
                return response()->json(['ok' => true, 'message' => 'Permintaan submit sedang diproses laptop.']);
            }

            DB::table('remote_scan_sessions')
                ->where('scan_session_id', $session->scan_session_id)
                ->update([
                    'submit_request_seq' => (int) $session->submit_request_seq + 1,
                    'submit_result' => 'pending',
                    'submit_message' => 'Permintaan submit diterima. Menunggu laptop memeriksa form.',
                    'updated_at' => now(),
                ]);

            return response()->json([
                'ok' => true,
                'message' => 'Permintaan submit dikirim ke laptop.',
            ]);
        });
    }

    /** Phone reads the result of the laptop-side form validation and submit handoff. */
    public function phoneStatus(string $token)
    {
        $session = $this->findByToken($token);
        abort_unless($session, 404, 'Sesi scanner tidak ditemukan.');

        $expired = now()->gte($session->expires_at);
        return response()->json([
            'active' => $session->status === 'active' && !$expired,
            'status' => $expired && $session->status === 'active' ? 'expired' : $session->status,
            'sequence' => (int) $session->sequence,
            'submit_result' => $session->submit_result,
            'submit_message' => $session->submit_message,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /** Laptop reports whether native form validation passed before submitting the existing transaction form. */
    public function reportSubmitResult(Request $request, string $token)
    {
        $data = $request->validate([
            'result' => ['required', 'in:validation_error,dispatched'],
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        $session = DB::table('remote_scan_sessions')
            ->where('user_id', $request->user()->user_id)
            ->where('token_hash', hash('sha256', $token))
            ->where('status', 'active')
            ->first();

        abort_unless($session, 404, 'Sesi scanner tidak ditemukan atau sudah ditutup.');

        $updates = [
            'submit_result' => $data['result'],
            'submit_message' => $data['message'] ?? null,
            'updated_at' => now(),
        ];
        if ($data['result'] === 'dispatched') {
            $updates['status'] = 'completed';
        }

        DB::table('remote_scan_sessions')
            ->where('scan_session_id', $session->scan_session_id)
            ->update($updates);

        return response()->json(['ok' => true]);
    }

    /** Laptop polls this endpoint and consumes all queued scans plus one phone submit request. */
    public function poll(Request $request, string $token)
    {
        return DB::transaction(function () use ($request, $token) {
            $session = DB::table('remote_scan_sessions')
                ->where('user_id', $request->user()->user_id)
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            abort_unless($session, 404, 'Sesi scanner tidak ditemukan.');

            $isExpired = now()->gte($session->expires_at);
            if ($isExpired && $session->status === 'active') {
                DB::table('remote_scan_sessions')
                    ->where('scan_session_id', $session->scan_session_id)
                    ->update(['status' => 'expired', 'updated_at' => now()]);
                $session->status = 'expired';
            }

            $events = collect();
            if (!$isExpired && $session->status === 'active') {
                $events = DB::table('remote_scan_events')
                    ->where('scan_session_id', $session->scan_session_id)
                    ->whereNull('consumed_at')
                    ->orderBy('scan_event_id')
                    ->get();

                if ($events->isNotEmpty()) {
                    DB::table('remote_scan_events')
                        ->whereIn('scan_event_id', $events->pluck('scan_event_id'))
                        ->whereNull('consumed_at')
                        ->update(['consumed_at' => now()]);

                    DB::table('remote_scan_sessions')
                        ->where('scan_session_id', $session->scan_session_id)
                        ->update([
                            'consumed_sequence' => $events->max('sequence'),
                            'updated_at' => now(),
                        ]);
                }
            }

            $submitRequested = false;
            if (!$isExpired && $session->status === 'active'
                && $session->submit_result === 'pending'
                && (int) $session->submit_request_seq > (int) $session->submit_handled_seq) {
                $submitRequested = true;
                DB::table('remote_scan_sessions')
                    ->where('scan_session_id', $session->scan_session_id)
                    ->update([
                        'submit_handled_seq' => $session->submit_request_seq,
                        'updated_at' => now(),
                    ]);
            }

            $status = $isExpired && $session->status === 'active' ? 'expired' : $session->status;
            return response()->json([
                'barcodes' => $events->map(fn ($event) => [
                    'barcode' => $event->barcode,
                    'sequence' => (int) $event->sequence,
                ])->values(),
                'barcode' => $events->first()?->barcode,
                'sequence' => (int) ($events->last()?->sequence ?? $session->sequence),
                'submit_requested' => $submitRequested,
                'active' => $status === 'active',
                'status' => $status,
                'expires_at' => $session->expires_at,
            ])->header('Cache-Control', 'no-store');
        });
    }

    /** Close a paired session when the laptop user closes the modal. */
    public function close(Request $request, string $token)
    {
        DB::table('remote_scan_sessions')
            ->where('user_id', $request->user()->user_id)
            ->where('token_hash', hash('sha256', $token))
            ->where('status', 'active')
            ->update(['status' => 'closed', 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function findByToken(string $token): ?object
    {
        return DB::table('remote_scan_sessions')
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }
}
