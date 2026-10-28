<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * MoodlIA plugin implementation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use local_moodlia\external\list_users;
use local_moodlia\external\set_user_suspended;
use local_moodlia\operation\delete_user;
use local_moodlia\operation\update_user;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\external\list_users::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\external\set_user_suspended::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\operation\list_users::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\operation\set_user_suspended::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\operation\admin_tools::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\operation\update_user::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\operation\delete_user::class)]
/**
 * Account discovery, lifecycle, permission, and protected-account regression tests.
 *
 * @covers \local_moodlia\external\list_users
 * @covers \local_moodlia\external\set_user_suspended
 * @covers \local_moodlia\operation\list_users
 * @covers \local_moodlia\operation\set_user_suspended
 * @covers \local_moodlia\operation\admin_tools
 * @covers \local_moodlia\operation\update_user
 * @covers \local_moodlia\operation\delete_user
 */
final class user_management_test extends \advanced_testcase {
    /**
     * Search filters compose, pagination is stable, and secrets are not returned.
     */
    public function test_search_and_pagination(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $first = $this->getDataGenerator()->create_user(['username' => 'managed_one', 'email' => 'one@example.test']);
        $second = $this->getDataGenerator()->create_user(['username' => 'managed_two', 'suspended' => 1]);
        $deleted = $this->getDataGenerator()->create_user(['username' => 'managed_deleted']);
        delete_user::execute((int) $deleted->id);
        $page = list_users::execute('managed_', '', '', 'manual', null, 0, 1);
        $this->assertSame(2, $page['total']);
        $this->assertCount(1, $page['users']);
        $this->assertSame((int) $first->id, $page['users'][0]['user_id']);
        $this->assertArrayNotHasKey('password', $page['users'][0]);
        $this->assertSame((int) $second->id, list_users::execute('managed_', '', '', '', null, 1, 1)['users'][0]['user_id']);
        $this->assertSame(1, list_users::execute('', 'managed_two', '', '', true)['total']);
        $this->assertSame(0, list_users::execute('', 'managed_two', '', '', false)['total']);
        $this->assertSame(1, list_users::execute('', '', 'one@example.test')['total']);
        $this->assertSame(0, list_users::execute('%')['total']);
        $this->assertSame([], list_users::execute('managed_', '', '', '', null, 100)['users']);
        $this->assertSame(0, list_users::execute('', 'guest')['total']);
    }

    /**
     * Explicit suspension and reactivation persist and preserve profile fields.
     */
    public function test_suspend_and_reactivate(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $this->assertTrue(set_user_suspended::execute((int) $user->id, true)['suspended']);
        $this->assertSame(1, (int) $DB->get_field('user', 'suspended', ['id' => $user->id]));
        $restored = set_user_suspended::execute((int) $user->id, false);
        $this->assertFalse($restored['suspended']);
        $this->assertSame($user->email, $restored['email']);
        $this->assertSame(0, (int) $DB->get_field('user', 'suspended', ['id' => $user->id]));
    }

    /**
     * Existing create/update/delete functions compose with account discovery.
     */
    public function test_account_lifecycle(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $created = \local_moodlia\external\create_user::execute(
            'managed_lifecycle', 'Alex', 'Example', 'lifecycle@example.test', 'Fixture-Pass-42!'
        );
        $userid = $created['user_id'];
        $this->assertSame($userid, list_users::execute('', 'managed_lifecycle')['users'][0]['user_id']);
        $updated = \local_moodlia\external\update_user::execute($userid, 'Updated');
        $this->assertSame('Updated', $updated['firstname']);
        $this->assertSame('lifecycle@example.test', $updated['email']);
        $deleted = \local_moodlia\external\delete_user::execute($userid);
        $this->assertTrue($deleted['deleted']);
        $this->assertSame(0, list_users::execute('', 'managed_lifecycle')['total']);
    }

    /**
     * Pagination bounds are enforced by PHP independently of the client.
     */
    public function test_invalid_page_size(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->expectException(\invalid_parameter_exception::class);
        list_users::execute('', '', '', '', null, 0, 101);
    }

    /**
     * Ordinary users cannot enumerate the global account directory.
     */
    public function test_search_permission_denied(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $roleid = create_role('API directory test', 'apidirectorytest', '');
        assign_capability('local/moodlia:useapi', CAP_ALLOW, $roleid, \context_system::instance()->id);
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, \context_system::instance()->id);
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);
        $this->expectException(\required_capability_exception::class);
        list_users::execute();
    }

    /**
     * Ordinary users cannot suspend accounts.
     */
    public function test_suspend_permission_denied(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        set_user_suspended::execute((int) $user->id, true);
    }

    /**
     * Protected targets reject updates and deletion before any write.
     */
    public function test_protected_accounts(): void {
        global $CFG, $USER, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $adminid = (int) $USER->id;
        $deleted = $this->getDataGenerator()->create_user();
        delete_user::execute((int) $deleted->id);
        $remote = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'mnethostid', $CFG->mnet_localhost_id + 1, ['id' => $remote->id]);
        foreach ([$adminid, (int) $CFG->siteguest, (int) $deleted->id, (int) $remote->id] as $userid) {
            foreach (['delete', 'suspend'] as $action) {
                try {
                    if ($action === 'delete') {
                        delete_user::execute($userid);
                    } else {
                        set_user_suspended::execute($userid, true);
                    }
                    $this->fail('Protected account mutation succeeded.');
                } catch (\invalid_parameter_exception $exception) {
                    $this->assertNotEmpty($exception->getMessage());
                }
            }
        }
        $manager = $this->getDataGenerator()->create_user();
        $this->setUser($manager);
        foreach ([$adminid, (int) $manager->id] as $userid) {
            try {
                update_user::execute($userid, ['suspended' => true]);
                $this->fail('Administrator or own account suspension succeeded.');
            } catch (\invalid_parameter_exception $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }
}
