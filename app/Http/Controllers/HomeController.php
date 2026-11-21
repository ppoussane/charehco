<?php

namespace App\Http\Controllers;

use App\Support\HomePage;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home', HomePage::viewData(HomePage::published()));
    }
}
