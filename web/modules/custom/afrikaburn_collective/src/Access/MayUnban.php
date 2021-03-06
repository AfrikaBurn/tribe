<?php

/**
 * @file
 * Checks whether a user may unban users.
 */

namespace Drupal\afrikaburn_collective\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Drupal\afrikaburn_collective\Controller\CollectiveController;
use Drupal\afrikaburn_shared\Utils;

class MayUnban implements AccessInterface {

  /**
   * Implements appliesTo().
   */
  public function appliesTo() {
    return '_may_unban';
  }

  /**
   * Implements access().
   */
  public function access(AccountInterface $account) {

    $user = Utils::currentUser($account);
    $candidate = Utils::getCandidate();
    $collective = Utils::currentCollective();
    $error = false;

    switch(true){
      case $user->isAnonymous():
        $error = 'You need an account for this!';
        break;
      case !CollectiveController::isAdmin($collective, $user):
        $error = 'You are not an administrator of this collective!';
        break;
      case $user->id() == $candidate->id():
        $error = 'You may not unban yourself!';
        break;
    }

    if ($error){
      Utils::showError($error, $user, $candidate);
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }
}
