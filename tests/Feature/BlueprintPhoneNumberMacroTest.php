<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;

describe('Blueprint::phoneNumber macro', function (): void {
    beforeEach(function (): void {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        DB::purge();

        $connection = DB::connection();
        $connection->useDefaultSchemaGrammar();

        $this->blueprint = fn (): Blueprint => new Blueprint($connection, 'test');
    });

    it('adds an unsigned bigInteger column matching PhoneNumberCast storage', function (): void {
        $blueprint = ($this->blueprint)();

        $column = $blueprint->phoneNumber('phone');

        expect($column)->toBeInstanceOf(ColumnDefinition::class)
            ->and($column->get('name'))->toBe('phone')
            ->and($column->get('type'))->toBe('bigInteger')
            ->and($column->get('unsigned'))->toBeTrue()
            ->and($column->get('autoIncrement'))->toBeFalse();
    });

    it('returns a ColumnDefinition that supports further chaining', function (): void {
        $blueprint = ($this->blueprint)();

        $column = $blueprint->phoneNumber('phone')->nullable()->unique();

        expect($column->get('nullable'))->toBeTrue()
            ->and($column->get('unique'))->toBeTrue();
    });

    it('registers the column on the blueprint', function (): void {
        $blueprint = ($this->blueprint)();

        $blueprint->phoneNumber('phone');

        $names = array_map(fn (ColumnDefinition $c): string => $c->get('name'), $blueprint->getColumns());

        expect($names)->toContain('phone');
    });
});
