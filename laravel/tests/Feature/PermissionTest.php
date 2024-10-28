<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Run seeders to populate permissions
    }

    public function test_cat_has_kill_permission()
    {
        $this->assertTrue(Permission::hasPermission('cat', 'kill'));
        $this->assertTrue(Permission::hasPermission('cat', 'vote'));
        $this->assertTrue(Permission::hasPermission('cat', 'view_night_chat'));
        $this->assertFalse(Permission::hasPermission('cat', 'heal'));
    }

    public function test_witch_has_heal_permission()
    {
        $this->assertTrue(Permission::hasPermission('witch', 'heal'));
        $this->assertTrue(Permission::hasPermission('witch', 'vote'));
        $this->assertTrue(Permission::hasPermission('witch', 'use_poison'));
        $this->assertFalse(Permission::hasPermission('witch', 'kill'));
    }

    public function test_villager_can_only_vote()
    {
        $this->assertTrue(Permission::hasPermission('villager', 'vote'));
        $this->assertFalse(Permission::hasPermission('villager', 'kill'));
        $this->assertFalse(Permission::hasPermission('villager', 'heal'));
        $this->assertFalse(Permission::hasPermission('villager', 'see_role'));
    }

    public function test_seer_can_see_roles()
    {
        $this->assertTrue(Permission::hasPermission('seer', 'see_role'));
        $this->assertTrue(Permission::hasPermission('seer', 'vote'));
        $this->assertFalse(Permission::hasPermission('seer', 'kill'));
        $this->assertFalse(Permission::hasPermission('seer', 'heal'));
    }

    public function test_get_all_permissions_for_role()
    {
        $catPermissions = Permission::getPermissionsForRole('cat')->pluck('permission')->toArray();
        $this->assertContains('kill', $catPermissions);
        $this->assertContains('vote', $catPermissions);
        $this->assertContains('view_night_chat', $catPermissions);
        $this->assertCount(3, $catPermissions);

        $witchPermissions = Permission::getPermissionsForRole('witch')->pluck('permission')->toArray();
        $this->assertContains('heal', $witchPermissions);
        $this->assertContains('vote', $witchPermissions);
        $this->assertContains('use_poison', $witchPermissions);
        $this->assertCount(3, $witchPermissions);
    }

    public function test_all_roles_can_vote()
    {
        $rolesWithVotePermission = Permission::getRolesWithPermission('vote');
        $this->assertContains('cat', $rolesWithVotePermission);
        $this->assertContains('witch', $rolesWithVotePermission);
        $this->assertContains('villager', $rolesWithVotePermission);
        $this->assertContains('seer', $rolesWithVotePermission);
    }

    public function test_special_abilities_are_role_specific()
    {
        // Only cats can kill
        $rolesWithKillPermission = Permission::getRolesWithPermission('kill');
        $this->assertCount(1, $rolesWithKillPermission);
        $this->assertContains('cat', $rolesWithKillPermission);

        // Only witches can heal
        $rolesWithHealPermission = Permission::getRolesWithPermission('heal');
        $this->assertCount(1, $rolesWithHealPermission);
        $this->assertContains('witch', $rolesWithHealPermission);

        // Only seers can see roles
        $rolesWithSeeRolePermission = Permission::getRolesWithPermission('see_role');
        $this->assertCount(1, $rolesWithSeeRolePermission);
        $this->assertContains('seer', $rolesWithSeeRolePermission);
    }
}
