<?php
use Carbon\Carbon;
function CDateTime($stamp,$timezone=null)
{
	$dt = Carbon::createFromTimestamp($stamp);
	if($timezone != null)
		$dt->setTimezone(new \DateTimeZone($timezone));
	return $dt;
}
function SecondsToString($ss,$hours_day) 
{
	$s = $ss%60;
	$m = floor(($ss%3600)/60);
	$h = floor(($ss)/3600);
	
	$d = floor($h/$hours_day);
	$h = $h%$hours_day;
	//return "$d days, $h hours, $m minutes, $s seconds";
	return "$d day,$h hour,$m min";
}
/**
 * Check if the given DateTime object is a business day.
 *
 * @param DateTime $date
 * @return bool
 */
function isBusinessDay(\DateTime $date)
{
	if ($date->format('N') > 5) {
		return false;
	}

	//Hard coded public Holidays
	$holidays = [
		"New Years Day"         => new \DateTime(date('Y') . '-01-01'),
		"Memorial Day"          => new \DateTime(date('Y') . '-05-25'),
		"Independence Day"      => new \DateTime(date('Y') . '-07-03'),
		"Labor Day"             => new \DateTime(date('Y') . '-09-07'),
		"Thanksgiving Day"      => new \DateTime(date('Y') . '-11-26'),
		"Thanksgiving Day2"     => new \DateTime(date('Y') . '-11-27'),
		"Floating Holiday1"     => new \DateTime(date('Y') . '-12-24'),
		"Christmas Day"         => new \DateTime(date('Y') . '-12-25'),
		"Floating Holiday2"     => new \DateTime(date('Y') . '-12-31'),
	];
	foreach ($holidays as $holiday) {
		if ($holiday->format('Y-m-d') === $date->format('Y-m-d')) {
			return false;
		}
	}

	//December company holidays
	//if (new \DateTime(date('Y') . '-12-15') <= $date && $date <= new \DateTime((date('Y') + 1) . '-01-08')) {
	//	return false;
	//}

	// Other checks can go here

	return true;
}
/**
 * Get the available business time between two dates (in seconds).
 *
 * @param $start
 * @param $end
 * @return mixed
 */
function GetBusinessSeconds($start, $end,$starthour=8,$endhour=20)
{
	$start = $start instanceof \DateTime ? $start : new \DateTime($start);
	$end = $end instanceof \DateTime ? $end : new \DateTime($end);
	$dates = [];

	$date = clone $start;

	while ($date <= $end) {

		$datesEnd = (clone $date)->setTime(23, 59, 59);

		if (isBusinessDay($date)) {
			$dates[] = (object)[
				'start' => clone $date,
				'end'   => clone ($end < $datesEnd ? $end : $datesEnd),
				'starthour' => $starthour,
				'endhour' => $endhour,
			];
		}

		$date->modify('+1 day')->setTime(0, 0, 0);
	}

	return array_reduce($dates, function ($carry, $item) {

		$businessStart = (clone $item->start)->setTime($item->starthour, 000, 0);
		$businessEnd = (clone $item->start)->setTime($item->endhour, 00, 0);

		$start = $item->start < $businessStart ? $businessStart : $item->start;
		$end = $item->end > $businessEnd ? $businessEnd : $item->end;

		//Diff in seconds
		return $carry += max(0, $end->getTimestamp() - $start->getTimestamp());
	}, 0);
}
