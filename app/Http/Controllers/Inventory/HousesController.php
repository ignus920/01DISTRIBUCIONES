<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HousesController extends Controller
{
    public function homeHouses() {
        return view('inventory.house');
    }
}
