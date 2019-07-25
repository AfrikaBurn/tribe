<?php

/**
 * @file
 * Checks whether a user may promote members to admins in a collective.
 */

namespace Drupal\afrikaburn_collective\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Drupal\afrikaburn_collective\Controller\CollectiveController;
use Drupal\afrikaburn_collective\Utils;

class MayAdmin implements AccessInterface {

  /**
   * Implements appliesTo().
   */
  public function appliesTo() {
    return '_may_admin';
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
      case !CollectiveController::isMember($collective, $candidate):
        $error = '@user not a member!';
        break;
      case !CollectiveController::isAdmin($collective, $user):
        $error = 'You are not an administrator of this collective!';
        break;
    }

    if ($error){
      Utils::showError($error, $user, $candidate);
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }
}
