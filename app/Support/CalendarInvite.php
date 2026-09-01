<?php

namespace App\Support;

use App\Models\RecruiterInterview;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Generates a dependency-free iCalendar (.ics) event so interview
 * invitations can be attached to emails and opened in any calendar app.
 */
class CalendarInvite
{
    public const PRODID = '-//ProoDev//Interview Invite//EN';

    public static function for(RecruiterInterview $interview, User $recruiter, int $durationMinutes = 60): string
    {
        $start = $interview->scheduled_at?->copy()->utc() ?? now()->utc();
        $end = $start->copy()->addMinutes($durationMinutes);

        $summary = 'Interview with '.$recruiter->name.' on ProoDev';
        $description = implode("\n", array_filter([
            'You have an interview scheduled on ProoDev.',
            'Mode: '.Str::title($interview->mode ?? 'Not set'),
            'Recruiter: '.$recruiter->name.' ('.$recruiter->email.')',
        ]));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:'.self::PRODID,
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:'.(string) Str::uuid().'@proodev.com',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.self::escape($summary),
            'DESCRIPTION:'.self::escape($description),
            'LOCATION:'.self::escape(self::modeLabel($interview->mode)),
            'ORGANIZER;CN='.self::escape($recruiter->name).':mailto:'.$recruiter->email,
            'ATTENDEE;CN='.self::escape($interview->candidate->name).';ROLE=REQ-PARTICIPANT;RSVP=TRUE:mailto:'.$interview->candidate->email,
            'STATUS:CONFIRMED',
            'SEQUENCE:0',
            'BEGIN:VALARM',
            'TRIGGER:-PT30M',
            'ACTION:DISPLAY',
            'DESCRIPTION:Reminder: '.self::escape($summary),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    private static function modeLabel(?string $mode): string
    {
        return match ($mode) {
            'video' => 'Video call (link provided by recruiter)',
            'phone' => 'Phone call',
            'onsite' => 'On-site meeting',
            default => 'To be confirmed',
        };
    }

    private static function escape(string $value): string
    {
        return str_replace(["\n", ';', ',', '\\'], ['\\n', '\\;', '\\,', '\\\\'], $value);
    }
}
