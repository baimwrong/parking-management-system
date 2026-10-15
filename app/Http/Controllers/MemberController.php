<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MemberPass;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');

        $members = MemberPass::with('vehicleType')
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $term = strtoupper(trim($search));
                $q->where('pass_number', 'LIKE', "%{$term}%")
                    ->orWhere('plate_number', 'LIKE', "%{$term}%")
                    ->orWhere('member_name', 'LIKE', "%{$term}%")
                    ->orWhere('rfid_uid', 'LIKE', "%{$term}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $vehicleTypes = VehicleType::where('is_active', true)->get();

        return view('members.index', compact('members', 'status', 'search', 'vehicleTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'member_name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:100',
            'plate_number' => 'required|string|max:30',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'plan_type' => 'required|string|in:monthly,quarterly,annual,vip_complimentary',
            'rfid_uid' => 'nullable|string|max:50|unique:member_passes,rfid_uid',
            'price_paid' => 'nullable|numeric|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
            'notes' => 'nullable|string|max:500',
        ]);

        $plateNumber = strtoupper(trim($request->plate_number));
        $passNumber = 'MEM-' . date('Y') . '-' . strtoupper(Str::random(6));

        $member = MemberPass::create([
            'pass_number' => $passNumber,
            'rfid_uid' => $request->filled('rfid_uid') ? trim($request->rfid_uid) : null,
            'member_name' => trim($request->member_name),
            'phone' => trim($request->phone),
            'email' => $request->email,
            'plate_number' => $plateNumber,
            'vehicle_type_id' => $request->vehicle_type_id,
            'plan_type' => $request->plan_type,
            'price_paid' => $request->price_paid ?? 0.00,
            'valid_from' => Carbon::parse($request->valid_from),
            'valid_until' => Carbon::parse($request->valid_until),
            'status' => 'active',
            'notes' => $request->notes,
        ]);

        AuditLog::log('CREATE_MEMBER', 'MEMBERS', "Created member pass {$passNumber} for {$member->member_name}", (string) $member->id);

        return back()->with('success', "Member Pass {$passNumber} for {$member->member_name} created successfully.");
    }

    public function renew(Request $request, $id)
    {
        $request->validate([
            'duration_months' => 'required|integer|in:1,3,6,12',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        $member = MemberPass::findOrFail($id);
        $oldExpiry = $member->valid_until->isPast() ? Carbon::today() : $member->valid_until;
        $newExpiry = $oldExpiry->copy()->addMonths((int) $request->duration_months);

        $member->valid_until = $newExpiry;
        $member->status = 'active';
        $member->price_paid += (float) $request->amount_paid;
        $member->notes = ($member->notes ? $member->notes . " | " : "") . "Renewed on " . date('Y-m-d') . " for {$request->duration_months} mo";
        $member->save();

        AuditLog::log('RENEW_MEMBER', 'MEMBERS', "Renewed member pass {$member->pass_number} until {$newExpiry->toDateString()}", (string) $member->id);

        return back()->with('success', "Member Pass {$member->pass_number} successfully renewed until {$newExpiry->format('M d, Y')}.");
    }

    public function toggleStatus($id)
    {
        $member = MemberPass::findOrFail($id);
        $member->status = $member->status === 'active' ? 'suspended' : 'active';
        $member->save();

        AuditLog::log('STATUS_CHANGE_MEMBER', 'MEMBERS', "Pass {$member->pass_number} status set to {$member->status}", (string) $member->id);

        return back()->with('success', "Pass {$member->pass_number} status updated to {$member->status}.");
    }
}
