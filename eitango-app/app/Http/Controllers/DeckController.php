<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeckController extends Controller
{
    public function index() 
    {
        return view('decks.index');
    }
}
