<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CommandsController extends Controller
{
    public function homeCommands() {
        return view('inventory.commands');
    }
}
