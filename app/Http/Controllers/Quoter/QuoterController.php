<?php

namespace App\Http\Controllers\Quoter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class QuoterController extends Controller
{
    public function index(Request $request)
    {
        $agent = new Agent();

        // FORZAR TABLET A MOBILE: Para que tenga soporte Offline PWA
        if ($agent->isMobile() || $agent->isTablet()) {
            return redirect()->route('tenant.quoter.mobile');
        }

        // Desktop - redirigir a la ruta desktop
        return redirect()->route('tenant.quoter.desktop');
    }

    public function mobile(Request $request)
    {
        return view('livewire.tenant.quoter.quoter-mobile');
    }

    public function desktop(Request $request)
    {
        // Fallback de seguridad: Si una tablet entra aquí directo, redirigirla
        $agent = new Agent();
        if ($agent->isTablet()) {
            return redirect()->route('tenant.quoter.mobile');
        }
        return view('livewire.tenant.quoter.quoter-desktop');
    }

    public function products(Request $request)
    {
        $agent = new Agent();

        // FORZAR TABLET A MOBILE
        if ($agent->isMobile() || $agent->isTablet()) {
            return redirect()->route('tenant.quoter.products.mobile', $request->all());
        }

        // Desktop - redirigir a la ruta desktop
        return redirect()->route('tenant.quoter.products.desktop', $request->all());
    }
}