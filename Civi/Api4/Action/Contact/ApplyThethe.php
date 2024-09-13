<?php
namespace Civi\Api4\Action\Contact;

use Civi\Api4\Generic\BasicBatchAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Contact;

/**
 * Updates organizations' sort_name fields.
 */
class ApplyThethe extends BasicBatchAction {

  /**
   * Only do this where a pattern applies?
   *
   * You may wish to disable this if you need to re-create sort_names after removing a pattern.
   *
   * @default TRUE
   * @var bool
   */
  protected $whereApplicable = TRUE;

  /**
   *
   * This function should return an array with an output record for the item.
   *
   * @param array $item
   * @return array
   * @throws \Civi\API\Exception\NotImplementedException
   */
  protected function doTask($item) {
    $org = Contact::get($this->getCheckPermissions())
      ->addWhere('id', '=', $item['id'])
      ->addSelect('organization_name', 'sort_name')
      ->execute()->first();
    if (empty($org['organization_name'])) {
      return $item;
    }
    $munged = thethe_munge($org['organization_name']);
    if (($org['sort_name'] ?? '') !== $munged) {
      Contact::update($this->getCheckPermissions())
        ->addWhere('id', '=', $item['id'])
        ->addValue('sort_name', $munged)
        ->execute();
      $org['sort_name'] = $munged;
    }
    return $org;
  }

  public function _run(Result $result) {
    if ($this->whereApplicable) {
      $orGroup = [];
      foreach (thethe_get_setting('prefix') as $string) {
        if (!empty($string)) {
          $orGroup[] = ['organization_name', 'LIKE', "$string%"];
        }
      }
      foreach (thethe_get_setting('suffix') as $string) {
        if (!empty($string)) {
          $orGroup[] = ['organization_name', 'LIKE', "%$string"];
        }
      }

      foreach (thethe_get_setting('anywhere') as $string) {
        if (!empty($string)) {
          $orGroup[] = ['organization_name', 'LIKE', "%$string%"];
        }
      }
      $this->where[] = ['OR', $orGroup];
    }
    $this->where[] = ['contact_type', '=', 'Organization'];

    parent::_run($result);
  }

}
