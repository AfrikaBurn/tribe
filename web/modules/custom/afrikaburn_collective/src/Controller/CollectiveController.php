<?php
/**
 * @file
 * Contains \Drupal\afrikaburn_collective\Controller\CollectiveController.
 */

namespace Drupal\afrikaburn_collective\Controller;


use \Drupal\Core\Cache\Cache;
use \Drupal\Core\Controller\ControllerBase;
use \Drupal\user\Entity\User;
use \Symfony\Component\HttpFoundation\RedirectResponse;

use \Drupal\afrikaburn_shared\Utils;


class CollectiveController extends ControllerBase {


  /* --- Joining ---- */


  /**
   * Invite a participant to a collective
   */
  public static function join(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::set('member', $collective, $user);
    CollectiveController::clear('invite', $collective, $user);
    Utils::showStatus('@username now a member', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }

  /**
   * Invite a participant to a collective
   * @param $collective Collective to invite user to
   * @param $user       User to invite
   */
  public static function invite(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::set('invite', $collective, $user);
    Utils::showStatus('@username now invited', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }

  /**
   * Invite participant(s) to a collective
   */
  public static function bulkInvite($collective = FALSE, $emails = FALSE){

    $collective = $collective ? $collective : Utils::currentCollective();
    $emails = array_filter(
      preg_split(
        '/[;, ]+/',
        strtolower(
          preg_replace(
            '/\s\s+/',
            ' ',
            $emails ? $emails : \Drupal::request()->request->get('emails')
          )
        )
      )
    );

    $results = [
      'invited' => 0,
      'reminded' => 0,
    ];

    foreach($emails as $email){

      if (preg_match('/[^@]+@[^\.]+\..+/', $email) && !preg_match('/[^a-z0-9\.\+\-\_\@]/', $email)){

        // Find users based on email
        $query = \Drupal::entityQuery('user', 'OR')
          ->condition('field_email', $email)
          ->condition('field_secondary_mail', $email);
        $uids = $query->execute();

        // Invite existing account holders
        if (count($uids)){
          foreach(\Drupal\user\Entity\User::loadMultiple($uids) as $user){
            CollectiveController::set('invite', $collective, $user);
          }
        }

        // Mail invitee
        $index = array_search($email, array_column($collective->get('field_col_invite_mail')->getValue(), 'value'));
        $user = Utils::currentUser();

        if ($index === FALSE) {
          $collective->get('field_col_invite_mail')->appendItem(trim($email));
          $collective->get('field_col_invite_token')->appendItem(md5($collective->getTitle() . $email . time()));
          $collective->get('field_col_invite_by')->appendItem($user);
          $results['invited']++;
        } else {
          $collective->get('field_col_invite_token')->set($index, md5($collective->getTitle() . $email . time()));

          while ($collective->get('field_col_invite_by')->count() < $index) {
            $collective->get('field_col_invite_by')->appendItem($user);
          }

          $collective->get('field_col_invite_by')->set($index, $user);
          $results['reminded']++;
        }

        $collective->save();

      } else {
        if ($email) {
          drupal_set_message(
            $email .
            t(' does not seem to be a valid email address, please check it and try again.'), 'warning', TRUE
          );
        }
      }
    }

    if ($results['invited'] || $results['reminded']){
      drupal_set_message(
        implode(
          ', ',
          array_filter(
            [
              $results['invited'] ? $results['invited'] . ' people invited.' : FALSE,
              $results['reminded'] ? $results['reminded'] . ' people reminded.' : FALSE
            ]
          )
        )
      );
    }

    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }

  /**
   * Accept an invitation to a collective
   */
  public static function accept(){
    list($collective, $user) = CollectiveController::pathParams();

    $token = \Drupal::request()->get('token');
    $host = Utils::currentUser();

    if ($token) {
      $token_index = @array_search($token, array_column($collective->get('field_col_invite_token')->getValue(), 'value'));
      if ($token_index !== FALSE) {
        $host = $collective->get('field_col_invite_by')->get($token_index)->entity;
        $collective->get('field_col_invite_mail')->removeItem($token_index);
        $collective->get('field_col_invite_token')->removeItem($token_index);
        if ($collective->get('field_col_invite_by')->get($token_index)) $collective->get('field_col_invite_by')->removeItem($token_index);
        $collective->save();
      } else {
        drupal_set_message(
          t('Oh no! Your invitation is no longer valid!'),
          'warning',
          TRUE
        );
        return new RedirectResponse(\Drupal::url('<front>'));
      }
    }

    if (CollectiveController::setting($collective, 'vetted')){

      if ($host && CollectiveController::isAdmin($collective, $host)){
        CollectiveController::set('member', $collective, $user);
        CollectiveController::clear('invite', $collective, $user);
        Utils::showStatus('@username now a member', Utils::currentUser(), $user);
      } else {
        CollectiveController::set('join', $collective, $user);
        CollectiveController::clear('invite', $collective, $user);
        Utils::showStatus('Invitation Accepted, but membership still requires administrator approval. Look out for a notification soon!', Utils::currentUser(), $user);
      }

    } else {
      CollectiveController::set('member', $collective, $user);
      CollectiveController::clear('invite', $collective, $user);
      Utils::showStatus('@username now a member', Utils::currentUser(), $user);
    }

    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }

  /**
   * Ignore an invitation to a collective
   */
  public static function ignore(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::clear('invite', $collective, $user);
    Utils::showStatus('Invitation ignored', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('<front>'));
  }

  /**
   * Request to join a collective
   */
  public static function request(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::set('join', $collective, $user);
    Utils::showStatus('Membership requested', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }

  /**
   * Withdraw a request to join a collective
   */
  public static function withdraw(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::clear('join', $collective, $user);
    CollectiveController::clear('invite', $collective, $user);
    Utils::showStatus('Membership request withdrawn', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }

  /**
   * Approve a request to join a collective
   */
  public static function approve(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::clear('join', $collective, $user);
    CollectiveController::set('member', $collective, $user);
    CollectiveController::clear('invite', $collective, $user);
    Utils::showStatus('@username now a member', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }


  /* --- Snubbing --- */


  /**
   * Reject a request to join a collective
   */
  public static function reject(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::clear('join', $collective, $user);
    CollectiveController::clear('invite', $collective, $user);
    Utils::showStatus('Membership request rejected', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }


  /* --- Leaving --- */


  /**
   * Boot a participant from a collective
   */
  public static function leave(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::clear('admin', $collective, $user);
    CollectiveController::clear('member', $collective, $user);
    Utils::showStatus('@username no longer a member', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('<front>'));
  }


  /* --- Managing --- */


  /**
   * Boot a participant from a collective
   */
  public static function boot(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::leave($collective, $user);
    Utils::showStatus('@username no longer a member', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }

  /**
   * Ban a participant from a collective
   */
  public static function ban(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::clear('admin', $collective, $user);
    CollectiveController::clear('member', $collective, $user);
    CollectiveController::set('ban', $collective, $user);
    Utils::showStatus('@username now banned', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }
  /**
   * Unban a participant from a collective
   */
  public static function unban(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::clear('ban', $collective, $user);
    Utils::showStatus('@username now unbanned', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }

  /**
   * Promote a member to admin a collective
   */
  public static function admin(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::set('admin', $collective, $user);
    Utils::showStatus('@username now an Admin', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }

  /**
   * Strip a member from admining a collective
   */
  public static function strip(){
    list($collective, $user) = CollectiveController::pathParams();
    CollectiveController::clear('admin', $collective, $user);
    Utils::showStatus('@username no longer an Admin', Utils::currentUser(), $user);
    return new RedirectResponse(\Drupal::url('entity.node.canonical', ['node' => $collective->id()]));
  }


  /* --- Role retrieval --- */


  /**
   * Returns members of a collective.
   * @param $collective Collective to get members for
   */
  public static function getMembers($collective){
    return CollectiveController::getFlaggingUsers('member', $collective);
  }

  /**
   * Returns admins of a collective.
   * @param $collective Collective to get admins for
   */
  public static function getAdmins($collective){
    return CollectiveController::getFlaggingUsers('admin', $collective);
  }


  /* --- Role checking --- */


  /**
   * Checks whether a participant is invited to a collective
   * @param $collective Collective to check invitation against
   * @param $user       User to check invitation against
   */
  public static function isInvited($collective, $user){

    if ($collective) {

      $token = in_array(
        \Drupal::request()->get('token'),
        array_column($collective->field_col_invite_token->getValue(), 'value')
      );
      $direct = $user && CollectiveController::get('invite', $collective, $user);

      return $token || $direct;
    }

    return FALSE;
  }

  /**
   * Checks whether a participant has requested to join to a collective
   * @param $collective Collective to check membership request against
   * @param $user       User to check membership request against
   */
  public static function isRequested($collective, $user){
    return CollectiveController::get('join', $collective, $user);
  }

  /**
   * Checks whether a participant is a member of a collective
   * @param $collective Collective to check membership against
   * @param $user       User to check membership against
   * @param $stealth    Ignore system admin status
   */
  public static function isMember($collective, $user, $stealth = FALSE){
    return
      @!$stealth && (
        $user->hasRole('administrator') ||
        $user->hasRole('art_wrangler') ||
        $user->hasRole('mutant_vehicle_wrangler') ||
        $user->hasRole('theme_camp_wrangler') ||
        $user->hasRole('communications_wrangler') ||
        $user->hasRole('support_wrangler')
      ) || CollectiveController::get('member', $collective, $user);
  }

  /**
   * Checks whether a participant is an admin of a collective
   * @param $collective Collective to check administratorship against
   * @param $user       User to check administratorship against
   * @param $stealth    Ignore system admin status
   */
  public static function isAdmin($collective, $user, $stealth = FALSE){
    return
      @!$stealth && (
        $user->hasRole('administrator') ||
        $user->hasRole('art_wrangler') ||
        $user->hasRole('mutant_vehicle_wrangler') ||
        $user->hasRole('theme_camp_wrangler') ||
        $user->hasRole('communications_wrangler') ||
        $user->hasRole('support_wrangler')
      ) || CollectiveController::get('admin', $collective, $user);
  }

  /**
   * Checks whether a participant is banned from a collective
   * @param $collective Collective to check banning against
   * @param $user       User to check banning against
   */
  public static function isBanned($collective, $user){
    return CollectiveController::get('ban', $collective, $user);
  }


  /* --- Projects checking --- */


  /**
   * Checks collective has current projects
   */
  public static function hasCurrentProjects(){

    $collective = Utils::currentCollective();

    return $collective
      ? db_query("
          SELECT
            COUNT({node_field_data}.nid) as rows
          FROM
            {node_field_data} LEFT JOIN {flagging}
              ON {node_field_data}.nid = {flagging}.entity_id
              AND {flagging}.flag_id = 'archived',
            {node__field_collective}
          WHERE {node_field_data}.type IN ('art', 'theme_camps', 'mutant_vehicles', 'performances')
            AND {node__field_collective}.entity_id = {node_field_data}.nid
            AND {node__field_collective}.field_collective_target_id = ?
            AND {flagging}.entity_id IS NULL
          ", [$collective->id()]
        )->fetchCol('rows')[0]
      : 0;
  }

  /**
   * Checks collective has past projects
   */
  public static function hasPastProjects(){

    $collective = Utils::currentCollective();

    return $collective
      ? db_query("
          SELECT
            COUNT({node_field_data}.nid) as rows
          FROM
            {node_field_data}
            LEFT JOIN {flagging} ON {node_field_data}.nid = {flagging}.entity_id AND {flagging}.flag_id = 'archived',
            {node__field_collective}
          WHERE {node_field_data}.type IN ('art', 'theme_camps', 'mutant_vehicles', 'performances')
            AND {node__field_collective}.entity_id = {node_field_data}.nid
            AND {node__field_collective}.field_collective_target_id = ?
            AND {flagging}.entity_id IS NOT NULL
          ", [$collective->id()]
        )->fetchCol('rows')[0]
      : 0;
  }


  /* --- Settings checking --- */


  /**
   * Checks whether a collective has a setting
   * @param $collective Collective to check setting against
   * @param $setting    Setting to check
   */
  public static function setting($collective, $setting){
    return $collective && @array_fill_keys(
      array_column(
        $collective->field_settings->getValue(),
        'value'
      ),
      1
    )[$setting];
  }


  /* --- Flag Utility --- */


  /**
   * Sets a flagging
   * @param $fid  Flag ID
   * @param $user User to flag for
   * @param $user Collective to flag
   */
  public static function set($flag_id, $collective, $user){
    $flag_service = \Drupal::service('flag');
    $flag = $flag_service->getFlagById($flag_id);
    $flagging = $flag_service->getFlagging($flag, $collective, $user);
    if (!$flagging){
      $flag_service->flag($flag, $collective, $user);
      Cache::invalidateTags($collective->getCacheTags());
    } else {
      $flagging->save();
    }
  }

  /**
   * Clears a flagging
   * @param $fid  Flag ID
   * @param $user User to flag for
   * @param $user Collective to flag
   */
  public static function clear($flag_id, $collective, $user){
    $flag_service = \Drupal::service('flag');
    $flag = $flag_service->getFlagById($flag_id);
    if ($flag_service->getFlagging($flag, $collective, $user)){
      $flag_service->unflag($flag, $collective, $user);
      Cache::invalidateTags($collective->getCacheTags());
    }
  }

  /**
   * Returns a flagging
   * @param $fid  Flag ID
   * @param $user User to return flagging for
   * @param $user Collective to return flagging for
   */
  public static function get($flag_id, $collective, $user = FALSE){
    $flag_service = \Drupal::service('flag');
    $flag = $flag_service->getFlagById($flag_id);
    return $collective
      ? (
        $user->isAuthenticated()
          ? $flag_service->getFlagging($flag, $collective, $user)
          : $flag_service->getFlagging($flag, $collective)
      ) : FALSE;
  }

  /**
   * Returns Flagging users
   * @param $fid  Flag ID
   * @param $user Collective to return flagging for
   */
  public static function getFlaggingUsers($flag_id, $collective){
    $flag_service = \Drupal::service('flag');
    $flag = $flag_service->getFlagById($flag_id);
    return $flag_service->getFlaggingUsers($collective, $flag);
  }


  /* --- Path utility --- */


  /**
   * Returns and loads path parameter entities
   * @return array an array containing the collective from the url.
   */
  public static function pathParams(){

    $cid = \Drupal::routeMatch()->getParameter('cid');
    $uid = \Drupal::routeMatch()->getParameter('uid');

    return [
      \Drupal::entityTypeManager()->getStorage('node')->load($cid),
      \Drupal\user\Entity\User::load($uid ? $uid : Utils::currentUser()->id())
    ];
  }
}