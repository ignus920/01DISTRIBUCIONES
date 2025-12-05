<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nnjeim\World\World;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;
use Nnjeim\World\Models\City;

class WorldController extends Controller
{
    /**
     * Obtener todos los países
     */
    public function getCountries()
    {
        $action = World::countries();

        if ($action->success) {
            return response()->json([
                'success' => true,
                'data' => $action->data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error obteniendo países'
        ], 500);
    }

    /**
     * Obtener estados por país
     */
    public function getStates($countryId)
    {
        $action = World::states([
            'filters' => [
                'country_id' => $countryId,
            ],
        ]);

        if ($action->success) {
            return response()->json([
                'success' => true,
                'data' => $action->data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error obteniendo estados'
        ], 500);
    }

    /**
     * Obtener ciudades por estado
     */
    public function getCities($stateId)
    {
        $action = World::cities([
            'filters' => [
                'state_id' => $stateId,
            ],
        ]);

        if ($action->success) {
            return response()->json([
                'success' => true,
                'data' => $action->data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error obteniendo ciudades'
        ], 500);
    }

    /**
     * Obtener información completa de un país con estados y ciudades
     */
    public function getCountryComplete($countryCode)
    {
        $action = World::countries([
            'fields' => 'states,cities',
            'filters' => [
                'iso2' => $countryCode,
            ]
        ]);

        if ($action->success) {
            return response()->json([
                'success' => true,
                'data' => $action->data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error obteniendo información del país'
        ], 500);
    }

    /**
     * Buscar países por nombre
     */
    public function searchCountries(Request $request)
    {
        $search = $request->get('search');

        $action = World::countries([
            'search' => $search
        ]);

        if ($action->success) {
            return response()->json([
                'success' => true,
                'data' => $action->data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error buscando países'
        ], 500);
    }
}