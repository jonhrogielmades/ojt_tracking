<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\AcademicTerm;
use App\Models\PartnerCompany;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $profile = StudentProfile::query()->firstOrNew(['user_id' => $user->id]);
        $academicTerm = AcademicTerm::query()->active()->orderByDesc('starts_on')->orderByDesc('id')->first();
        $partnerCompanies = PartnerCompany::query()
            ->where(function ($query) use ($profile): void {
                $query->where('is_active', true);

                if ($profile->partner_company_id !== null) {
                    $query->orWhereKey($profile->partner_company_id);
                }
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return view('profile.edit', [
            'profile' => $profile,
            'academicTerm' => $academicTerm,
            'partnerCompanies' => $partnerCompanies,
            'weekDays' => [
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                7 => 'Sunday',
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validated();
        $workDays = collect($validated['work_days'] ?? [])
            ->map(static fn (mixed $day): int => (int) $day)
            ->sort()
            ->values()
            ->all();
        $partnerCompany = isset($validated['partner_company_id'])
            ? PartnerCompany::query()->find($validated['partner_company_id'])
            : null;
        $academicTerm = $request->academicTerm();

        DB::transaction(function () use ($user, $validated, $workDays, $partnerCompany, $academicTerm): void {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'student_id' => $validated['student_id'],
                'program' => $validated['program'],
                'section' => $validated['section'],
            ]);

            StudentProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'partner_company_id' => $partnerCompany?->id,
                    'company_name' => $validated['company_name'] ?? $partnerCompany?->name,
                    'department' => $validated['department'] ?? null,
                    'company_address' => $validated['company_address'] ?? $partnerCompany?->address,
                    'supervisor_name' => $validated['supervisor_name'] ?? null,
                    'supervisor_email' => $validated['supervisor_email'] ?? null,
                    'supervisor_phone' => $validated['supervisor_phone'] ?? null,
                    'ojt_start_date' => $validated['ojt_start_date'] ?? $academicTerm?->starts_on,
                    'ojt_end_date' => $validated['ojt_end_date'] ?? $academicTerm?->ends_on,
                    'work_days' => $workDays,
                    'scheduled_time_in' => $validated['scheduled_time_in'] ?? null,
                    'scheduled_time_out' => $validated['scheduled_time_out'] ?? null,
                    'schedule_notes' => $validated['schedule_notes'] ?? null,
                    'required_minutes' => isset($validated['required_hours'])
                        ? (int) round((float) $validated['required_hours'] * 60)
                        : ($academicTerm?->required_minutes ?? 0),
                ],
            );
        });

        return redirect()->route('profile.edit')->with('status', 'Your student and OJT profile has been updated.');
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
