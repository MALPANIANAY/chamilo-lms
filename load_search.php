<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;

$cidReset = true;

require_once 'main/inc/global.inc.php';

$htmlHeadXtra[] = '<link  href="'. api_get_path(WEB_PATH) .'web/assets/cropper/dist/cropper.min.css" rel="stylesheet">';
$htmlHeadXtra[] = '<script src="'. api_get_path(WEB_PATH) .'web/assets/cropper/dist/cropper.min.js"></script>';

api_block_anonymous_users();
$allowToSee = api_is_drh() || api_is_student_boss() || api_is_platform_admin();

if ($allowToSee === false) {
    api_not_allowed(true);
}
$userId = api_get_user_id();
$userInfo = api_get_user_info();

$userToLoad = isset($_GET['user_id']) ? $_GET['user_id'] : '';

$userToLoadInfo = [];
if ($userToLoad) {
    $userToLoadInfo = api_get_user_info($userToLoad);
}
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'subscribe_user':
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : '';
        SessionManager::suscribe_users_to_session($sessionId, [$userToLoad], SESSION_VISIBLE_READ_ONLY, false);
        Display::addFlash(Display::return_message(get_lang('UserAdded')));
        header("Location: ".api_get_self().'?user_id='.$userToLoad);
        exit;
        break;
    case 'unsubscribe_user':
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : '';
        SessionManager::unsubscribe_user_from_session($sessionId, $userToLoad);
        Display::addFlash(Display::return_message(get_lang('Unsubscribed')));
        header("Location: ".api_get_self().'?user_id='.$userToLoad);
        break;
}

$em = Database::getManager();

$formSearch = new FormValidator('load', 'get', api_get_self());
$formSearch->addHeader(get_lang('LoadDiagnosis'));
if (!empty($userInfo)) {
    $users = [];
    switch ($userInfo['status']) {
        case DRH:
            $users = UserManager::get_users_followed_by_drh($userId);
            break;
        case STUDENT_BOSS:
            $users = UserManager::getUsersFollowedByStudentBoss($userId);
            break;
    }

    if (!empty($users)) {
        $userList = [];
        foreach ($users as $user) {
            $userList[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
        }
        $formSearch->addSelect('user_id', get_lang('User'), $userList);
    }
}
if ($userToLoad) {
    $formSearch->setDefaults(['user_id' => $userToLoad]);
}

$formSearch->addButtonSearch(get_lang('Search'), 'save');

$form = new FormValidator('search', 'post', api_get_self().'?user_id='.$userToLoad);
$form->addHeader(get_lang('Diagnosis'));
$form->addHidden('user_id', $userToLoad);

/** @var ExtraFieldSavedSearch  $saved */
$search = [
    'user' => $userToLoad
];

$items = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findBy($search);
if (empty($items)) {
    Display::addFlash(Display::return_message('NoData'));
}

$defaults = [];
$tagsData = [];
if (!empty($items)) {
    /** @var ExtraFieldSavedSearch $item */
    foreach ($items as $item) {
        $variable = 'extra_'.$item->getField()->getVariable();
        if ($item->getField()->getFieldType() == ExtraField::FIELD_TYPE_TAG) {
            $tagsData[$variable] = $item->getValue();
        }
        $defaults[$variable] = $item->getValue();
    }
}

$extraField = new ExtraField('session');
$extraFieldValue = new ExtraFieldValue('session');

$theme = 'theme_fr';
if ($userToLoadInfo) {
    $lang = $userToLoadInfo['language'];
    switch ($lang) {
        case 'french2':
        case 'french':
            $theme = 'theme_fr';
            break;
        case 'german2':
        case 'german':
            $theme = 'theme_de';
            break;
    }
}

$extraFieldUser = new ExtraField('user');

$userForm = new FormValidator('user_form', 'post', api_get_self());
$panel = Display::panel(get_lang('FiliereExplanation'), '', '', '',  '', 'filiere_panel');
$userForm->addHeader(Display::url(get_lang('Filiere'), '#', ['id'=> 'filiere']).''.$panel);
$fieldsToShow = [
    'statusocial',
    'filiere_user',
    'filiereprecision',
    'filiere_want_stage',
];
$forceShowFields = true;
$filter = false;
$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    [],
    false,
    $forceShowFields, //$forceShowFields = false
    [],
    [],
    $fieldsToShow
);

$panel = Display::panel(get_lang('DisponibilitePendantMonStageExplanation'), '', '', '',  '', 'dispo_pendant_panel');
$userForm->addHeader(Display::url(get_lang('DisponibilitePendantMonStage'), '#', ['id'=> 'dispo_pendant']).''.$panel);

$fieldsToShow = [
    'datedebutstage',
    'datefinstage',
    'poursuiteapprentissagestage',
    'heures_disponibilite_par_semaine_stage'
];

$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    [],
    false,
    $forceShowFields, //$forceShowFields = false
    [],
    [],
    $fieldsToShow
);


$panel = Display::panel(get_lang('ObjectifsApprentissageExplanation'), '', '', '',  '', 'objectifs_panel');
$userForm->addHeader(Display::url(get_lang('ObjectifsApprentissage'), '#', ['id'=> 'objectifs']).''.$panel);

$fieldsToShow = [
    'objectif_apprentissage'
];

$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    false,
    $fieldsToShow,
    $fieldsToShow,
    [],
    [],
    false,
    $forceShowFields,//$forceShowFields = false
    [],
    [],
    $fieldsToShow
);


$panel = Display::panel(get_lang('MethodeTravailExplanation'), '', '', '',  '', 'methode_panel');
$userForm->addHeader(Display::url(get_lang('MethodeTravail'), '#', ['id'=> 'methode']).''.$panel);

$fieldsToShow = [
    'methode_de_travaille',
    'accompagnement'
];

$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    [],
    false,
    $forceShowFields, //$forceShowFields = false
    [],
    [],
    $fieldsToShow
);


// Session fields
$showOnlyThisFields = [
    'access_start_date',
    'access_end_date',
    //'heures_disponibilite_par_semaine', this is only for user
    'domaine',
    'filiere',
    $theme,
    'ecouter',
    'lire',
    'participer_a_une_conversation',
    's_exprimer_oralement_en_continu',
    'ecrire'
];

$extra = $extraField->addElements(
    $form,
    '',
    [],
    false, //filter
    true,
    $showOnlyThisFields,
    $showOnlyThisFields,
    $defaults,
    [],
    false, //$orderDependingDefaults
    true, // force
    [ 'domaine' => 3, $theme => 5], // $separateExtraMultipleSelect
    [
        'domaine' => [
            get_lang('Domaine').' 1',
            get_lang('Domaine').' 2',
            get_lang('Domaine').' 3'
        ],
        $theme  => [
            get_lang('Theme').' 1',
            get_lang('Theme').' 2',
            get_lang('Theme').' 3',
            get_lang('Theme').' 4',
            get_lang('Theme').' 5'
        ],
    ]
);

$form->addButtonSearch(get_lang('Search'), 'search');
$form->addButtonSave(get_lang('Save'), 'save');

$extraFieldsToFilter = $extraField->get_all(array('variable = ?' => 'temps-de-travail'));
$extraFieldToSearch = array();
if (!empty($extraFieldsToFilter)) {
    foreach ($extraFieldsToFilter as $filter) {
        $extraFieldToSearch[] = $filter['id'];
    }
}
$extraFieldListToString = implode(',', $extraFieldToSearch);

$result = SessionManager::getGridColumns('simple', $extraFieldsToFilter);
$columns = $result['columns'];
$column_model = $result['column_model'];

$form->setDefaults($defaults);


/** @var HTML_QuickForm_select $element */
$domaine1 = $form->getElementByName('extra_domaine[0]');
$domaine2 = $form->getElementByName('extra_domaine[1]');
$domaine3 = $form->getElementByName('extra_domaine[2]');
$userForm->setDefaults($defaults);
$domainList =  [];
if ($domaine1) {
    $domainList[] = $domaine1->getValue();
}
if ($domaine2) {
    $domainList[] = $domaine2->getValue();
}
if ($domaine3) {
    $domainList[] = $domaine3->getValue();
}
$themeList = [];
$extraField = new ExtraField('session');
$resultOptions = $extraField->searchOptionsFromTags('extra_domaine', 'extra_'.$theme, $domainList);

if ($resultOptions) {
    $resultOptions = array_column($resultOptions, 'tag', 'id');
    $resultOptions = array_filter($resultOptions);

    for ($i = 0; $i < 5; $i++) {
        /** @var HTML_QuickForm_select $theme */
        $themeElement = $form->getElementByName('extra_'.$theme.'['.$i.']');
        foreach ($resultOptions as $key => $value) {
            $themeElement->addOption($value, $value);
        }
    }
}

$filterToSend = '';

if ($formSearch->validate()) {
    $formSearchParams = $formSearch->getSubmitValues();
    $filters = [];
    foreach ($defaults as $key => $value) {
        if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
            continue;
        }
        if (!empty($value)) {
            $filters[$key] = $value;
        }
    }

    $filterToSend = [];
    if (!empty($filters)) {
        $filterToSend = ['groupOp' => 'AND'];
        if ($filters) {
            $count = 1;
            $countExtraField = 1;
            foreach ($result['column_model'] as $column) {
                if ($count > 5) {
                    if (isset($filters[$column['name']])) {
                        $defaultValues['jqg'.$countExtraField] = $filters[$column['name']];
                        $filterToSend['rules'][] = ['field' => $column['name'], 'op' => 'cn', 'data' => $filters[$column['name']]];
                    }
                    $countExtraField++;
                }
                $count++;
            }
        }
    }
}

$params = [];
if ($form->validate()) {
    $params = $form->getSubmitValues();
    $save = false;
    $search = false;
    if (isset($params['search'])) {
        unset($params['search']);
        $search = true;
    }

    if (isset($params['save'])) {
        $save = true;
        unset($params['save']);
    }

    $form->setDefaults($params);

    $filters = [];

    // Search
    if ($search) {
        // Parse params.
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
                continue;
            }
            if (!empty($value)) {
                $filters[$key] = $value;
            }
        }

        $filterToSend = [];
        if (!empty($filters)) {
            $filterToSend = ['groupOp' => 'AND'];
            if ($filters) {
                $count = 1;
                $countExtraField = 1;
                foreach ($result['column_model'] as $column) {
                    if ($count > 5) {
                        if (isset($filters[$column['name']])) {
                            $defaultValues['jqg'.$countExtraField] = $filters[$column['name']];
                            $filterToSend['rules'][] = [
                                'field' => $column['name'],
                                'op' => 'cn',
                                'data' => $filters[$column['name']]
                            ];
                        }
                        $countExtraField++;
                    }
                    $count++;
                }
            }
        }
    }

    if ($save) {

        /** @var \Chamilo\UserBundle\Entity\User $user */
        $user = $em->getRepository('ChamiloUserBundle:User')->find($userToLoad);
        $extraFieldValueSession = new ExtraFieldValue('session');

        $sessionFields = [
            'extra_access_start_date',
            'extra_access_end_date',
            'extra_filiere',
            'extra_domaine',
            'extra_domaine[0]',
            'extra_domaine[1]',
            'extra_domaine[3]',
            'extra_temps-de-travail',
            //'extra_competenceniveau',
            'extra_'.$theme,
            'extra_ecouter',
            'extra_lire',
            'extra_participer_a_une_conversation',
            'extra_s_exprimer_oralement_en_continu',
            'extra_ecrire'
        ];

        $userData = $params;

        foreach ($userData as $key => $value) {


            $found = strpos($key, '__persist__');
            if ($found === false) {
                continue;
            }


        }

        if (isset($userData['extra_filiere_want_stage']) &&
            isset($userData['extra_filiere_want_stage']['extra_filiere_want_stage'])
        ) {
            $wantStage = $userData['extra_filiere_want_stage']['extra_filiere_want_stage'];

            if ($wantStage === 'yes') {
                if (isset($userData['extra_filiere_user'])) {
                    $userData['extra_filiere'] = [];
                    $userData['extra_filiere']['extra_filiere'] = $userData['extra_filiere_user']['extra_filiere_user'];
                }
            }
        }

        // save in ExtraFieldSavedSearch.
        foreach ($userData as $key => $value) {
            if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
                continue;
            }

            if (!in_array($key, $sessionFields)) {
                continue;
            }

            $field_variable = substr($key, 6);
            $extraFieldInfo = $extraFieldValueSession
                ->getExtraField()
                ->get_handler_field_info_by_field_variable($field_variable);

            if (!$extraFieldInfo) {
                continue;
            }

            $extraFieldObj = $em->getRepository('ChamiloCoreBundle:ExtraField')->find($extraFieldInfo['id']);

            $search = [
                'field' => $extraFieldObj,
                'user' => $user
            ];

            /** @var ExtraFieldSavedSearch $saved */
            $saved = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findOneBy($search);

            if ($saved) {
                $saved
                    ->setField($extraFieldObj)
                    ->setUser($user)
                    ->setValue($value)
                ;
                $em->merge($saved);
            } else {
                $saved = new ExtraFieldSavedSearch();
                $saved
                    ->setField($extraFieldObj)
                    ->setUser($user)
                    ->setValue($value)
                ;
                $em->persist($saved);
            }
            $em->flush();
        }
        Display::addFlash(Display::return_message(get_lang('Saved'), 'success'));
        header('Location: '.api_get_self().'?user_id='.$userToLoad);
        exit;

    }
}

$view = $form->returnForm();

$jsTag = '';
if (!empty($tagsData)) {
    foreach ($tagsData as $extraField => $tags) {
        foreach ($tags as $tag) {
            $tag = api_htmlentities($tag);
           // $jsTag .= "$('#$extraField')[0].addItem('$tag', '$tag');";
        }
    }
}

$htmlHeadXtra[] ='<script>
$(function() {
    '.$extra['jquery_ready_content'].'
    '.$jsTag.'
});
</script>';

if (!empty($filterToSend)) {
    $userStartDate = isset($params['extra_access_start_date']) ? $params['extra_access_start_date'] : '';
    $userEndDate = isset($params['extra_access_end_date']) ? $params['extra_access_end_date'] : '';

    $date = new DateTime($userStartDate);
    $date->sub(new DateInterval('P3D'));
    $userStartDateMinus = $date->format('Y-m-d h:i:s');

    $date = new DateTime($userEndDate);
    $date->add(new DateInterval('P2D'));
    $userEndDatePlus = $date->format('Y-m-d h:i:s');

    $sql = " AND (
        (s.access_start_date > '$userStartDateMinus' AND s.access_start_date < '$userEndDatePlus') OR
        (s.access_start_date > '$userStartDateMinus' AND (s.access_start_date = '' OR s.access_start_date IS NULL)) OR 
        ((s.access_start_date = '' OR s.access_start_date IS NULL) AND (s.access_end_date = '' OR s.access_end_date IS NULL))
    )";

    if ($userStartDate && !empty($userStartDate)) {
        $filterToSend['custom_dates'] = $sql;
    }
    $filterToSend = json_encode($filterToSend);
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_search=true&load_extra_field='.$extraFieldListToString.'&_force_search=true&rows=20&page=1&sidx=&sord=asc&filters2='.$filterToSend;
} else {
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_search=true&load_extra_field='.$extraFieldListToString.'&_force_search=true&rows=20&page=1&sidx=&sord=asc';
}

// Autowidth
$extra_params['autowidth'] = 'true';

// height auto
$extra_params['height'] = 'auto';
$extra_params['postData'] = array(
    'filters' => array(
        "groupOp" => "AND",
        "rules" => $result['rules']
    )
);

$sessionByUserList = SessionManager::get_sessions_by_user($userToLoad, true, true);

$sessionUserList = array();
if (!empty($sessionByUserList)) {
    foreach ($sessionByUserList as $sessionByUser) {
        $sessionUserList[] = $sessionByUser['session_id'];
    }
}
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
    var sessionList = '.json_encode($sessionUserList).';
    if ($.inArray(options.rowId, sessionList) == -1) {
        return \'<a href="'.api_get_self().'?action=subscribe_user&user_id='.$userToLoad.'&session_id=\'+options.rowId+\'">'.Display::return_icon('add.png', addslashes(get_lang('Subscribe')),'',ICON_SIZE_SMALL).'</a>'.'\';
    } else {
        return \'<a href="'.api_get_self().'?action=unsubscribe_user&user_id='.$userToLoad.'&session_id=\'+options.rowId+\'">'.Display::return_icon('delete.png', addslashes(get_lang('Delete')),'',ICON_SIZE_SMALL).'</a>'.'\';
    }
}';

$htmlHeadXtra[] = api_get_jqgrid_js();

$griJs = Display::grid_js(
    'sessions',
    $url,
    $columns,
    $column_model,
    $extra_params,
    array(),
    $action_links,
    true
);
$grid = '<div id="session-table" class="table-responsive">';
$grid .= Display::grid_html('sessions');
$grid .= '</div>';

$tpl = new Template(get_lang('Diagnosis'));

if (empty($items)) {
    $view = '';
    $grid = '';
    $griJs = '';
}
$tpl->assign('form', $view);
$tpl->assign('form_search', $formSearch->returnForm().$userForm->returnForm());

$table = new HTML_Table(array('class' => 'data_table'));
$column = 0;
$row = 0;

$total = '0';
$sumHours = '0';
$numHours = '0';

$field = 'heures_disponibilite_par_semaine';
$extraField = new ExtraFieldValue('user');
$data = $extraField->get_values_by_handler_and_field_variable($userToLoad, $field);

$availableHoursPerWeek = 0;

function dateDiffInWeeks($date1, $date2)
{
    if ($date1 > $date2) {
        return dateDiffInWeeks($date2, $date1);
    }
    $first = new \DateTime($date1);
    $second = new \DateTime($date2);

    return floor($first->diff($second)->days / 7);
}

if ($data) {
    $availableHoursPerWeek = $data['value'];
    $numberWeeks = 0;
    if ($form->validate()) {
        $formData = $form->getSubmitValues();

        if (isset($formData['extra_access_start_date']) && isset($formData['extra_access_end_date'])) {
            $startDate = $formData['extra_access_start_date'];
            $endDate = $formData['extra_access_end_date'];
            $numberWeeks = dateDiffInWeeks($startDate, $endDate);
        }
    } else {
        if ($defaults) {
            if (isset($defaults['extra_access_start_date']) && isset($defaults['extra_access_end_date'])) {
                $startDate = $defaults['extra_access_start_date'];
                $endDate = $defaults['extra_access_end_date'];
                $numberWeeks = dateDiffInWeeks($startDate, $endDate);
            }
        }
    }

    $total = $numberWeeks * $availableHoursPerWeek;
    $sessions = SessionManager::getSessionsFollowedByUser($userToLoad);

    if ($sessions) {
        $sessionFieldValue = new ExtraFieldValue('session');

        foreach ($sessions as $session) {
            $sessionId = $session['id'];
            $data = $sessionFieldValue->get_values_by_handler_and_field_variable($sessionId, 'temps-de-travail');
            if ($data) {
                $sumHours += $data['value'];
            }
        }
    }
}

$numHours = $total - $sumHours;
$headers = array(
    "Total d'heures disponibles" => $total,
    'Sommes des heures de sessions inscrites' => $sumHours,
    "Nombre d'heures encore disponible" => $numHours
);
foreach ($headers as $header => $value) {
    $table->setCellContents($row, 0, $header);
    $table->updateCellAttributes($row, 0, 'width="250px"');
    $table->setCellContents($row, 1, $value);
    $row++;
}

$button = '';
if ($userToLoad) {
    $button = Display::url(
        get_lang('OfajEndOfLearnPath'),
        api_get_path(WEB_CODE_PATH).'messages/new_message.php?prefill=ofaj&send_to_user='.$userToLoad,
        ['class' => 'btn btn-default']
    );
    $button .= '<br /><br />';
}

$tpl->assign('grid', $grid.$button.$table->toHtml());
$tpl->assign('grid_js', $griJs);

$content = $tpl->fetch('default/user_portal/search_extra_field.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();