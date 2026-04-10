<?php

namespace Relaticle\Comments\Tests\TestCases;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Relaticle\Comments\Tests\TestCase;

abstract class MultiTenancyTestCase extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $column = config('comments.multi_tenancy.tenant_column', 'tenant_id');
        $commentsTable = config('comments.table_names.comments', 'comments');

        Schema::table($commentsTable, function (Blueprint $table) use ($column) {
            $table->unsignedBigInteger($column)->nullable()->index()->after('id');
        });
    }
}
