<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientContract;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientContractController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, Client $client)
    {
        $this->authorize('view', $client);

        $contracts = $client->contracts()
            ->where('user_id', $request->user()->id)
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($contracts);
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif',
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');
        $userId = $request->user()->id;

        // Generate unique filename
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = "contracts/{$userId}/" . $filename;

        // Store the file
        Storage::disk('public')->put($path, file_get_contents($file));

        // Create contract record
        ClientContract::create([
            'client_id' => $client->id,
            'user_id' => $userId,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'title' => $request->title ?? $file->getClientOriginalName(),
            'description' => $request->description,
            'uploaded_at' => now(),
        ]);

        return back()->with('success', __('toast.contract_uploaded'));
    }

    public function download(ClientContract $contract): StreamedResponse
    {
        $this->authorize('view', $contract);

        if (!Storage::disk('public')->exists($contract->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $contract->file_path,
            $contract->original_name
        );
    }

    public function destroy(ClientContract $contract): RedirectResponse
    {
        $this->authorize('delete', $contract);

        // Delete the file
        if (Storage::disk('public')->exists($contract->file_path)) {
            Storage::disk('public')->delete($contract->file_path);
        }

        $contract->delete();

        return back()->with('success', __('toast.contract_deleted'));
    }
}
