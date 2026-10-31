<?php

use Illuminate\Support\Facades\Schedule;

// Hourly so dead uploads release their disk space quickly; every step is cheap and idempotent.
Schedule::command('secrets:cleanup')->hourly();
