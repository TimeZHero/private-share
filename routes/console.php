<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('secrets:cleanup')->daily();
