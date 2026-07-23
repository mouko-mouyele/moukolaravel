<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Vehicle;
use App\Services\DocumentService;
use App\Services\IpfsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private IpfsService $ipfs
    ) {
    }

    public function index(Vehicle $vehicle): JsonResponse
    {
        $documents = $vehicle->documents()
            ->with('uploadedBy:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Document $doc) {
                $data = $doc->toArray();
                $data['ipfs_url'] = $this->ipfs->publicUrl($doc->ipfs_cid);

                return $data;
            });

        return response()->json(['documents' => $documents]);
    }

    public function store(StoreDocumentRequest $request, Vehicle $vehicle): JsonResponse
    {
        $stored = $this->documentService->store($request->file('file'), $vehicle->id);
        $isPublic = $request->boolean('is_public');
        $ipfsCid = null;

        if ($isPublic || $request->type === 'technical_inspection') {
            $ipfsCid = $this->ipfs->pinFile($stored['file_path']);
            $isPublic = true;
        }

        $document = Document::create([
            'vehicle_id' => $vehicle->id,
            'uploaded_by' => $request->user()->id,
            'type' => $request->type,
            'title' => $request->title,
            'expiry_date' => $request->expiry_date,
            'is_public' => $isPublic,
            'ipfs_cid' => $ipfsCid,
            ...$stored,
        ]);

        return response()->json([
            'message' => 'Document enregistré.'.($ipfsCid ? ' Publié sur IPFS.' : ''),
            'document' => $document,
            'ipfs_url' => $this->ipfs->publicUrl($ipfsCid),
        ], 201);
    }

    public function show(Document $document): JsonResponse
    {
        $integrityOk = $this->documentService->verifyIntegrity($document->file_path, $document->file_hash);

        return response()->json([
            'document' => $document->load(['vehicle:id,license_plate', 'uploadedBy:id,name']),
            'integrity_verified' => $integrityOk,
            'ipfs_url' => $this->ipfs->publicUrl($document->ipfs_cid),
        ]);
    }

    public function download(Document $document): StreamedResponse|JsonResponse
    {
        $disk = config('autochain.documents.disk', 'documents');

        if (! Storage::disk($disk)->exists($document->file_path)) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        if (! $this->documentService->verifyIntegrity($document->file_path, $document->file_hash)) {
            return response()->json(['message' => 'Intégrité du document compromise.'], 422);
        }

        return Storage::disk($disk)->download($document->file_path, $document->title);
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->documentService->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document supprimé.']);
    }
}
