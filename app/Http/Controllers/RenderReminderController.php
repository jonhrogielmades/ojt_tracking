<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class RenderReminderController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $expectedSecret = (string) config('ojt.render.cron_secret');
        $providedSecret = (string) $request->header('X-Render-Cron-Secret', '');

        abort_if(
            $expectedSecret === ''
                || $providedSecret === ''
                || ! hash_equals($expectedSecret, $providedSecret),
            404,
        );

        $exitCode = Artisan::call('students:send-reminders');

        if ($exitCode !== SymfonyCommand::SUCCESS) {
            return response()->json(['status' => 'failed'], 503);
        }

        return response()->json(['status' => 'completed']);
    }
}
