<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentPortfolioController extends Controller
{
    public function index(Request $request): View
    {
        return $this->portfolioView($request, 'student.portfolio');
    }

    public function printable(Request $request): View
    {
        return $this->portfolioView($request, 'student.portfolio-print');
    }

    private function portfolioView(Request $request, string $view): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $user->load('studentProfile');
        $activities = ActivityLog::query()
            ->whereBelongsTo($user)
            ->orderByDesc('activity_date')
            ->get();
        $skills = $this->skillFrequency($activities);
        $completedMinutes = (int) DtrEntry::query()->whereBelongsTo($user)->sum('total_minutes');

        return view($view, compact('user', 'activities', 'skills', 'completedMinutes'));
    }

    /**
     * @param  Collection<int, ActivityLog>  $activities
     * @return Collection<int, array{name: string, count: int}>
     */
    private function skillFrequency(Collection $activities): Collection
    {
        return $activities
            ->flatMap(fn (ActivityLog $activity): array => preg_split('/[,;\n|]+/', (string) $activity->skills_tools) ?: [])
            ->map(fn (string $skill): string => Str::squish($skill))
            ->filter()
            ->groupBy(fn (string $skill): string => Str::lower($skill))
            ->map(fn (Collection $matchingSkills): array => [
                'name' => $matchingSkills->first(),
                'count' => $matchingSkills->count(),
            ])
            ->sortByDesc('count')
            ->values();
    }
}
