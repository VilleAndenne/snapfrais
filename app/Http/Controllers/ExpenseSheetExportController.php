<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseSheetExportController extends Controller
{
    public function index() {
        return Inertia::render('expenseSheet/Export/Index');
    }
}
