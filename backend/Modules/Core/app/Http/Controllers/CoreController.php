<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CoreController extends Controller
{
    public function index()
    {
        return view('core::index');
    }

    public function create()
    {
        return view('core::create');
    }

    public function store(Request $request) {}

    public function show($id)
    {
        return view('core::show');
    }

    public function edit($id)
    {
        return view('core::edit');
    }

    public function update(Request $request, $id) {}

    public function destroy($id) {}
}
