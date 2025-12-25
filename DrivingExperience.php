<?php
class DrivingExperience {
    public int $expID;
    public string $date;
    public string $startTime;
    public string $endTime;
    public float $kilometers;

    public function __construct(array $row) {
        $this->expID = (int)$row['expID'];
        $this->date = (string)$row['date'];
        $this->startTime = (string)$row['startTime'];
        $this->endTime = (string)$row['endTime'];
        $this->kilometers = (float)$row['kilometers'];
    }

    public function durationHours(): float {
        $start = new DateTime($this->startTime);
        $end = new DateTime($this->endTime);
        $diff = $end->diff($start);
        return $diff->h + ($diff->i / 60);
    }
}
