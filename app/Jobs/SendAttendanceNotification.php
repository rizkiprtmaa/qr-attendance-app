<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAttendanceNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $attendance;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        $student = $this->attendance->user->student;

        if (!$student || !$student->parent_number) {
            // Skip jika tidak ada nomor orang tua
            return;
        }

        $phoneNumber = $student->parent_number;
        $studentName = $this->attendance->user->name;
        $attendanceType = $this->attendance->type;
        $attendanceTime = $this->attendance->check_in_time;
        $status = $this->attendance->status;

        $whatsAppService->sendAttendanceNotification(
            $phoneNumber,
            $studentName,
            $attendanceType,
            $attendanceTime,
            $status
        );
    }
}
