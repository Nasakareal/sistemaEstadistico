<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThermalPrinter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThermalPrinterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $printers = ThermalPrinter::query()
            ->when(!$user->isSuperadmin(), fn ($query) => $query->where('active', true))
            ->orderByDesc('active')
            ->orderBy('display_name')
            ->get()
            ->map(fn (ThermalPrinter $printer) => $this->payload($printer))
            ->values();

        return response()->json(['data' => $printers]);
    }

    public function store(Request $request)
    {
        $user = $this->authorizeSuperadmin($request);
        $validated = $this->validated($request);

        $printer = ThermalPrinter::query()->create([
            ...$validated,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Impresora registrada.',
            'data' => $this->payload($printer),
        ], 201);
    }

    public function update(Request $request, ThermalPrinter $thermalPrinter)
    {
        $user = $this->authorizeSuperadmin($request);
        $thermalPrinter->fill($this->validated($request, $thermalPrinter));
        $thermalPrinter->updated_by = $user->id;
        $thermalPrinter->save();

        return response()->json([
            'message' => 'Impresora actualizada.',
            'data' => $this->payload($thermalPrinter->fresh()),
        ]);
    }

    public function destroy(Request $request, ThermalPrinter $thermalPrinter)
    {
        $this->authorizeSuperadmin($request);
        $thermalPrinter->delete();

        return response()->json(['message' => 'Impresora eliminada.']);
    }

    private function validated(Request $request, ?ThermalPrinter $printer = null): array
    {
        $request->merge([
            'display_name' => trim((string) $request->input('display_name')),
            'mac_address' => ThermalPrinter::normalizeMac((string) $request->input('mac_address')),
            'hardware_name' => trim((string) $request->input('hardware_name')) ?: null,
        ]);

        $id = $printer?->id;
        $validated = $request->validate([
            'display_name' => [
                'required',
                'string',
                'max:80',
                'regex:/^[\pL\pN][\pL\pN _.-]*$/u',
                Rule::unique('thermal_printers', 'display_name')->ignore($id),
            ],
            'mac_address' => [
                'required',
                'string',
                'regex:/^[0-9A-F]{2}(?::[0-9A-F]{2}){5}$/',
                Rule::unique('thermal_printers', 'mac_address')->ignore($id),
            ],
            'hardware_name' => ['nullable', 'string', 'max:80'],
            'active' => ['required', 'boolean'],
        ], [
            'display_name.unique' => 'Ese nombre ya está asignado a otra impresora.',
            'display_name.regex' => 'El nombre contiene caracteres no permitidos.',
            'mac_address.regex' => 'La dirección MAC no tiene un formato válido.',
            'mac_address.unique' => 'Esa dirección MAC ya está registrada.',
        ]);

        return [
            'display_name' => $validated['display_name'],
            'mac_address' => $validated['mac_address'],
            'hardware_name' => $validated['hardware_name'] ?? null,
            'active' => (bool) $validated['active'],
        ];
    }

    private function authorizeSuperadmin(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user && $user->isSuperadmin(),
            403,
            'Solo Superadmin puede administrar el catálogo de impresoras.'
        );

        return $user;
    }

    private function payload(ThermalPrinter $printer): array
    {
        return [
            'id' => $printer->id,
            'display_name' => $printer->display_name,
            'mac_address' => $printer->mac_address,
            'hardware_name' => $printer->hardware_name,
            'active' => (bool) $printer->active,
            'created_at' => optional($printer->created_at)->toISOString(),
            'updated_at' => optional($printer->updated_at)->toISOString(),
        ];
    }
}
