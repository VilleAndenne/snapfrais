<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
{
    $forms = Form::all();

    //

    return inertia('Dashboard', [
        'forms' => $forms,
    ]);
}
}
