<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AutomaticSchedule;
use App\Models\AutomaticScheduleDetail;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use App\Models\Student;
use Carbon\Carbon;

class GenerateAutomaticSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-auto-sessions {day? : Specific day to generate schedule for} {--preview : Only show what would be created without actually creating sessions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate automatic sessions based on schedule settings';

    /**
     * Days of the week mapping (Indonesian to English)
     */
    protected $daysMapping = [
        'Senin' => 'Monday',
        'Selasa' => 'Tuesday',
        'Rabu' => 'Wednesday',
        'Kamis' => 'Thursday',
        'Jumat' => 'Friday',
        'Sabtu' => 'Saturday',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $day = $this->argument('day');
        $isPreview = $this->option('preview');

        if ($day) {
            // Generate for a specific day
            if (!array_key_exists($day, $this->daysMapping)) {
                $this->error("Invalid day specified. Use one of: " . implode(', ', array_keys($this->daysMapping)));
                return 1;
            }

            $this->generateForDay($day, $isPreview);
        } else {
            // If no day specified, generate for today or tomorrow
            $todayIndo = array_search(Carbon::now()->format('l'), $this->daysMapping);
            if (!$todayIndo) {
                $this->warn("Today is Sunday, no schedules to generate.");
                return 0;
            }

            $this->generateForDay($todayIndo, $isPreview);
        }

        return 0;
    }

    /**
     * Generate sessions for a specific day
     */
    protected function generateForDay($dayIndo, $isPreview = false)
    {
        $this->info("Generating automatic sessions for: $dayIndo");

        // Get schedule for this day
        $schedule = AutomaticSchedule::where('day_of_week', $dayIndo)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            $this->warn("No active schedule found for $dayIndo");
            return;
        }

        // Get active schedule details
        $scheduleDetails = AutomaticScheduleDetail::where('automatic_schedule_id', $schedule->id)
            ->where('is_active', true)
            ->get();

        if ($scheduleDetails->isEmpty()) {
            $this->warn("No active schedule details found for $dayIndo");
            return;
        }

        // Get the next date for this day of week
        $dayEng = $this->daysMapping[$dayIndo];
        $today = Carbon::now();

        // If today is the target day, use today's date
        if ($today->format('l') === $dayEng) {
            $targetDate = $today;
        } else {
            // Otherwise, find the next occurrence
            $targetDate = $today->copy()->next($dayEng);
        }

        $this->info("Target date: " . $targetDate->format('Y-m-d'));

        $generatedCount = 0;
        $skippedCount = 0;

        // Generate sessions for each schedule detail
        foreach ($scheduleDetails as $detail) {
            // Only proceed if the subject class still exists
            $subjectClass = SubjectClass::find($detail->subject_class_id);

            if (!$subjectClass) {
                $this->warn("Subject class ID {$detail->subject_class_id} not found, skipping...");
                $skippedCount++;
                continue;
            }

            // Format the session title using the template
            $sessionTitle = str_replace(
                ['%subject%', '%date%'],
                [$subjectClass->class_name, $targetDate->format('d-m-Y')],
                $detail->session_title_template
            );

            // Check if a session already exists for this date and time
            $existingSession = SubjectClassSession::where('subject_class_id', $detail->subject_class_id)
                ->whereDate('class_date', $targetDate->format('Y-m-d'))
                ->where('start_time', $detail->start_time)
                ->first();

            if ($existingSession) {
                $this->warn("Session already exists for class {$subjectClass->class_name} on {$targetDate->format('Y-m-d')} at {$detail->start_time}, skipping...");
                $skippedCount++;
                continue;
            }

            // Log what would be created if in preview mode
            if ($isPreview) {
                $this->info("Would create session: {$sessionTitle} for {$subjectClass->class_name} ({$subjectClass->classes->name}) on {$targetDate->format('Y-m-d')} at {$detail->start_time}");
                $generatedCount++;
                continue;
            }

            // Create the session
            $session = SubjectClassSession::create([
                'subject_class_id' => $detail->subject_class_id,
                'subject_title' => $sessionTitle,
                'class_date' => $targetDate->format('Y-m-d') . ' ' . $detail->start_time,
                'start_time' => $detail->start_time,
                'end_time' => $detail->end_time,
                'jam_pelajaran' => $detail->jam_pelajaran
            ]);

            // Get all students in this class
            $students = Student::whereHas('classes', function ($query) use ($subjectClass) {
                $query->where('id', $subjectClass->classes_id);
            })->get();

            // Create attendance records for each student in this session
            foreach ($students as $student) {
                // Check if student has approved permission for this date
                $permissionExists = \App\Models\PermissionSubmission::where('user_id', $student->user_id)
                    ->whereDate('permission_date', $targetDate->format('Y-m-d'))
                    ->where('status', 'approved')
                    ->first();

                if ($permissionExists) {
                    // Jika siswa memiliki izin yang disetujui, atur status sesuai tipe izin (izin/sakit)
                    SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => $permissionExists->type, // 'izin' or 'sakit'
                        'check_in_time' => $targetDate->format('Y-m-d') . ' ' . $detail->start_time,
                    ]);
                } else {
                    // Jika tidak ada izin, set default status 'tidak_hadir'
                    SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => 'tidak_hadir', // Default status
                        'check_in_time' => null,
                    ]);
                }
            }

            $this->info("Created session: {$sessionTitle} for {$subjectClass->class_name} ({$subjectClass->classes->name}) with {$students->count()} students");
            $generatedCount++;
        }

        $this->info("Generation complete for $dayIndo ({$targetDate->format('Y-m-d')})");
        $this->info("Generated: $generatedCount session(s), Skipped: $skippedCount session(s)");
    }
}
