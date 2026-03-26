<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\Comments\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
