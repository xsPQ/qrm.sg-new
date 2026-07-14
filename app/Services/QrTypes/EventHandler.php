<?php

namespace App\Services\QrTypes;

use App\Models\QrCode;
use Illuminate\Http\Request;

class EventHandler implements QrTypeHandler
{
    public function handle(QrCode $qrCode, Request $request): mixed
    {
        $title = $qrCode->content['title'] ?? $qrCode->title ?? 'Event';
        $start = $qrCode->content['start'] ?? null;
        $end = $qrCode->content['end'] ?? null;
        $location = $qrCode->content['location'] ?? null;
        $description = $qrCode->content['description'] ?? null;
        $timezone = $qrCode->content['timezone'] ?? 'UTC';

        $icsUrl = null;
        if ($start) {
            $icsUrl = $this->generateIcsUrl($qrCode->id, $title, $start, $end, $location, $description, $timezone);
        }

        return view('qr-types.event', [
            'qrCode' => $qrCode,
            'title' => $title,
            'start' => $start,
            'end' => $end,
            'location' => $location,
            'description' => $description,
            'timezone' => $timezone,
            'icsUrl' => $icsUrl,
        ]);
    }

    private function generateIcsUrl(
        int $qrCodeId,
        string $title,
        string $start,
        ?string $end,
        ?string $location,
        ?string $description,
        string $timezone = 'UTC',
    ): string {
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//qrm.sg//Event//EN\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "DTSTART;TZID=" . $this->escapeIcsText($timezone) . ":" . $this->formatIcsDate($start) . "\r\n";

        if ($end) {
            $ics .= "DTEND;TZID=" . $this->escapeIcsText($timezone) . ":" . $this->formatIcsDate($end) . "\r\n";
        }

        $ics .= "SUMMARY:" . $this->escapeIcsText($title) . "\r\n";

        if ($location) {
            $ics .= "LOCATION:" . $this->escapeIcsText($location) . "\r\n";
        }

        if ($description) {
            $ics .= "DESCRIPTION:" . $this->escapeIcsText($description) . "\r\n";
        }

        $ics .= "UID:qr-{$qrCodeId}@qrm.sg\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return 'data:text/calendar;charset=utf-8,' . rawurlencode($ics);
    }

    private function formatIcsDate(string $iso8601): string
    {
        return preg_replace('/[-:]/', '', substr($iso8601, 0, 19));
    }

    private function escapeIcsText(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\;', '\,', '\n'],
            $text,
        );
    }
}
