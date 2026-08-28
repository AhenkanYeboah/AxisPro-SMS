<?php
namespace App\Http\Controllers;

class AdminStudentController extends Controller
{
    public function dashboard()
    {
        return response('<h1 style="color:green;font-family:sans-serif;padding:50px">✅ CONTROLLER WORKS - NO DB</h1>', 200);
    }
    public function show($s){ return response('show ok'); }
    public function setExamDate($r,$s){ return redirect('/admin/dashboard'); }
    public function markExamCompleted($s){ return redirect('/admin/dashboard'); }
    public function verify($s){ return redirect('/admin/dashboard'); }
    public function decline($s){ return redirect('/admin/dashboard'); }
    public function destroy($s){ return redirect('/admin/dashboard'); }
}
