<?php

namespace Tests\Feature;

use Tests\TestCase;

class RbacPermissionGuideCompletenessTest extends TestCase
{
    public function test_every_rbac_permission_has_a_guide_entry(): void
    {
        $permissionLabels = (array) config('rbac.permissions', []);
        $permissions = array_keys($permissionLabels);

        $guides = (array) config('rbac_guide.permissions', []);

        foreach ($permissions as $permission) {
            $this->assertArrayHasKey($permission, $guides, "Missing guide for permission: {$permission}");

            $guide = (array) $guides[$permission];

            $this->assertArrayHasKey('summary', $guide, "Guide missing summary: {$permission}");
            $this->assertArrayHasKey('grants', $guide, "Guide missing grants: {$permission}");
            $this->assertArrayHasKey('not_grants', $guide, "Guide missing not_grants: {$permission}");
            $this->assertArrayHasKey('affected_areas', $guide, "Guide missing affected_areas: {$permission}");
            $this->assertArrayHasKey('risk', $guide, "Guide missing risk: {$permission}");
            $this->assertArrayHasKey('related_permissions', $guide, "Guide missing related_permissions: {$permission}");

            $risk = (array) ($guide['risk'] ?? []);
            $this->assertArrayHasKey('sensitive_data', $risk, "Guide missing risk.sensitive_data: {$permission}");
            $this->assertArrayHasKey('financial_risk', $risk, "Guide missing risk.financial_risk: {$permission}");
            $this->assertArrayHasKey('system_risk', $risk, "Guide missing risk.system_risk: {$permission}");
        }
    }
}
