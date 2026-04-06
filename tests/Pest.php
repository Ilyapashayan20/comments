<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\Comments\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

uses(\Relaticle\Comments\Tests\TestCases\MultiTenancyTestCase::class, RefreshDatabase::class)->in('MultiTenancy');
