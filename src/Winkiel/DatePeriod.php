<?php

namespace Winkiel;

class DatePeriod
{
    protected $start_date;
    protected $end_date;
    protected $dates_list;
    protected $include_dates_between;

    public static function createPeriodBoundaries(\DateTime $end_date, $period)
    {
        return new self(self::getPreviousPeriodDate($end_date, $period), $end_date);
    }

    public static function createDatesRange(\DateTime $start_date, \DateTime $end_date)
    {
        return new self($start_date, $end_date, true);
    }

    public static function date(\DateTime $date)
    {
        return new self($date, $date);
    }

    public static function yesterday()
    {
        return new self(new \DateTime('yesterday'), new \DateTime('yesterday'));
    }

    private function __construct(
        \DateTime $start_date,
        \DateTime $end_date,
        $include_dates_between = false,
        $trim_to_yesterday = true
    ) {
        $start_date = new \DateTime($start_date->format('Y-m-d'));
        $end_date = new \DateTime($end_date->format('Y-m-d'));

        if ($trim_to_yesterday && $end_date > new \DateTime('yesterday')) {
            $end_date = new \DateTime('yesterday');
        }

        $end_ts = $end_date->format('U');
        $start_ts = $start_date->format('U');

        if ($end_ts - $start_ts < 0) {
            throw new \InvalidArgumentException(sprintf(
                'Start date (%s) has to be earlier or equal to end date (%s)',
                $start_date->format('Y-m-d H:i:s'),
                $end_date->format('Y-m-d H:i:s')
            ));
        }

        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->include_dates_between = $include_dates_between;

        $this->createDatesList();
    }

    protected function createDatesList($step = 1)
    {
        $this->dates_list = array();
        $currentDate = clone $this->start_date;

        if ($this->include_dates_between) {
            $index = 0;
            while ($currentDate <= $this->end_date) {
                if ($index % $step == 0) {
                    $this->dates_list[] = new \DateTime($currentDate->format('Y-m-d'));
                }
                $currentDate = $currentDate->add(\DateInterval::createFromDateString("+1 day"));
                $index++;
            }

            if (end($this->dates_list) != $this->end_date) {
                $this->dates_list[] = clone $this->end_date;
            }
        } else {
            $this->dates_list[] = $this->start_date;
            if ($this->start_date != $this->end_date) {
                $this->dates_list[] = $this->end_date;
            }
        }
    }

    public function getStartDate()
    {
        return $this->start_date;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }

    public function getDates($step = 1)
    {
        $this->createDatesList($step);

        return $this->dates_list;
    }

    public function containsPeriodBoundariesOnly()
    {
        return count($this->dates_list) == 2;
    }

    public static function getPreviousPeriodDate(\DateTime $date, $period, $nb_periods = 1)
    {
        $newDate = clone $date;
        $validPeriods = array('day', 'week', 'month', 'year');

        if (!in_array($period, $validPeriods)) {
            throw new \InvalidArgumentException(sprintf("Period value must be one of [%s]", implode(', ', $validPeriods)));
        }

        if ($nb_periods < 1) {
            throw new \InvalidArgumentException("Number of periods must be >= 1");
        }

        return $newDate->sub(\DateInterval::createFromDateString($nb_periods.' '.$period));
    }

    public function getMonths()
    {
        $months = array();

        $startMonth = new \DateTime($this->start_date->format('Y-m-d'));
        $startMonth->modify('first day of this month');

        $endMonth = new \DateTime($this->end_date->format('Y-m-d'));
        $endMonth->modify('first day of this month');

        while ($startMonth <= $endMonth) {
            $months[] = clone $startMonth;
            $startMonth->modify('+1 month');
        }

        return $months;
    }
}
