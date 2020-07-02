<?php

use Carbon\Carbon;


function takeLastMessage($count)
{
    return \App\Models\Replay::with('ticket')
        ->whereHas('ticket')
        ->where(function ($q) {
            $q->where('FromUser', 1);
            $q->orWhere('FromUser', 2);
        })
        ->latest()
        ->take($count)
        ->get();

}

function takeLastNotifications($count)
{
    return \App\Models\GeneralNotification::latest()->admin()->take($count)->get();
}


function getDiffBetweenTwoDate($startDate, $endDate, $formate = 'a')
{
    $fdate = $startDate;
    $tdate = $endDate;
    $datetime1 = new DateTime($fdate);
    $datetime2 = new DateTime($tdate);
    $interval = $datetime1->diff($datetime2);
    $days = $interval->format('%a');
    return $days;
}


function getDiffBetweenTwoDateIMinute($startDate, $endDate)
{
    $to = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $endDate);
    $from = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $startDate);
    return $diff_in_minutes = $to->diffInMinutes($from);
}

function getDiffinSeconds($created_date)
{
    return Carbon::now()->diffInSeconds(Carbon::parse($created_date));
}

function getSeconds($hours, $mins, $secs)
{
    return ($hours * 60 * 60) + ($mins * 60) + ($secs);
}

function getDoctorById($DoctorId)
{
    $doctor = \App\Models\Doctor::find($DoctorId);
    if ($doctor)
        return $doctor->name_ar;
    else
        return '';
}

function getBranchById($branchId)
{
    $branch = \App\Models\Provider::find($branchId);
    if ($branchId)
        return $branch->name_ar;
    else
        return '';
}


function maskPhoneNumber($number)
{
    $mask_number = str_repeat("x", (strlen($number)) - 4) . substr($number, -4);
    return $mask_number;
}
