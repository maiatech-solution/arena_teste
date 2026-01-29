<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyInfo;

class ModuleController extends Controller
{
    public function index()
    {
        $company = CompanyInfo::first();

        // 1. Se não tiver empresa cadastrada, manda cadastrar primeiro
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('admin.company.edit');
        }

        // 2. Se já escolheu o módulo, vai para o dashboard
        if ($company->modules_active > 0) {
            return $company->modules_active == 2
                ? redirect()->route('bar.dashboard')
                : redirect()->route('dashboard');
        }

        return view('admin.select_modules');
    }

    public function store(Request $request)
    {
        $request->validate(['module' => 'required|in:1,2,3']);

        $company = CompanyInfo::first();
        if ($company) {
            $company->update(['modules_active' => $request->module]);
        }

        return $request->module == 2
            ? redirect()->route('bar.dashboard')
            : redirect()->route('dashboard');
    }
}
