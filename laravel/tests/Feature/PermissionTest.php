<?php

use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('cat has correct permissions', function () {
    expect(Permission::hasPermission('cat', 'kill'))->toBeTrue()
        ->and(Permission::hasPermission('cat', 'vote'))->toBeTrue()
        ->and(Permission::hasPermission('cat', 'view_night_chat'))->toBeTrue()
        ->and(Permission::hasPermission('cat', 'heal'))->toBeFalse();
});

test('witch has correct permissions', function () {
    expect(Permission::hasPermission('witch', 'heal'))->toBeTrue()
        ->and(Permission::hasPermission('witch', 'vote'))->toBeTrue()
        ->and(Permission::hasPermission('witch', 'use_poison'))->toBeTrue()
        ->and(Permission::hasPermission('witch', 'kill'))->toBeFalse();
});

test('villager can only vote', function () {
    expect(Permission::hasPermission('villager', 'vote'))->toBeTrue()
        ->and(Permission::hasPermission('villager', 'kill'))->toBeFalse()
        ->and(Permission::hasPermission('villager', 'heal'))->toBeFalse()
        ->and(Permission::hasPermission('villager', 'see_role'))->toBeFalse();
});

test('seer can see roles', function () {
    expect(Permission::hasPermission('seer', 'see_role'))->toBeTrue()
        ->and(Permission::hasPermission('seer', 'vote'))->toBeTrue()
        ->and(Permission::hasPermission('seer', 'kill'))->toBeFalse()
        ->and(Permission::hasPermission('seer', 'heal'))->toBeFalse();
});

test('roles have correct permission counts', function () {
    $catPermissions = Permission::getPermissionsForRole('cat')->pluck('permission')->toArray();
    expect($catPermissions)->toContain('kill')
        ->and($catPermissions)->toContain('vote')
        ->and($catPermissions)->toContain('view_night_chat')
        ->and($catPermissions)->toHaveCount(3);

    $witchPermissions = Permission::getPermissionsForRole('witch')->pluck('permission')->toArray();
    expect($witchPermissions)->toContain('heal')
        ->and($witchPermissions)->toContain('vote')
        ->and($witchPermissions)->toContain('use_poison')
        ->and($witchPermissions)->toHaveCount(3);
});

test('all roles can vote', function () {
    $rolesWithVotePermission = Permission::getRolesWithPermission('vote');
    expect($rolesWithVotePermission)->toContain('cat')
        ->and($rolesWithVotePermission)->toContain('witch')
        ->and($rolesWithVotePermission)->toContain('villager')
        ->and($rolesWithVotePermission)->toContain('seer');
});

test('special abilities are role specific', function () {
    // Only cats can kill
    $rolesWithKillPermission = Permission::getRolesWithPermission('kill');
    expect($rolesWithKillPermission)->toHaveCount(1)
        ->and($rolesWithKillPermission)->toContain('cat');

    // Only witches can heal
    $rolesWithHealPermission = Permission::getRolesWithPermission('heal');
    expect($rolesWithHealPermission)->toHaveCount(1)
        ->and($rolesWithHealPermission)->toContain('witch');

    // Only seers can see roles
    $rolesWithSeeRolePermission = Permission::getRolesWithPermission('see_role');
    expect($rolesWithSeeRolePermission)->toHaveCount(1)
        ->and($rolesWithSeeRolePermission)->toContain('seer');
});
