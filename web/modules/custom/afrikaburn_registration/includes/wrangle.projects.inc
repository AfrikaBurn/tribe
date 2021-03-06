<?php
/**
 * @file: collective-projects.inc
 * Contains wrangle project view preprocessors.
 */


function _editable($id){
  return (
    [

      'wrangle_art' => [
        'field_prj_adm_wrangler',
        'field_prj_brn_burning',
        'field_prj_cll_collaborate',
        'field_prjr_complete',
        'field_prj_adm_review',
        'field_prj_snd_level',
        'field_prj_snd_sound',
        'field_prj_stc_size',
        'field_allow_support_camp',
        'field_waps_allowed',
        'field_vps_allowed',
        'field_ddts_allowed',
        'field_collective',
      ],

      'wrangle_binnekring_events' => [
        'field_prj_adm_wrangler',
        'field_prj_cll_collaborate',
        'field_prjr_complete',
        'field_prj_adm_review',
        'field_prj_snd_level',
        'field_allow_support_camp',
        'field_waps_allowed',
        'field_vps_allowed',
        'field_ddts_allowed',
        'field_collective',
      ],

      'wrangle_mutant_vehicles' => [
        'field_prj_adm_wrangler',
        'field_prj_cll_collaborate',
        'field_prjr_complete',
        'field_prj_adm_review',
        'field_prj_adm_registration',
        'field_prj_snd_sound',
        'field_prj_snd_level',
        'field_mv_amount_worthy',
        'field_grt_awarded',
        'field_allow_support_camp',
        'field_waps_allowed',
        'field_vps_allowed',
        'field_ddts_allowed',
        'field_collective',
      ],

      'wrangle_theme_camps' => [
        'field_prj_adm_wrangler',
        'field_prj_cll_collaborate',
        'field_prjr_complete',
        'field_prj_adm_review',
        'field_prj_snd_sound',
        'field_prj_snd_level',
        'field_prj_plc_binnekring',
        'field_final_placement',
        'field_plot',
        'field_neighbours',
        'field_ice_card',
        'field_placed',
        'field_placement_card',
        'field_placement_letter_sent',
        'field_waps_allowed',
        'field_vps_allowed',
        'field_ddts_allowed',
        'field_collective',
        'field_good_standing',
        'field_interactive',
        'field_service',
        'field_moop_assesment',
        'field_adds_to_landscape',
        'field_post_event_standing',
        'field_green_tech',
      ],

      'wrangle_wtf' => [
        'field_prj_wtf_short_copy',
        'field_prj_adm_latitude',
        'field_prj_adm_longitude',
      ],
    ]
  )[$id];
}

function _merge(){
  return [
    'administrative' => [
      'field_moop_assesment',
      'field_prj_adm_registration',
      'field_prj_snd_level',
      'field_prj_stc_size',
      'field_final_placement',
      'field_plot',
      'field_neighbours',
      'field_allow_support_camp',
      'field_waps_allowed',
      'field_vps_allowed',
      'field_ddts_allowed',
      'field_prj_plc_people',
    ],
    'process' => [
      'field_collective',
      'field_prj_adm_wrangler',
      'field_prj_adm_review',
      'field_prjr_complete',
      'field_mv_amount_worthy',
      'field_grt_awarded',
    ],
    'edit' => [
      'field_prj_wtf_short_copy',
      'field_prj_adm_latitude',
      'field_prj_adm_longitude',
    ],
  ];
}

function _title($field){
  return @([
    'field_prj_brn_burning' => FALSE,
    'field_prj_cll_collaborate' => FALSE,
    'field_prjr_complete' => FALSE,
    'field_prj_snd_sound' => FALSE,
    'field_prj_plc_binnekring' => FALSE,
    'field_ice_card' => FALSE,
    'field_placed' => FALSE,
    'field_placement_card' => FALSE,
    'field_placement_letter_sent' => FALSE,
    'field_prj_snd_level' => 'Sound level',
    'field_grt_awarded' => 'Awarded',
    'field_mv_amount_worthy' => 'Amount Worthy',
    'field_prj_adm_latitude' => 'LAT',
    'field_prj_adm_longitude' => 'LONG',
    'field_good_standing' => FALSE,
    'field_interactive' => FALSE,
    'field_service' => FALSE,
    'field_adds_to_landscape' => FALSE,
    'field_waps_allowed' => 'WAPs',
    'field_vps_allowed' => 'VPs',
    'field_ddts_allowed' => 'DDTs',
    'field_post_event_standing' => FALSE,
    'field_green_tech' => FALSE,
])[$field];
}


/**
 * Prepares wrangler filter.
 */
function ar_prepare_wrangler_filter(&$form, $type){

  $view = \Drupal\views\Views::getView('wranglers');

  if (!$form['wrangler']) return;

  if (is_object($view)) {

    // ARGH! consistent naming so desperately needed!
    $type = [
      'art' => 'art_wrangler',
      'mutant_vehicles' => 'mutant_vehicle_wrangler',
      'performances' => 'art_wrangler',
      'theme_camps' => 'theme_camp_wrangler'
    ][$type];

    $view->setArguments([$type]);
    $view->setDisplay('wrangler_select');
    $view->preExecute();
    $view->execute();

    $form['wrangler']['#type'] = 'select';
    $form['wrangler']['#options'][''] = t('- any wrangler -');
    $form['wrangler']['#options']['unassigned'] = t('- UNASSIGNED -');
    foreach($view->result as $row){
      $form['wrangler']['#options'][$row->uid] =
        $row->_entity->get('field_first_name')->value .
        ' "' . $row->_entity->get('name')->value . '" ' .
        $row->_entity->get('field_last_name')->value;
    }

    $form['size']['#options']['All'] = t('- any size -');
    $form['status']['#options']['All'] = t('- any state -');
    $form['location']['#options']['All'] = t('- anywhere -');
    $form['completed']['#options']['All'] = t('- any form -');

  }
}

/**
 * Prepares wrangle view fields
 */
function ab_prepare_wrangler_view(&$variables){

  $view = $variables['view'];

  if ($view->current_display == 'download') return;

  $name = $view->id();
  $type = trim(explode(',', $view->storage->get('tag'))[1]);
  $rows = $variables['rows'];
  $mails = [];
  $node = \Drupal\node\Entity\Node::create(['type' => $type]);
  $form = \Drupal::service('entity.form_builder')->getForm($node);

  foreach($rows[0]['#rows'] as $index=>&$row){

    $nid = $row->_entity->id();
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

    $mails[] = [
      'contact' => @$row->_relationship_entities['field_prj_gen_contact']
        ? $row->_relationship_entities['field_prj_gen_contact']->get('mail')->value
        : FALSE,
      'lead' => @$row->_relationship_entities['field_prj_gen_lead']
        ? $row->_relationship_entities['field_prj_gen_lead']->get('mail')->value
        : FALSE,
      'manager' => @$row->_relationship_entities['field_prj_gen_manager']
        ? $row->_relationship_entities['field_prj_gen_manager']->get('mail')->value
        : FALSE,
    ];

    foreach(_editable($view->id()) as $field){
      if (isset($view->field[$field])){
        $field_form = @array_shift(
          array_filter(
            [
              $form[$field]['value'],
              $form[$field]['widget']['value'],
              $form[$field]['widget'][0]['value'],
              $form[$field]['widget'][0]['target_id'],
              $form[$field]['widget'],
            ]
          )
        );

        $value = $node->get($field)->getValue();
        switch ($field_form['#type']){

          case 'checkboxes':
            $values = array_column($value, 'value');
            foreach($values as $cb_value) {
              $field_form[$cb_value]['#checked'] = 1;
            }
          break;

          case 'checkbox':
            $field_form['#checked'] = array_column($value, 'value')[0] == "1";
          break;

          case "entity_autocomplete":
          case "select":
            $field_form['#value'] = $field_form['#multiple']
              ? array_column($value, 'target_id')
              : @array_shift(array_column($value, 'target_id'));
          break;

          default:
            $field_form['#value'] = @array_shift(
              array_column($value, 'value')
            );
        }

        // Title
        $title = _title($field);
        if (isset($title)) {
          if ($title) $field_form['#title'] = $title;
          else unset($field_form['#title']);
        }

        unset($field_form['#description']);
        $field_form['#attributes']['autocomplete'] = "off";
        $field_form['#attributes']['class'][] = 'editor-parent';

        // Links to completed forms

        $supporting = @$row->_entity->field_supporting_project->entity;

        if ($field == 'field_prjr_complete' && $supporting) {
          $field_form = [
            '#markup' => '
              <ul>
                <li><a href="/node/' . $nid . '/support_camp" target="_blank">View</a></li>
                <li><a href="/node/' . $nid . '/edit/support_camp" target="_blank">Edit</a></li>
                <li><a href="/node/' . $nid . '/layout_print" target="_blank">Print</a></li>
              </ul>
            ',
          ];
        }

        if ($field == 'field_prjr_complete' && !$supporting){

          if ($type == 'theme_camps'){
            unset($field_form['#options']['support_camp']);
            unset($field_form['support_camp']);
          }

          foreach($field_form['#options'] as $option=>$markup){

            if ($option == 'support_camp'){
              if ($supporting){
                $sid = $supporting->nid->value;
                $field_form[$option]['#description'] =
                  '<a target="_blank" href="/node/'.$sid.'/' . $option . '">view</a> ' .
                  '<a target="_blank" href="/node/'.$sid.'/edit/' . $option . '">edit</a>';
              } else {
                $cid = $row->_relationship_entities['field_collective']->nid->value;
                $field_form[$option]['#description'] =
                  '<a target="_blank" href="/node/add/theme_camps/' . $option . '?field_collective=' . $cid . '&field_supporting_project=' .$nid . '">Create</a> ';
                $field_form[$option]['#attributes']['disabled'] = 'disabled';
              }
            } else {
              $field_form[$option]['#description'] =
                '<a target="_blank" href="/node/'.$nid.'/' . $option . '">view</a> ' .
                '<a target="_blank" href="/node/'.$nid.'/edit/' . $option . '">edit</a>';
            }

            if ($option == 'grant')
              $field_form[$option]['#description'] .= '<a target="_blank" href="/node/'.$nid.'/' . $option . '_print">print</a>';
            if ($option == 'form_2' && $type == 'theme_camps')
              $field_form[$option]['#description'] .= '<a target="_blank" href="/node/'.$nid.'/layout_print">print</a>';
          }
        }

        if ($field_form) $view->editor[$index][$field] = render($field_form);
      }
    }
  }

  $view->mails = [
    'contact' => array_column($mails, 'contact'),
    'lead' => array_column($mails, 'lead'),
    'manager' => array_column($mails, 'manager'),
  ];
}

/**
 * Inserts wrangle view fields
 */
function ab_insert_wrangler_view_rows(&$variables, $comments, $merge, $editable){

  $view = $variables['view'];

  foreach($variables['rows'] as $index=>&$row){

    $nid = $view->result[$index]->_entity->id();
    $row['nid'] = $nid;

    // Move form
    if ($editable){
      foreach(_editable($view->id()) as $field){
        if (isset($view->field[$field])){
          $row['columns'][$field]['content'][0]['field_output'] = $view->editor[$index][$field];
        }
      }
    }

    if ($comments){
      // Latest comment
      $row['columns']['administrative']['content'][] = [
        'field_output' => @render(views_embed_view('wrangle_comments', 'latest', $nid))
      ];
      // Comment form
      $form = [
        '#type' => 'details',
        '#title' => 'Add comment',
        'content' => \Drupal::service('entity.form_builder')->getForm(
          \Drupal::entityTypeManager()->getStorage('comment')->create(
            [
              'entity_type'  => 'node',
              'entity_id'    => $nid,
              'field_name'   => 'field_admin_comments',
              'comment_type' => 'admin_comments',
              'pid' => NULL,
            ]
          )
        )
      ];
      unset($form['content']['field_body']['widget'][0]['value']['#title']);
      $form['content']['#action'] .= '?destination=' . \Drupal\Component\Utility\UrlHelper::encodePath(drupal_get_destination()['destination']);
      $row['columns']['administrative']['content'][] = ['field_output' => render($form)];
      // Comment history
      $history = [
        '#type' => 'details',
        '#title' => 'History',
        'content' => views_embed_view('wrangle_comments', 'history', $nid),
      ];
      $row['columns']['administrative']['content'][] = [
        'field_output' => render($history)
      ];
    }

    if ($merge) {

      $merged = [];

      // Merge admin columns
      foreach (_merge() as $column=>$fields) {
        foreach($fields as $field){
          if (isset($view->field[$field])){
            $row['columns'][$column]['content'][] = [
              'field_output' => @$row['columns'][$field]['content'][0]['field_output']
            ];
            unset($row['columns'][$field]);
            $row['columns'][$column]['attributes'] =  new \Drupal\Core\Template\Attribute(['class' => 'column-' . $column]);

            $merged[$column] = $field;
          }
        }
      }

      // Unset admin column headers
      foreach (_merge() as $column=>$fields) {
        foreach($fields as $field){
          unset($variables['header'][$field]);
        }
      }

      // Set merged column headings
      foreach ($merged as $column=>$field) {
        $label = $column;
        $label[0] = strtoupper($label[0]);
        $variables['header'][$column] = [
          'content' => t($label),
          'title' => t($label),
          'attributes' => new \Drupal\Core\Template\Attribute(['class' => 'column-' . $column]),
        ];
      }
    }
  }

  // Prepare email addresses
  foreach($view->mails as $type=>$mails){
    $variables['mails'][$type] = implode(', ', array_combine($mails, $mails));
  }
}