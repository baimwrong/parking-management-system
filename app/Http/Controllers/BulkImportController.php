<?php

namespace App\Http\Controllers;

use App\Models\BulkImport;
use App\Services\BulkImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BulkImportController extends Controller
{
    protected BulkImportService $importService;

    public function __construct(BulkImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        $recentImports = BulkImport::with('user')
            ->latest()
            ->paginate(15);

        return view('admin.bulk_import', compact('recentImports'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'import_type' => 'required|string|in:slots,members,tariffs,vehicles',
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // max 5MB
        ]);

        $type = $request->import_type;
        $file = $request->file('csv_file');

        $result = $this->importService->import($type, $file, Auth::id());

        if (!$result['success'] && empty($result['successful'])) {
            return back()->with('error', $result['message'])
                ->with('import_errors', $result['errors'] ?? []);
        }

        $msgType = ($result['failed'] > 0) ? 'warning' : 'success';

        return back()->with($msgType, $result['message'])
            ->with('import_result', $result);
    }
}
