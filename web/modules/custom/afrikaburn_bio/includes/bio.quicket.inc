<?php
/**
 * @file
 * Contains quicket bio utility functions
 */

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;

/**
 * Retrieve a quicket code/id to enable user to purchase tickets.
 * @param string $id_number
 */
function _generate($id_number){

  module_load_include('inc', 'afrikaburn_shared', 'includes/shared.quicket');

  $config = Drupal::config('afrikaburn_shared.settings');
  $ticket_types = array_values(
    array_filter(
      [
        $config->get('main_general_id'),
        $config->get('main_general_minor_id'),
        $config->get('main_general_kids_id'),
        $config->get('main_mayday_id'),
        $config->get('main_mayday_minor_id'),
        $config->get('main_mayday_kids_id'),
      ]
    )
  );

  return quicket_request(
    'POST',
    'codes',
    [
      'EventId' => $config->get('main_id'),
      'IsPercentage' => FALSE,
      'DiscountAmount' => 0.0,
      'NumUses' => 6,
      'IsAccessCode' => TRUE,
      'Email' => str_replace(' ', '', $id_number),
      'TicketTypes' => $ticket_types,
    ]
  );
}

/**
 * Update users ID number on quicket
 * @param string $id_number
 * @param string $quicket_code
 */
function _update($id_number, $quicket_code, $quicket_id){

  module_load_include('inc', 'afrikaburn_shared', 'includes/shared.quicket');

  $config = Drupal::config('afrikaburn_shared.settings');
  $event_id = $config->get('main_id');
  $existing = $quicket_code ? quicket_request(
    'GET',
    "codes/search?eventId=$event_id&text=$quicket_code"
  ) : FALSE;

  return $existing
    ? quicket_request(
      'PUT',
      "codes/$quicket_id",
      [
        'EventId' => $event_id,
        'IsPercentage' => FALSE,
        'DiscountAmount' => 0.0,
        'NumUses' => 6,
        'IsAccessCode' => TRUE,
        'Email' => str_replace(' ', '', $id_number),
        'TicketTypes' =>  $existing->TicketTypes,
      ]
    )
  : _generate($id_number);
}

/**
 * Remove user ticket code on quicket
 * @param string $quicket_id
 */
function _delete($quicket_id) {
  module_load_include('inc', 'afrikaburn_shared', 'includes/shared.quicket');
  quicket_request(
    'DELETE',
    "codes/$quicket_id"
  );
}