<?php

namespace Civi\Api4\Action\Contact;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Contact;

class PreviewThethe extends AbstractAction {

  public function _run(Result $result) {
    $testCases = [];

    foreach (thethe_get_setting('prefix') as $string) {
      $contact = Contact::get()
        ->addSelect('organization_name')
        ->addWhere('contact_type', '=', "Organization")
        ->addWhere('organization_name', 'LIKE', "$string%")
        ->setLimit(1)
        ->execute()->first();
      if ($contact) {
        $testCases[$contact['id']] = $contact['organization_name'];
      }
    }

    $notes = [];
    foreach (thethe_get_setting('suffix') as $string) {
      $contact = Contact::get()
        ->addSelect('organization_name')
        ->addWhere('contact_type', '=', "Organization")
        ->addWhere('organization_name', 'LIKE', "%$string")
        ->setLimit(1)
        ->execute()->first();
      if ($contact) {
        $testCases[$contact['id']] = $contact['organization_name'];
      }
      // $notes[] = "ct $contact[organization_name] included for suffix '$string'";
    }

    foreach (thethe_get_setting('anywhere') as $string) {
      $contact = Contact::get()
        ->addSelect('organization_name')
        ->addWhere('contact_type', '=', "Organization")
        ->addWhere('organization_name', 'LIKE', "%$string%")
        ->setLimit(1)
        ->execute()->first();
      if ($contact) {
        $testCases[$contact['id']] = $contact['organization_name'];
      }
    }

    foreach ($testCases as $ctID => $orgName) {
      $result[] = ['contact_id' => $ctID, 'organization_name' => $orgName, 'sort_name' => thethe_munge($orgName)];
    }
    // $result['notes'] = $notes;
  }
}
