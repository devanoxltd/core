<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:clean-up')->weekly();
