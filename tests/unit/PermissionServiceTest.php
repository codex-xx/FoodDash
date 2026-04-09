<?php

use App\Libraries\PermissionService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PermissionServiceTest extends CIUnitTestCase
{
    private PermissionService $permissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissions = new PermissionService();
    }

    public function testAdminHasFullOrderPermissions(): void
    {
        $this->assertTrue($this->permissions->allows('admin', 'orders', 'read'));
        $this->assertTrue($this->permissions->allows('admin', 'orders', 'write'));
        $this->assertTrue($this->permissions->allows('admin', 'orders', 'update'));
        $this->assertTrue($this->permissions->allows('admin', 'orders', 'delete'));
        $this->assertTrue($this->permissions->allows('admin', 'orders', 'assign'));
    }

    public function testRestaurantCannotDeleteOrders(): void
    {
        $this->assertFalse($this->permissions->allows('restaurant', 'orders', 'delete'));
    }

    public function testCustomerCanCreateOrdersButCannotDelete(): void
    {
        $this->assertTrue($this->permissions->allows('customer', 'orders', 'write'));
        $this->assertFalse($this->permissions->allows('customer', 'orders', 'delete'));
    }

    public function testUnknownRoleOrActionIsRejected(): void
    {
        $this->assertFalse($this->permissions->allows('guest', 'orders', 'read'));
        $this->assertFalse($this->permissions->allows('admin', 'orders', 'export'));
    }
}
