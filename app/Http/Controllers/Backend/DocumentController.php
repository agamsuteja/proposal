<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\NotaPenggunaanProposal;
use App\Models\PencairanDana;
use App\Models\PengembalianDana;
use App\Models\Proposal;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function show(Request $request, string $path)
    {
        abort_if(str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/'), 404);
        $proposal = Proposal::where('lampiran', $path)->first();
        foreach ([[PencairanDana::class, 'bukti_transfer'], [PengembalianDana::class, 'bukti'], [NotaPenggunaanProposal::class, 'file_nota']] as [$class,$field]) {
            if (! $proposal) {
                $proposal = $class::where($field, $path)->first()?->proposal;
            }
        }
        abort_unless($proposal, 404);
        Access::viewProposal($request->user(), $proposal);
        $disk = Storage::disk('local')->exists($path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, basename($path), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
