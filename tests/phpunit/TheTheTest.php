<?php

use CRM_Thethe_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class TheTheTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use Civi\Test\Api3TestTrait;

  /**
   * Set up for headless tests.
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test sort name is saved with changes - we use
   * 1) the default 'The ' for prefix
   * 2) a suffix strings a single string
   * 3) an array for anywhere strings
   */
  public function testSaveSortName() {
    Civi::settings()->set('thethe_org_suffix_strings', 'Ltd');
    Civi::settings()->set('thethe_org_anywhere_strings', ['%', '-']);
    $this->callAPISuccess('Contact', 'create', [
      'organization_name' => 'The Top 10 -% Ltd',
      'contact_type' => 'Organization',
    ]);
    $organization = $this->callAPISuccess('Contact', 'getsingle', ['organization_name' => 'The Top 10 -% Ltd']);
    $this->assertEquals('Top 10', $organization['sort_name']);
  }

  /**
   */
  public function testCaseRules() {
    Civi::settings()->set('thethe_org_prefix_strings', "'université de'");
    Civi::settings()->set('thethe_org_suffix_strings', "' Ltd'");
    Civi::settings()->set('thethe_org_anywhere_strings', "' and'");

    // Test mbstring support.
    if (!function_exists('mb_strtolower')) {
      $this->markTestSkipped('Cannot test mbstring functions; not installed');
      return;
    }
    $this->assertEquals('Life', thethe_munge('UNIVERSITÉ de Life LTD'));

    // This test is expected.
    $this->assertEquals('This That', thethe_munge('This and That'));

    // This test documents current behaviour; I'm not sure if this is *desired* behaviour or not.
    $this->assertEquals('This And That', thethe_munge('This And That'));
  }

  /**
   * Test parsing the settings string/array.
   *
   * @dataProvider getSettingsData
   */
  public function testParseSetting($patternsString, $expectedArray) {
    $this->assertEquals($expectedArray, thethe_parse_setting_value($patternsString));
  }

  /**
   * Data provider for testGetSettings
   */
  protected function getSettingsData(): array {
    return [
      'empty string means no patterns' => ['', []],
      'pattern with comma is ok' => ["','", [',']],
      'unquoted' => ['the', ['the']],
      'quoted' => ["'the '", ['the ']],
      'php array' => [['the '], ['the ']],
      'quoted csv empty item' => ["'the ','a ',", ['the ', 'a ']],
      'Can include single quotes escaped with backslash' => ["'\\''", ["'"]],
    ];
  }

}
