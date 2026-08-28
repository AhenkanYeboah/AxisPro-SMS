<?php
namespace App\Http\Controllers;
use Illuminate\View\View;
class HomeController extends Controller
{
    public function index(): View
    {
        if (!app()->bound('currentSchool')) {
            return view('platform.home');
        }
        $school = app('currentSchool');
        if ($school->subdomain === 'royalcountrysideacademy') {
            return view('home');
        }
        return view('home-generic', ['school' => $school]);
    }
}
