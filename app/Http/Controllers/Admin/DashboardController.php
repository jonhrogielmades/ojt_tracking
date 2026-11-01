<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DtrEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        $studentCount = User::query()->students()->count();
        $activeStudentCount = User::query()->students()->where('is_active', true)->count();
        $inactiveStudentCount = $studentCount - $activeStudentCount;
        $completedMinutes = (int) DtrEntry::query()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('role', UserRole::Student->value))
            ->sum('total_minutes');
        $recentStudents = User::query()
            ->students()
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'studentCount',
            'activeStudentCount',
            'inactiveStudentCount',
            'completedMinutes',
            'recentStudents',
        ));
    }
}
