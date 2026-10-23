<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\WordTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WordTemplateController extends Controller
{
    public function __invoke(Request $request, WordTemplateService $templates): BinaryFileResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/admin/login');
        }

        abort_unless($user->is_aktif && in_array($user->role, [UserRole::Admin, UserRole::Guru], true), 403);

        return $templates->download();
    }
}
