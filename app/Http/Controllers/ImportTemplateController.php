<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\ExcelTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportTemplateController extends Controller
{
    public function __invoke(Request $request, string $type, ExcelTemplateService $templates): BinaryFileResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/admin/login');
        }

        abort_unless($user->is_aktif && $user->role === UserRole::Admin, 403);

        return $templates->download($type);
    }
}
