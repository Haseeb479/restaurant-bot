<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Serves private files that must NOT be publicly accessible.
 *
 * Currently handles:
 *   - Payment slips (formerly stored under public/uploads/payments)
 *
 * All routes in this controller MUST be protected by admin auth.
 * Do NOT register these routes without checking session('admin_logged_in').
 */
class StorageController extends Controller
{
    /**
     * Stream a payment slip file to the Super Admin.
     *
     * GET /admin/storage/payment-slip/{path}
     *
     * Only Super Admins may download payment slips — they contain
     * bank account numbers, transaction IDs, and personal financial data.
     */
    public function paymentSlip(Request $request, string $filename)
    {
        // Must be authenticated as Super Admin
        abort_unless(session('admin_logged_in') === true, 403, 'Admin access required to view payment slips.');

        // Sanitise the filename — prevent directory traversal
        $filename = basename($filename);
        $path     = 'payment_slips/' . $filename;

        if (! Storage::disk('private')->exists($path)) {
            abort(404, 'Payment slip not found.');
        }

        $mime = Storage::disk('private')->mimeType($path) ?: 'application/octet-stream';

        // Stream the file with safe headers:
        // - Content-Disposition: attachment forces download rather than inline render (prevents stored-XSS via SVG/HTML)
        // - X-Content-Type-Options: nosniff prevents MIME-sniffing
        return response()->streamDownload(
            fn () => print(Storage::disk('private')->get($path)),
            $filename,
            [
                'Content-Type'              => $mime,
                'Content-Disposition'       => 'attachment; filename="' . $filename . '"',
                'X-Content-Type-Options'    => 'nosniff',
                'Cache-Control'             => 'no-store, private',
            ]
        );
    }
}
