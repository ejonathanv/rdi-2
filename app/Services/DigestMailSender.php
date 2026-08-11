<?php

namespace App\Services;

use App\Mail\DailyPatrolsDigestMail;
use App\Mail\OpenUrgentsDigestMail;
use App\Mail\WeeklyIncidentsDigestMail;
use App\Models\Area;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class DigestMailSender
{
    public function __construct(private DigestReports $reports) {}

    public function sendOpenUrgents(): int
    {
        $sent = 0;

        foreach ($this->areasWithContacts() as $area) {
            $digest = $this->reports->openUrgents($area);

            if (! $this->reports->hasOpenUrgents($digest)) {
                continue;
            }

            $sent += $this->sendToContacts($area, new OpenUrgentsDigestMail($digest));
        }

        return $sent;
    }

    public function sendWeeklyIncidents(): int
    {
        $sent = 0;

        foreach ($this->areasWithContacts() as $area) {
            $digest = $this->reports->weeklyIncidents($area);
            $sent += $this->sendToContacts($area, new WeeklyIncidentsDigestMail($digest));
        }

        return $sent;
    }

    public function sendDailyPatrols(): int
    {
        $sent = 0;

        foreach ($this->areasWithContacts() as $area) {
            $digest = $this->reports->dailyPatrols($area);

            if (count($digest['patrols']) === 0) {
                continue;
            }

            $sent += $this->sendToContacts($area, new DailyPatrolsDigestMail($digest));
        }

        return $sent;
    }

    /**
     * @return Collection<int, Area>
     */
    private function areasWithContacts()
    {
        return Area::query()
            ->where('is_active', true)
            ->whereHas('contacts')
            ->orderBy('id')
            ->get();
    }

    private function sendToContacts(Area $area, object $mailable): int
    {
        $contacts = $this->reports->contactsForArea($area);

        if ($contacts->isEmpty()) {
            return 0;
        }

        Mail::to($contacts)->queue($mailable);

        return $contacts->count();
    }
}
