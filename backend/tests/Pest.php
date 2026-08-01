<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| All tests use RefreshDatabase so each test starts with a clean state.
| The 'api' dataset tag groups all API feature tests for filtering.
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');
