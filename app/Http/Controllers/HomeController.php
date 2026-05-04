<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function login(){
        return view('login');
    }
    public function register(){
        return view('register');
    }

    public function drivers(){
        return view('driver-list');
    }

    public function operation(){
        return view('operation-list');
    }

    public function addUser(){
        return view('add-operator');
    }

    public function profile(){
        return view('admin-profile');
    }
    public function updateUser(){
        return view('update-operator');
    }
    public function attendanceLogin(){
        return view('attendance-login');
    }

    public function attendanceDashboard(){
        return view('attendance');
    }

    public function debtDashboard(){
        return view('debt-list');
    }

     public function debtLogin(){
        return view('debt-login');
    }

    public function debtTest(){
        return view('debt-test');
    }

}
