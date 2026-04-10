<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\Comments\Tests\TestCase;
use Relaticle\Comments\Tests\TestCases\MultiTenancyTestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

uses(MultiTenancyTestCase::class, RefreshDatabase::class)->in('MultiTenancy');
