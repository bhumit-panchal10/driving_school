<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Schedule;
use App\Models\ScheduleMaster;
use Illuminate\Support\Facades\Log;


class GenerateDailySchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:daily_schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new schedule entry daily for 91 days after 90 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        Log::info('Starting GenerateDailySchedule command...');

        $today = Carbon::now()->startOfDay();
        $after90Days = $today->copy()->addDays(90);
        $scheduleMasters = ScheduleMaster::get();

        Log::info("Processing schedules for date: {$after90Days->toDateString()}");


        foreach ($scheduleMasters as $scheduleMaster) {

            $newDate = $after90Days->toDateString(); // Next day

            // Insert new schedule entry
            Schedule::create([
                'car_id' => $scheduleMaster->car_id,
                'SchoolId' => $scheduleMaster->SchoolId,
                'fromtime' => $scheduleMaster->fromtime,
                'Totime' => $scheduleMaster->Totime,
                'Schedule_date' => $newDate,
                'Schedulemasterid' => $scheduleMaster->Schedule_master_id,
                'strIP' => request()->ip(),
                'created_at' => now(),
            ]);
            Log::info("Added schedule for ScheduleMaster ID: {$scheduleMaster->Schedule_master_id} on {$newDate}");
        }
        Log::info('GenerateDailySchedule command completed.');


        return Command::SUCCESS;
    }
}
