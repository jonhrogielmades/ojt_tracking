<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWeeklyTargetRequest;
use App\Models\DtrEntry;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $profile = StudentProfile::query()->firstOrNew(['user_id' => $user->id]);
        $completedMinutes = (int) DtrEntry::query()->whereBelongsTo($user)->sum('total_minutes');
        $requiredMinutes = (int) $profile->required_minutes;
        $remainingMinutes = max(0, $requiredMinutes - $completedMinutes);
        $weeklyTargetMinutes = max(60, (int) ($profile->weekly_target_minutes ?: 2400));
        $currentWeekMinutes = (int) DtrEntry::query()
            ->whereBelongsTo($user)
            ->whereBetween('work_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->sum('total_minutes');
        $weeksRemaining = $remainingMinutes > 0 ? (int) ceil($remainingMinutes / $weeklyTargetMinutes) : 0;
        $forecastDate = $remainingMinutes > 0
            ? CarbonImmutable::now()->startOfWeek()->addWeeks(max(0, $weeksRemaining - 1))->endOfWeek()
            : CarbonImmutable::now();

        return view('student.progress', compact(
            'profile',
            'completedMinutes',
            'requiredMinutes',
            'remainingMinutes',
            'weeklyTargetMinutes',
            'currentWeekMinutes',
            'weeksRemaining',
            'forecastDate',
        ));
    }

    public function update(UpdateWeeklyTargetRequest $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        StudentProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['weekly_target_minutes' => (int) round((float) $request->validated('weekly_target_hours') * 60)],
        );

        return to_route('student.progress')->with('success', 'Your weekly target was updated.');
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
