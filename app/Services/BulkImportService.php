<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BulkImport;
use App\Models\MemberPass;
use App\Models\ParkingLocation;
use App\Models\ParkingSlot;
use App\Models\ParkingZone;
use App\Models\Tariff;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BulkImportService
{
    /**
     * Process CSV bulk import based on type
     */
    public function import(string $type, UploadedFile $file, int $userId): array
    {
        $rows = $this->parseCsv($file);
        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'The uploaded CSV file is empty or could not be parsed.',
                'total' => 0,
                'successful' => 0,
                'failed' => 0,
                'errors' => ['Empty file'],
            ];
        }

        return match ($type) {
            'slots' => $this->importSlots($rows, $file->getClientOriginalName(), $userId),
            'members' => $this->importMembers($rows, $file->getClientOriginalName(), $userId),
            'tariffs' => $this->importTariffs($rows, $file->getClientOriginalName(), $userId),
            'vehicles' => $this->importVehicles($rows, $file->getClientOriginalName(), $userId),
            default => throw new \InvalidArgumentException("Unsupported import type: {$type}"),
        };
    }

    /**
     * Parse CSV file into an array of associative rows
     */
    protected function parseCsv(UploadedFile $file): array
    {
        $csvData = [];
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [];
        }

        // Detect BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle, 2000, ',');
        if (!$headers) {
            fclose($handle);
            return [];
        }

        // Clean headers: lowercase, trim, remove invisible chars
        $cleanHeaders = array_map(function ($h) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
        }, $headers);

        while (($row = fgetcsv($handle, 2000, ',')) !== false) {
            if (count($row) === 1 && empty($row[0])) {
                continue; // Skip blank lines
            }

            $rowData = [];
            foreach ($cleanHeaders as $index => $header) {
                $rowData[$header] = isset($row[$index]) ? trim($row[$index]) : null;
            }
            $csvData[] = $rowData;
        }

        fclose($handle);
        return $csvData;
    }

    /**
     * Import Parking Slots
     */
    protected function importSlots(array $rows, string $filename, int $userId): array
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // account for header & 1-index

                $validator = Validator::make($row, [
                    'zone_code' => 'required|string',
                    'slot_number' => 'required|string|max:50',
                    'vehicle_type' => 'nullable|string|in:car,motorcycle,suv,ev,truck,bus',
                    'notes' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row {$rowNum}: " . implode(', ', $validator->errors()->all());
                    $failed++;
                    continue;
                }

                $zone = ParkingZone::where('code', $row['zone_code'])->first();
                if (!$zone) {
                    $errors[] = "Row {$rowNum}: Zone with code '{$row['zone_code']}' not found.";
                    $failed++;
                    continue;
                }

                $slotNumber = strtoupper(trim($row['slot_number']));
                $existing = ParkingSlot::where('parking_zone_id', $zone->id)
                    ->where('slot_number', $slotNumber)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'vehicle_type' => $row['vehicle_type'] ?? 'car',
                        'notes' => $row['notes'] ?? $existing->notes,
                    ]);
                } else {
                    ParkingSlot::create([
                        'parking_zone_id' => $zone->id,
                        'slot_number' => $slotNumber,
                        'vehicle_type' => $row['vehicle_type'] ?? 'car',
                        'status' => 'available',
                        'notes' => $row['notes'] ?? null,
                        'is_active' => true,
                    ]);
                    $zone->increment('total_slots');
                    $zone->increment('available_slots');
                }

                $successful++;
            }

            $bulkLog = BulkImport::create([
                'user_id' => $userId,
                'import_type' => 'slots',
                'filename' => $filename,
                'total_rows' => count($rows),
                'successful_rows' => $successful,
                'failed_rows' => $failed,
                'status' => $failed === 0 ? 'completed' : ($successful > 0 ? 'partial' : 'failed'),
                'error_details' => $errors,
            ]);

            AuditLog::log('BULK_IMPORT', 'SLOTS', "Imported {$successful} parking slots from {$filename}", (string) $bulkLog->id);

            DB::commit();

            return [
                'success' => true,
                'total' => count($rows),
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors,
                'message' => "Successfully imported {$successful} slots. {$failed} failed.",
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'total' => count($rows),
                'successful' => 0,
                'failed' => count($rows),
                'errors' => [$e->getMessage()],
                'message' => 'An error occurred during slot import: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Import Member Passes
     */
    protected function importMembers(array $rows, string $filename, int $userId): array
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                $validator = Validator::make($row, [
                    'pass_number' => 'required|string|max:50',
                    'member_name' => 'required|string|max:100',
                    'phone' => 'required|string|max:30',
                    'email' => 'nullable|email|max:100',
                    'plate_number' => 'required|string|max:30',
                    'vehicle_type_code' => 'required|string',
                    'plan_type' => 'nullable|string|in:monthly,quarterly,annual,vip_complimentary',
                    'price_paid' => 'nullable|numeric|min:0',
                    'valid_from' => 'required|date',
                    'valid_until' => 'required|date|after_or_equal:valid_from',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row {$rowNum}: " . implode(', ', $validator->errors()->all());
                    $failed++;
                    continue;
                }

                $vType = VehicleType::where('code', strtolower($row['vehicle_type_code']))->first();
                if (!$vType) {
                    $errors[] = "Row {$rowNum}: Vehicle type '{$row['vehicle_type_code']}' not found.";
                    $failed++;
                    continue;
                }

                $passNumber = strtoupper(trim($row['pass_number']));
                $plateNumber = strtoupper(trim($row['plate_number']));

                MemberPass::updateOrCreate(
                    ['pass_number' => $passNumber],
                    [
                        'rfid_uid' => !empty($row['rfid_uid']) ? trim($row['rfid_uid']) : null,
                        'member_name' => trim($row['member_name']),
                        'phone' => trim($row['phone']),
                        'email' => !empty($row['email']) ? trim($row['email']) : null,
                        'plate_number' => $plateNumber,
                        'vehicle_type_id' => $vType->id,
                        'plan_type' => $row['plan_type'] ?? 'monthly',
                        'price_paid' => $row['price_paid'] ?? 0.00,
                        'valid_from' => Carbon::parse($row['valid_from']),
                        'valid_until' => Carbon::parse($row['valid_until']),
                        'status' => 'active',
                        'notes' => $row['notes'] ?? null,
                    ]
                );

                $successful++;
            }

            $bulkLog = BulkImport::create([
                'user_id' => $userId,
                'import_type' => 'members',
                'filename' => $filename,
                'total_rows' => count($rows),
                'successful_rows' => $successful,
                'failed_rows' => $failed,
                'status' => $failed === 0 ? 'completed' : ($successful > 0 ? 'partial' : 'failed'),
                'error_details' => $errors,
            ]);

            AuditLog::log('BULK_IMPORT', 'MEMBERS', "Imported {$successful} member passes from {$filename}", (string) $bulkLog->id);

            DB::commit();

            return [
                'success' => true,
                'total' => count($rows),
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors,
                'message' => "Successfully imported {$successful} member passes. {$failed} failed.",
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'total' => count($rows),
                'successful' => 0,
                'failed' => count($rows),
                'errors' => [$e->getMessage()],
                'message' => 'An error occurred during member import: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Import Tariffs
     */
    protected function importTariffs(array $rows, string $filename, int $userId): array
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                $validator = Validator::make($row, [
                    'location_code' => 'required|string',
                    'vehicle_type_code' => 'required|string',
                    'tariff_name' => 'required|string|max:100',
                    'initial_duration_minutes' => 'nullable|integer|min:1',
                    'initial_rate' => 'required|numeric|min:0',
                    'subsequent_rate_per_hour' => 'required|numeric|min:0',
                    'max_daily_cap' => 'nullable|numeric|min:0',
                    'overnight_surcharge' => 'nullable|numeric|min:0',
                    'lost_ticket_fee' => 'nullable|numeric|min:0',
                    'grace_period_minutes' => 'nullable|integer|min:0',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row {$rowNum}: " . implode(', ', $validator->errors()->all());
                    $failed++;
                    continue;
                }

                $loc = ParkingLocation::where('code', $row['location_code'])->first();
                if (!$loc) {
                    $errors[] = "Row {$rowNum}: Location code '{$row['location_code']}' not found.";
                    $failed++;
                    continue;
                }

                $vType = VehicleType::where('code', strtolower($row['vehicle_type_code']))->first();
                if (!$vType) {
                    $errors[] = "Row {$rowNum}: Vehicle type '{$row['vehicle_type_code']}' not found.";
                    $failed++;
                    continue;
                }

                Tariff::create([
                    'parking_location_id' => $loc->id,
                    'vehicle_type_id' => $vType->id,
                    'name' => trim($row['tariff_name']),
                    'tariff_type' => $row['tariff_type'] ?? 'progressive',
                    'initial_duration_minutes' => $row['initial_duration_minutes'] ?? 60,
                    'initial_rate' => $row['initial_rate'],
                    'subsequent_rate_per_hour' => $row['subsequent_rate_per_hour'],
                    'max_daily_cap' => $row['max_daily_cap'] ?? 40.00,
                    'overnight_surcharge' => $row['overnight_surcharge'] ?? 15.00,
                    'lost_ticket_fee' => $row['lost_ticket_fee'] ?? 50.00,
                    'grace_period_minutes' => $row['grace_period_minutes'] ?? 15,
                    'is_active' => true,
                ]);

                $successful++;
            }

            $bulkLog = BulkImport::create([
                'user_id' => $userId,
                'import_type' => 'tariffs',
                'filename' => $filename,
                'total_rows' => count($rows),
                'successful_rows' => $successful,
                'failed_rows' => $failed,
                'status' => $failed === 0 ? 'completed' : ($successful > 0 ? 'partial' : 'failed'),
                'error_details' => $errors,
            ]);

            AuditLog::log('BULK_IMPORT', 'TARIFFS', "Imported {$successful} tariff rates from {$filename}", (string) $bulkLog->id);

            DB::commit();

            return [
                'success' => true,
                'total' => count($rows),
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors,
                'message' => "Successfully imported {$successful} tariffs. {$failed} failed.",
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'total' => count($rows),
                'successful' => 0,
                'failed' => count($rows),
                'errors' => [$e->getMessage()],
                'message' => 'An error occurred during tariff import: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Import Registered / Whitelisted Vehicles
     */
    protected function importVehicles(array $rows, string $filename, int $userId): array
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                $validator = Validator::make($row, [
                    'plate_number' => 'required|string|max:30',
                    'vehicle_type_code' => 'required|string',
                    'owner_name' => 'required|string|max:100',
                    'notes' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row {$rowNum}: " . implode(', ', $validator->errors()->all());
                    $failed++;
                    continue;
                }

                $vType = VehicleType::where('code', strtolower($row['vehicle_type_code']))->first();
                if (!$vType) {
                    $errors[] = "Row {$rowNum}: Vehicle type '{$row['vehicle_type_code']}' not found.";
                    $failed++;
                    continue;
                }

                $plateNumber = strtoupper(trim($row['plate_number']));
                $passNumber = 'REG-' . preg_replace('/[^A-Z0-9]/', '', $plateNumber);

                MemberPass::updateOrCreate(
                    ['plate_number' => $plateNumber],
                    [
                        'pass_number' => $passNumber,
                        'member_name' => trim($row['owner_name']),
                        'phone' => 'N/A',
                        'vehicle_type_id' => $vType->id,
                        'plan_type' => 'vip_complimentary',
                        'price_paid' => 0.00,
                        'valid_from' => Carbon::today(),
                        'valid_until' => Carbon::today()->addYears(2),
                        'status' => 'active',
                        'notes' => 'Registered/Whitelisted: ' . ($row['notes'] ?? ''),
                    ]
                );

                $successful++;
            }

            $bulkLog = BulkImport::create([
                'user_id' => $userId,
                'import_type' => 'vehicles',
                'filename' => $filename,
                'total_rows' => count($rows),
                'successful_rows' => $successful,
                'failed_rows' => $failed,
                'status' => $failed === 0 ? 'completed' : ($successful > 0 ? 'partial' : 'failed'),
                'error_details' => $errors,
            ]);

            AuditLog::log('BULK_IMPORT', 'VEHICLES', "Imported {$successful} registered vehicles from {$filename}", (string) $bulkLog->id);

            DB::commit();

            return [
                'success' => true,
                'total' => count($rows),
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors,
                'message' => "Successfully imported {$successful} registered vehicles. {$failed} failed.",
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'total' => count($rows),
                'successful' => 0,
                'failed' => count($rows),
                'errors' => [$e->getMessage()],
                'message' => 'An error occurred during vehicle import: ' . $e->getMessage(),
            ];
        }
    }
}
