<?php
/**
 * Created by PhpStorm.
 * User: softia
 * Date: 12/04/2018
 * Time: 09:12
 */
if ($_POST['requete_quest']) {

    $userConfigs = require('../../application/config/config.php');

    $tabsplitA = explode('mysql:', $userConfigs['components']['db']['connectionString']);
    $tabsplitB = explode(';', $tabsplitA[1]);
    $tablePrefix = $userConfigs['components']['db']['tablePrefix'];

    // mysql
    $tabhost = explode('host=', $tabsplitB[0]);
    $host = $tabhost[1];
    $tabport = explode('port=', $tabsplitB[1]);
    $port = $tabport[1];
    $tabdbname = explode('dbname=', $tabsplitB[2]);
    $dbname = $tabdbname[1];
    $user = $userConfigs['components']['db']['username'];
    $password = $userConfigs['components']['db']['password'];

    /*
    // pgsql
    $tabhost = explode('host=',$tabsplitB[0]);
    $host = $tabhost[1];
    $tabport = explode('port=',$tabsplitB[1]);
    $port = $tabport[1];
    $tabuser = explode('user=',$tabsplitB[2]);
    $user = $tabuser[1];
    $tabpassword = explode('password=',$tabsplitB[3]);
    $password = $tabpassword[1];
    $tabdbname = explode('dbname=',$tabsplitB[4]);
    $dbname = $tabdbname[1];
    */

    $nb_question = count($_POST['requete_quest']['LesQuestions']);
    $y = 0;

    foreach ($_POST['requete_quest']['LesQuestions'] as $question) {
        for ($i = 0; $i < 3; $i++) {
            //$db = mysqli_connect($host, $user, $password, $dbname);
            $db = mysqli_connect('localhost', 'root', '', 'limesurvey');
            $query = "SELECT title FROM ".$tablePrefix."labels WHERE lid=" . $question['SousQuestions'][$i]['lid'] . " AND code='" . $question['SousQuestions'][$i]['code'] . "'";
            if ($result = mysqli_query($db, $query)) {
                while ($row = $result->fetch_assoc()) {
                    $question['SousQuestions'][$i]['title'] = $row['title'];

                }
                mysqli_free_result($result);
            }
            mysqli_close($db);
        }

        $_traitement[$y]['LesQuestions'] = $question['SousQuestions'];
        $_traitement[$y]['qid'] = $_POST['requete_quest']['LesQuestions'][$y]['qid'];
        $y++;

    }
    echo json_encode($_traitement);

}


class Summary
{
    private $surveyId;
    private $group_id;
    private $question_id;
    private $group_order;
    private $codeQuestion;
    private $summary;
    private $tableExist;
    private $title = "";
    private $qid = "";
    private $question_scale = array();
    private $relevanceTitle;

    public function __construct()
    {
        $this->surveyId = 0;
        $this->group_id = 0;
        $this->question_id = 0;
        $this->group_order = 0;
        $this->codeQuestion = 0;
        $this->tableExist = false;
        $this->summary = "";
        $this->new_group = 0;
        $this->relevanceTitle = "";
    }


    public function getSurveyId()
    {
        return ($this->surveyId);
    }

    public function getGroupId()
    {
        return ($this->group_id);
    }

    public function getQuestionId()
    {
        return ($this->question_id);
    }

    public function getGroupOrder()
    {
        return ($this->group_order);
    }

    public function getCodeQuestion()
    {
        return ($this->codeQuestion);
    }

    public function getSummary()
    {
        return ($this->summary);
    }

    public function getTableExist()
    {
        return ($this->tableExist);
    }


    public function createSummary($isActivate, $plug_setting_notFinished)
    {
        $group_names = Yii::app()->db->createCommand('SELECT gid, group_name FROM {{groups}} WHERE sid=' . $this->surveyId . ' ORDER BY gid ASC ')
            ->queryAll();
        $type_questions = Yii::app()->db->createCommand("SELECT Q.gid, group_name, type, title, question, qid, parent_qid, scale_id, question_order, relevance FROM {{groups}} G, {{questions}} Q where G.sid='" . $this->surveyId . "' AND Q.sid=G.sid AND Q.gid=G.gid ORDER BY Q.gid, parent_qid, question_order,qid  ASC ")
            ->queryAll();
        $question_attr = Yii::app()->db->createCommand("SELECT qid, attribute FROM {{question_attributes}}")
            ->queryAll();

        $concac_qid = "";
        $nb_ques_in = 0;
        foreach ($type_questions as $type_quest) {
            if ($type_quest['type'] == ";") {
                $concac_qid .= $type_quest['qid'] . "-";
                $nb_ques_in++;
            }
        }

        foreach ($group_names as $group) {
            if ($group['group_name'] != "Récapitulatif") {
                $this->summary .= '<a id="group' . $group['gid'] . '" style="cursor:pointer;" onclick="myFunction(' . $group['gid'] . ')" "><span id="' . $group['gid'] . '_change" class="glyphicon glyphicon-plus-sign" ></span> ' . $group['group_name'] . '</a>';
                //$this->summary .= "<br>";
                $this->summary .= '<div style="display: none" class="cat" id="' . $group['gid'] . '" >' . $this->questionSummary($group['group_name'], $type_questions, $isActivate, $plug_setting_notFinished, $group['gid'], $question_attr) . '</div>';
                $this->summary .= "<br>";
            }
        }

        $route="/plugins/Recapitulatif/Summary.php";
        $url_absolute = YiiBase::app()->createAbsoluteUrl($route);
        $url_splited = explode("/index.php/", $url_absolute);
        $url_ajax = $url_splited[0].$route;       

        $this->summary .= $this->footerSummary($concac_qid, $nb_ques_in, $url_ajax);
        if (($plug_setting_notFinished == '"1"' && $isActivate == '""') || ($plug_setting_notFinished == '"1"' && $isActivate == '"1"')) {
            $this->summary .= $this->showOnlyNotFinished();
        }

        //$this->summary = "{A001.shown} <br> {A002.shown} <br>";
    }

    private function showOnlyNotFinished()
    {
        $footer = "
            <script>
                $(document).ready(function(){
                    
                    $(\"div.question\").each(function(){
                    if ($(\"a\", this).attr(\"style\") != \"color: red;\")
                    {
                        $(this).hide();
                    } 
                    });
                });
            </script>
            ";
        return $footer;
    }

    private function questionSummary($group_name, $info_question, $isActivate, $plug_setting_notFinished, $gid, $question_attr)
    {
        $hidden = false;
        $question = "";
        //var_dump($question_attr);
        //die();
        foreach ($info_question as $info) {
            foreach ($question_attr as $attr) {
                if ($attr['qid'] == $info['qid'] && $attr['attribute'] == "hidden") {
                    $hidden = true;
                }
            }
            if ($hidden == false) {
                if (($info['gid'] == $gid) && $info['parent_qid'] == '0') {
                    if ($info['type'] == "X") {
                        $question .= '<div class="noquestion type_XQ" style="display: none"><u><b style="color:#F0EFE7;">' . $info['question'] . '</b></u>&emsp;';
                    } else if ($info['relevance'] != "1" && $info['relevance'] != "") {
                        $relevance = trim($info['relevance'], '(');
                        $relevance = rtrim($relevance, ')');
                        //var_dump($relevance);
                        $relevance = html_entity_decode($relevance);
                        //var_dump($relevance);die();
                        $question .= '<div class="question"><u><b><a id="question' . $info['qid'] . '" class=" linkToButton " onclick="goLocalStorage(this)" data-button-to-click="#button-' . $info['gid'] . '" href="/limesurvey/index.php/survey/#" >{if(' . $relevance . ', "' . $info['question'] . '", "Question non accessible")}</a></b></u>&emsp;';
                    } else if ($info['type'] == "M" || $info['type'] == "P" || $info['type'] == "K") {
                        $question .= '<div class="question"><u><b><a id="question' . $info['qid'] . '" class=" linkToButton " onclick="goLocalStorage(this)" data-button-to-click="#button-' . $info['gid'] . '" href="/limesurvey/index.php/survey/#" >' . $info['question'] . '</a></b></u>&emsp;<br>'; // $this->checkType($info) .'<br>';
                    } else if ($info['type'] == ";") {
                        $question .= '<div class="question" id=' . $info['qid'] . '><u><b><a id="question' . $info['qid'] . '" class=" linkToButton " onclick="goLocalStorage(this)" data-button-to-click="#button-' . $info['gid'] . '" href="/limesurvey/index.php/survey/#" >' . $info['question'] . '</a></b></u>&emsp;';
                    } else {
                        $question .= '<div class="question"><u><b><a  id="question' . $info['qid'] . '" class=" linkToButton " onclick="goLocalStorage(this)" data-button-to-click="#button-' . $info['gid'] . '" href="/limesurvey/index.php/survey/#" >' . $info['question'] . '</a></b></u>&emsp;'; // $this->checkType($info) .'<br>';
                    }


                    if ($isActivate == '"1"' && $plug_setting_notFinished == '""') {
                        //$question .= '{C0001_SQ007.shown} <br>';
                        $question .= $this->checkType($info_question, $info) . '<br></div>';
                    } /*
                else if (($plug_setting_notFinished == '"1"' && $isActivate == '""') || ($plug_setting_notFinished == '"1"' && $isActivate == '"1"')) {
                    $question .= $this->checkTypeOnlyNotFinished($info_question, $info) . '<br></div>';
                }
                */
                    else {
                        $question .= $this->checkTypeHidden($info_question, $info) . '<br></div>';
                    }
                }
            }
            $hidden = false;
        }
        return $question;
    }

    private function checkType($info_question, $info_type)
    {
        $result = "";
        $question_settings = Yii::app()->db->createCommand('SELECT qaid, qid, value FROM {{question_attributes}} WHERE qid=' . $info_type['qid'] . ' ORDER BY qaid ASC ')
            ->queryAll();
        $this->question_scale[0] = $question_settings[1]['value'];
        $this->question_scale[1] = $question_settings[2]['value'];

        //var_dump($this->question_scale);

        if ($info_type['parent_qid'] == '0') {
            switch ($info_type['type']) {
                case "*":
                    break;
                case "R":
                    $subquestion_count = Yii::app()->db->createCommand("SELECT value FROM {{question_attributes}} WHERE qid=". $info_type['qid'])
                                    ->queryAll();
                    for($zz=1;$zz<=$subquestion_count[0]["value"]; $zz++){
                        $result .= '<i>{' . $info_type['title'] . '_'.$zz.'.shown} / </i>';
                    }
                    break;
                case "|":
                    $result = '<i>{' . $info_type['title'] . '.shown}</i>';
                    break;
                case ":":
                    $subqy = Yii::app()->db->createCommand("SELECT * FROM {{questions}} WHERE scale_id=0 AND parent_qid=".$info_type['qid']." ORDER BY qid ASC")
                                    ->queryAll();
                    $subqx = Yii::app()->db->createCommand("SELECT * FROM {{questions}} WHERE scale_id=1 AND parent_qid=".$info_type['qid']." ORDER BY qid ASC")
                                    ->queryAll();
               
                    $result .= '<table style="width:100;  border-color: white;width:100%;color: white;border: 1px solid;padding: 0.5rem;"><tbody>
                                    <tr style="border: 1px solid white;padding:0.5rem">
                                        <th></th>';
                    foreach($subqx as $sbx){
                        $result .= '    <th>'.$sbx['question'].'</th>';
                    }
                    $result .= "    </tr>";
                    foreach($subqy as $sby){
                        $result .="
                                    <tr>
                                        <td>".$sby['question']."</td>";
                        foreach($subqx as $sbx){
                            $result .=" <td>{".$info_type['title']."_".$sby['title']."_".$sbx['title'].".shown}";
                        }
                        $result .=" </tr>";    
                    }                   
                    $result .='</tbody></table>';
                    break;
                case ";":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    foreach ($info_question as $info) {
                        if ($info['scale_id'] == 1) {
                            if ($this->qid == $info['parent_qid']) {
                                $question_label = Yii::app()->db->createCommand("SELECT value FROM {{question_attributes}} WHERE qid=" . $info['qid'] . " ORDER BY qaid ASC ")
                                    ->queryAll();
                                $question_lid = explode("label", $question_label[0]['value']);
                                $q_lid = $question_lid[1];

                                $parent_title = Yii::app()->db->createCommand("SELECT title FROM {{questions}} WHERE parent_qid=".$info['parent_qid']." AND scale_id = 0" )
                                    ->queryRow();
                                $result .= '<div class="subquestion" style="margin-left: 2rem "><span>'
                                    . $info['question'] . '</span>&emsp;<i id=' . $q_lid . '>{' . $this->title . '_'. $parent_title['title'] .'_' . $info['title'] . '.shown}</i></div>';
                            }
                        }
                    }

                    break;
                case "X":
                    //$result = "";
                    break;
                case "M":
                case "P":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    foreach ($info_question as $info) {
                        if ($this->qid == $info['parent_qid']) {
                            //$result .= '<div class="subquestion" id="' . $info['qid'] . '" style="margin-left: 2rem">'.'<i>{'.$this->title.'_'.$info['title'].'.shown}</i></div>';
                            $result .= '<p class="onlyone"><i>{' . $this->title . '_' . $info['title'] . '.shown}</i></p>';
                        }
                    }
                    break;
                case "1":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    if ($this->question_scale[1] == "Importance") {
                        foreach ($info_question as $info) {
                            if ($this->qid == $info['parent_qid']) {
                                $result .= '<div class="subquestion" style="margin-left: 2rem"><span>' . $this->question_scale[0] . ':</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '_0.shown}</i></div>';
                                $result .= '<div class="subquestion" style="margin-left: 2rem"><span>' . $this->question_scale[1] . ':</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '_1.shown}</i></div>';
                            }
                        }
                    } else {
                        foreach ($info_question as $info) {
                            if ($this->qid == $info['parent_qid']) {
                                $result .= '<div class="subquestion" style="margin-left: 2rem "><span>' . $info['question'] . '</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '_0.shown}</i><i>{' . $this->title . '_' . $info['title'] . '_1.shown}</i></div>';
                            }
                        }
                    }
                    break;
                case "5":
                    $result = '<i>{if('.$info_type['title'].' == 6, "", '.$info_type['title'].'.shown)}</i>';
                    break;
                case "H":
                case "E":
                case "C":
                case "A":
                case "B":
                case "Q":
                case "K":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    foreach ($info_question as $info) {
                        if ($this->qid == $info['parent_qid']) {
                            $result .= '<div class="subquestion" style="margin-left: 2rem"><span>' . $info['question'] . '</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '.shown}</i></div>';
                        }
                    }
                    break;
                case "O":
                    $result = '<i>{' . $info_type['title'] . '.shown} . Commentaire : {' . $info_type['title'] . '_comment.shown}</i>';
                    break;
                case "F":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    foreach ($info_question as $info) {
                        if ($this->qid == $info['parent_qid']) {
                            $result .= '<div class="subquestion subquestion_typef" style="margin-left: 2rem"><span>' . $info['question'] . '</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '.shown}</i></div>';
                        }
                    }
                    break;
                default:
                    $result = '<i>{' . $info_type['title'] . '.shown}</i>';
            }
        }
        //$result .= '</div>';
        return $result;
    }

    private function checkTypeHidden($info_question, $info_type)
    {
        $result = "";
        $question_settings = Yii::app()->db->createCommand('SELECT qaid, qid, value FROM {{question_attributes}} WHERE qid=' . $info_type['qid'] . ' ORDER BY qaid ASC ')
            ->queryAll();
        $this->question_scale[0] = $question_settings[1]['value'];
        $this->question_scale[1] = $question_settings[2]['value'];

        //var_dump($this->question_scale);

        if ($info_type['parent_qid'] == '0') {
            switch ($info_type['type']) {
                case "*":
                    break;
                case "R":
                    $subquestion_count = Yii::app()->db->createCommand("SELECT value FROM {{question_attributes}} WHERE qid=". $info_type['qid'])
                                    ->queryAll();
                    for($zz=1;$zz<=$subquestion_count[0]["value"]; $zz++){
                        $result .= '<i style="display: none">{' . $info_type['title'] . '_'.$zz.'.shown} / </i>';
                    }
                    break;
                case "|":
                    $result = '<i style="display: none">{' . $info_type['title'] . '.shown}</i>';
                    break;
                case ":":
                    $subqy = Yii::app()->db->createCommand("SELECT * FROM {{questions}} WHERE scale_id=0 AND parent_qid=".$info_type['qid']." ORDER BY qid ASC")
                                    ->queryAll();
                    $subqx = Yii::app()->db->createCommand("SELECT * FROM {{questions}} WHERE scale_id=1 AND parent_qid=".$info_type['qid']." ORDER BY qid ASC")
                                    ->queryAll();
               
                    $result .= '<table style="display: none; width:100;  border-color: white;width:100%;color: white;border: 1px solid;padding: 0.5rem;"><tbody>
                                    <tr style="border: 1px solid white;padding:0.5rem">
                                        <th></th>';
                    foreach($subqx as $sbx){
                        $result .= '    <th>'.$sbx['question'].'</th>';
                    }
                    $result .= "    </tr>";
                    foreach($subqy as $sby){
                        $result .="
                                    <tr>
                                        <td>".$sby['question']."</td>";
                        foreach($subqx as $sbx){
                            $result .=" <td>{".$info_type['title']."_".$sby['title']."_".$sbx['title'].".shown}";
                        }
                        $result .=" </tr>";    
                    }                   
                    $result .='</tbody></table>';
                    break;
                case ";":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    foreach ($info_question as $info) {
                        if ($info['scale_id'] == 1) {
                            if ($this->qid == $info['parent_qid']) {
                                $question_label = Yii::app()->db->createCommand("SELECT value FROM {{question_attributes}} WHERE qid=" . $info['qid'] . " ORDER BY qaid ASC ")
                                    ->queryAll();


                                $question_lid = explode("label", $question_label[0]['value']);
                                $q_lid = $question_lid[1];

                                $parent_title = Yii::app()->db->createCommand("SELECT title FROM {{questions}} WHERE parent_qid=".$info['parent_qid']." AND scale_id = 0" )
                                    ->queryRow();
                                $result .= '<div class="subquestion"  style="margin-left: 2rem "><span>'
                                    . $info['question'] . '</span>&emsp;<i style="display: none" id=' . $q_lid . '>{' . $this->title . '_'.$parent_title['title'].'_' . $info['title'] . '.shown}</i></div>';
                            }
                        }
                    }
                    break;
                case "X":
                    //$result = "";
                    break;
                case "M":
                case "P":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    foreach ($info_question as $info) {
                        if ($this->qid == $info['parent_qid']) {
                            //$result .= '<div class="subquestion" id="' . $info['qid'] . '" style="margin-left: 2rem">'.'<i>{'.$this->title.'_'.$info['title'].'.shown}</i></div>';
                            $result .= '<p style="display: none" class="onlyone"><i>{' . $this->title . '_' . $info['title'] . '.shown}</i></p>';
                        }
                    }
                    break;
                case "1":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    if ($this->question_scale[1] == "Importance") {
                        foreach ($info_question as $info) {
                            if ($this->qid == $info['parent_qid']) {
                                $result .= '<div style="display: none" class="subquestion" style="margin-left: 2rem"><span>' . $this->question_scale[0] . ':</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '_0.shown}</i></div>';
                                $result .= '<div style="display: none" class="subquestion" style="margin-left: 2rem"><span>' . $this->question_scale[1] . ':</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '_1.shown}</i></div>';
                            }
                        }
                    } else {
                        foreach ($info_question as $info) {
                            if ($this->qid == $info['parent_qid']) {
                                $result .= '<div style="display: none" class="subquestion" style="margin-left: 2rem "><span>' . $info['question'] . '</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '_0.shown}</i><i>{' . $this->title . '_' . $info['title'] . '_1.shown}</i></div>';
                            }
                        }
                    }
                    break;
                case "5":
                    $result = '<i style="display: none">{if('.$info_type['title'].' == 6, "", '.$info_type['title'].'.shown)}</i>';
                    break;
                case "H":
                case "E":
                case "C":
                case "A":
                case "B":
                case "Q":
                case "K":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    foreach ($info_question as $info) {
                        if ($this->qid == $info['parent_qid']) {
                            $result .= '<div style="display: none" class="subquestion" style="margin-left: 2rem"><span>' . $info['question'] . '</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '.shown}</i></div>';
                        }
                    }
                    break;
                case "O":
                    $result = '<i style="display: none">{' . $info_type['title'] . '.shown} . Commentaire : {' . $info_type['title'] . '_comment.shown}</i>';
                    break;
                case "F":
                    $this->qid = $info_type['qid'];
                    $this->title = $info_type['title'];
                    foreach ($info_question as $info) {
                        if ($this->qid == $info['parent_qid']) {
                            $result .= '<div style="display: none" class="subquestion subquestion_typef" style="margin-left: 2rem"><span>' . $info['question'] . '</span>&emsp;<i>{' . $this->title . '_' . $info['title'] . '.shown}</i></div>';
                        }
                    }
                    break;
                default:
                    $result = '<i style="display: none">{' . $info_type['title'] . '.shown}</i>';
            }
        }
        //$result .= '</div>';
        return $result;
    }

    private function footerSummary($concac_qid, $nb_ques_in, $url)
    {
        $footer = "
                    <script> function myFunction(id_show) 
                    { 
                        if (document.getElementById(id_show).style.display === \"none\") 
                        {
                            document.getElementById(id_show).style.display = \"block\";
                            $('#'+id_show+\"_change\").removeClass( \"glyphicon-plus-sign\" );
                            $('#'+id_show+\"_change\").addClass( \"glyphicon-minus-sign\" );
                        }
                        else
                        {
                             document.getElementById(id_show).style.display = \"none\";
                             $('#'+id_show+\"_change\").removeClass( \"glyphicon-minus-sign\" );
                             $('#'+id_show+\"_change\").addClass( \"glyphicon-plus-sign\" );
                        }
                    }
                    
                    function goLocalStorage(obj)
                    {
                        var saveTitleQuestion = obj.id;
                        localStorage.setItem('saveTitleQuestionCle', saveTitleQuestion);
            localStorage.removeItem(\"PrecedentCle\");
                    }

                   $(document).ready(function(){
                        if (localStorage.getItem(\"PrecedentCle\") !== null)
                        {
                            document.getElementById(\"moveprevbtn\").style.display  = 'none';
                            var newContent = '<a type=\"submit\" id=\"precedent\" value=\"precedent\" name=\"precedent\" onclick=\"goLocalStoragePrecedent()\" class=\"submit button btn btn-lg  btn-default col-xs-3 text-left\" style=\"display: block;\">Précédent</a>';
                            document.getElementById('moveprevbtn').insertAdjacentHTML('afterend',newContent);
                        }
                    });
                    
                    function goLocalStoragePrecedent(){
                        if (document.getElementsByTagName(\"h3\")[0].innerHTML == \"Récapitulatif\"){
                            var pagePrecedenteId = localStorage.getItem(\"PrecedentCle\");
                            var allQuestions = document.getElementById(pagePrecedenteId).getElementsByTagName(\"div\");

                            var test_repondue = false;
                            var test_noquestion = true;
                            for (var i=0, max=allQuestions.length ; i < max; i++){
                                if(allQuestions[i].className == \"question\"){
                                    test_noquestion = false;
                                    if(allQuestions[i].firstChild.firstChild.firstChild.style.color == \"red\"){
                                        test_repondue =true;
                                        break;
                                    }
                                }
                            }
                            
                            if(test_noquestion == true){
                                var str1 = '#button-';
                                var data_to_click = str1.concat(pagePrecedenteId);
                                items = document.getElementsByClassName(\"dropdown-menu\")[0].getElementsByTagName('li');

                                for (var i = 0; i < items.length; ++i) {
                                    if(items[i].getElementsByTagName('a')[0].getAttribute('data-button-to-click') == data_to_click){
                                        items[i].getElementsByTagName('a')[0].click();
                                        break;
                                    }
                                }
                            }
                           
                            else{
                                if(test_repondue == true && test_noquestion == false){
                                    for (var i=0, max=allQuestions.length ; i < max; i++){
                                        if(allQuestions[i].className == \"question\"){
                                            if(allQuestions[i].firstChild.firstChild.firstChild.style.color == \"red\"){
                                                allQuestions[i].firstChild.firstChild.firstChild.click();
                                                break;
                                            }
                                        }
                                    }
                                }
                                else if(test_repondue == false && test_noquestion == false){
                                   for (var i=0, max=allQuestions.length ; i < max; i++){
                                        if(allQuestions[i].className == \"question\"){
                                                allQuestions[i].firstChild.firstChild.firstChild.click();
                                                break;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    
                    $(document).ready(function(){

                        $(\"div.question\").each(function(){
                            if($(\"a\", this).text() == \"Question non accessible\")
                            {
                                $(this).hide();
                            }
                        });
                        
                    });
                    
                    $(document).ready(function(){
                        var nbQ = 0;
                        var answer = 0;
                        var res = 0;
                        
                        $('.cat').each(function() {
                            var id = $(this).attr('id');
                            
                            $(\"div.question\", this).each(function(){
                            
                                if ($(\"tbody\",this).length > 0){
                                    var indic_reponse = true;
                                    var td_count =  this.getElementsByTagName('tr')[0].getElementsByTagName('th').length;
                                    $(\"tr:gt(0)\", this).each(function(){
                                    
                                        if(indic_reponse == true){
                                            var htmlstring1 = this.getElementsByTagName('td')[0].innerHTML;
                                            htmlstring1 = htmlstring1.trim();
                   
                                            if(htmlstring1 != ',' && htmlstring1 != ''){
                                            
                                                for (i = 1; i < td_count; i++){
                                                    var htmlstring2 = this.getElementsByTagName('td')[i].innerHTML;
                                                    htmlstring2 = htmlstring2.trim();
                                                    
                                                    if(htmlstring2 != ''){
                                                        indic_reponse = true;
                                                    }
                                                    else{
                                                        indic_reponse = false;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    });
                                    if (indic_reponse == true){
                                        answer++;
                                        $(\"tbody\", this).css(\"color\", \"#FFFFFF\")
                                        $(\"a\", this).css(\"color\", \"#FFFFFF\")
                                        nbQ++;
                                    } else {
                                        $(\"tbody\", this).css(\"color\", \"red\")
                                        $(\"a\", this).css(\"color\", \"red\")
                                        nbQ++;
                                    }                        
                                }
                                else if($(this).css(\"display\") == \"block\" && $(\"div\",this).last().hasClass('subquestion_typef') && $(\"div > span\",this).last().text().trim() == ',') {
                                    
                                    var div_subq_count =  this.getElementsByTagName('div').length;
                                    var indic_reponse = true;
                                    for (i = 0; i < div_subq_count; i++){
                                    
                                        if(indic_reponse == true){
                                            var htmlstring1 = this.getElementsByTagName('div')[i].getElementsByTagName('span')[0].innerHTML;
                                            htmlstring1 = htmlstring1.trim();
                   
                                            if(htmlstring1 != ',' && htmlstring1 != ''){
                                                var htmlstring2 = this.getElementsByTagName('div')[i].getElementsByTagName('i')[0].innerHTML;
                                                htmlstring2 = htmlstring2.trim();
                                                    
                                                if(htmlstring2 != ''){
                                                    indic_reponse = true;
                                                }
                                                else{
                                                    indic_reponse = false;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if (indic_reponse == true){
                                        answer++;
                                        $(\"a\", this).css(\"color\", \"#FFFFFF\")
                                        nbQ++;
                                    } else {
                                        $(\"a\", this).css(\"color\", \"red\")
                                        nbQ++;
                                    }                 
                                }
                                else {   
                                    if($(\"p.onlyone\", this).length > 0){
                                        $(\"p.onlyone\", this).each(function(){
                                            if($(\"i\", this).is(\":empty\") == false){
                                                res++;
                                            }
                                        });
                                        if (res > 0){
                                            answer++;
                                            $(\"a\", this).css(\"color\", \"#FFFFFF\")
                                            nbQ++;  
                                        }
                                        else{
                                            $(\"a\", this).css(\"color\", \"red\")
                                            nbQ++;
                                        }
                                        res = 0;
                                    }
                                    else {
                            
                                        if($(\"i\", this).is(':empty')){
                                            if ($(\"a\", this).text() != 'Question non accessible'){
                                                $(\"a\", this).css(\"color\", \"red\");
                                                nbQ++;   
                                            }
                                        }
                                        else{
                                            if ($(\"a\", this).text() != 'Question non accessible'){
                                                $(\"a\", this).css(\"color\", \"#FFFFFF\");
                                                nbQ++;
                                                answer++;
                                            }
                                        }
                                    }
                                }                        
                            });
                            
                            $(\"div.subquestion\", \"div.question\").each(function(){
                                if($(\"i\", this).is(':empty')){
                                    $(\"span\", this).css(\"color\", \"red\");
                                }
                                else{
                                    $(\"span\", this).css(\"color\", \"#FFFFFF\");                     
                                }
                            });

                            $('div#'+id).before('<span>(' + answer + ' question(s) répondue(s) sur ' + nbQ + ')</span>');
                            
                            if (answer != nbQ){
                                $(\"a#group\"+id).css(\"color\", \"red\")
                            }
                            nbQ = 0;
                            answer = 0;
                            res = 0;
                        });
                        
                        $('.cat').each(function() {
                            var q_count = this.getElementsByClassName('question').length;
                            var nq_count =  this.getElementsByClassName('noquestion').length;
                            tq_count = q_count + nq_count;
                            var qX_count = 0;
                            $(\"div.question\", this).each(function(){
                                if($(this).hasClass(\"type_XQ\")){
                                    qX_count++;
                                }
                            });
                            $(\"div.noquestion\", this).each(function(){
                                if($(this).hasClass(\"type_XQ\")){
                                    qX_count++;
                                }
                            });
                            if(tq_count == qX_count){
                                $(this).prev().css(\"display\", \"none\")
                            
                            }
                        });

                    });
                    </script>
                    <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>
                                <script>
                                    window.onload =function()
                                    {
                                        var SousQuestion = function (lid, code, title) {
                                            this.lid = lid;
                                            this.code = code;
                                            this.title = title;
                                        }

                                        var Question = function (qid, SousQuestions) {
                                            this.qid;
                                            this.SousQuestions = [];
                                        }
                                        
                                        var requete_object = function (LesQuestions) {
                                            this.LesQuestions= [];
                                        }
                                        
                                        the_requete_object = new requete_object();
                                        
                                        concac_tab_qid = '" . $concac_qid . "';
                                        tab_qid = concac_tab_qid.split(\"-\");

                                        for(y=0; y<" . $nb_ques_in . ";y++ ){

                                            question_instance = new Question();
                                            for (i = 0; i < 3; i++) {
                                                question_instance.qid = tab_qid[y];
                                                question_instance.SousQuestions[i] = new SousQuestion();
                                                question_instance.SousQuestions[i].lid = document.getElementById(tab_qid[y]).getElementsByTagName(\"div\")[i].getElementsByTagName(\"i\")[0].id;
                                                question_instance.SousQuestions[i].code = document.getElementById(tab_qid[y]).getElementsByTagName(\"div\")[i].getElementsByTagName(\"i\")[0].innerHTML;
                                                question_instance.SousQuestions[i].title = '';
                                            }
                                            the_requete_object.LesQuestions[y] = question_instance;
                                        }
                                        

                                        console.log(the_requete_object); 
                                        $.ajax({
                                            url: '".$url."',
                                            type: 'POST',
                                            timeout: 20000,
                                            //datatype: 'application/json',
                                            contentType: 'application/x-www-form-urlencoded',
                                            data: \{requete_quest: the_requete_object\},
                                            success: function(result) {
                                                var json_res = JSON.parse(result);
                                                
                                                json_res.forEach(function(element) {
                                                    element.LesQuestions.forEach(function(box) {
                                                        
                                                        for(i=0;i<3;i++){
                                                            if(document.getElementById(element.qid).getElementsByTagName(\"div\")[i].getElementsByTagName(\"i\")[0].id == box.lid 
                                                                && document.getElementById(element.qid).getElementsByTagName(\"div\")[i].getElementsByTagName(\"i\")[0].innerHTML == box.code)
                                                                {
                                                                    document.getElementById(element.qid).getElementsByTagName(\"div\")[i].getElementsByTagName(\"i\")[0].innerHTML = box.title ;
                                                                }
                                                        }   
                                                    }); 
                                                });     
                                            }
                                        });
                                    }
                                </script> 
                   
                    ";
        return $footer;
    }

    public function explodeSurveyId($absolute_url)
    {
        $url = explode("/surveyid/", $absolute_url);
        $this->surveyId = $url[1];
    }

    public function request_group_id()
    {
        $this->group_id = Yii::app()->db->createCommand("SELECT gid FROM {{groups}} WHERE sid=:sid AND group_name=:group_name")
            ->bindValue(':sid', $this->surveyId)
            ->bindValue(':group_name', 'Récapitulatif')
            ->queryRow();
        //var_dump($this->group_id);die();
    }

    public function request_group_order($surveyId)
    {
        $this->group_order = Yii::app()->db->createCommand("SELECT MAX(group_order) AS maxgo FROM {{groups}} WHERE sid=:id")
            ->bindValue(":id", $surveyId)
            ->queryRow();
    }

    public function request_question_id($surveyId)
    {
        $this->question_id = Yii::app()->db->createCommand("SELECT MAX(qid) AS maxqid FROM {{questions}} WHERE sid=:id")
            ->bindValue(":id", $surveyId)
            //->select('MAX(qid)')
            //->from('{{questions}}')
            //->where('sid=:id', array(':id' => $surveyId))
            ->queryRow();
    }

    public function createCodeQuestion()
    {
        $this->codeQuestion = "AAA{$this->surveyId}";
    }

    public function changeIntoGroups($surveyId, $group_order, $event)
    {
        /*
        $insert_group = "INSERT INTO {{groups}} VALUES (NULL, '$surveyId', 'Récapitulatif', '$new_group_order', 'Récapitulatif', 'fr', '', '')";
        $res_group = Yii::app()->db->createCommand($insert_group)->execute();
        */
        switch ($event) {
            case "insert":
                $insert_group = Yii::app()->db->createCommand("INSERT INTO {{groups}} VALUES (default , :surveyId, :group_name, :group_order, :description, :lang, '', '')")
                    ->bindValue(':surveyId', $surveyId)
                    ->bindValue(':group_name', "Récapitulatif")
                    ->bindValue(':group_order', $group_order)
                    ->bindValue(':description', "Récapitulatif")
                    ->bindValue(':lang', "fr")
                    ->execute();
                //var_dump($insert_group);
                break;
            case "update":
                break;
            default:
                break;
        }
    }

    public function changeIntoQuestions($surveyId, $gid = NULL, $codeQuestion = NULL, $summary, $event)
    {
        /*
        $insert_question = "INSERT INTO {{questions}} VALUES (NULL, 0, '$surveyId', '$new_gid', 'T', '$codeQuestion', '$summary', '', '', 'N', 'N', 1, 'en', 0, 0, 1, '')";
        $res_question = Yii::app()->db->createCommand($insert_question);
        */

        switch ($event) {
            case "insert":
                $insert_question = Yii::app()->db->createCommand("INSERT INTO {{questions}} VALUES (default, 0, :surveyId, :gid, 'X', :codeQuestion, :summary, '', '', 'N', 'N', 1, 'fr', 0, 0, 1, '')")
                    ->bindValue(':surveyId', $surveyId)
                    ->bindValue(':gid', $gid)
                    ->bindValue(':codeQuestion', $codeQuestion)
                    ->bindValue(':summary', $summary)
                    ->execute();
                //var_dump($insert_question);
                break;
            case "update":
                $update_question = Yii::app()->db->createCommand("UPDATE {{questions}} SET question=:summary WHERE title=:title")
                    ->bindValue(':title', "AAA" . $surveyId)
                    ->bindValue(':summary', $summary)
                    ->execute();
                //var_dump($update_question);
                break;
            default:
                break;
        }
    }

    public function checkTableExist($surveyId)
    {
        $selected_table = Yii::app()->db->createCommand()
            ->select('group_name')
            ->from('{{groups}}')
            ->where('sid=:sid', array(':sid' => $surveyId))
            ->queryColumn();

        foreach ($selected_table as $table) {
            if ($table == "Récapitulatif")
                $this->tableExist = true;
        }
    }

    public function checkPluginSettings()
    {
        $plugin_settings = Yii::app()->db->createCommand("SELECT L.key, L.value FROM {{plugin_settings}} L WHERE L.key=:res")
            ->bindValue(':res', 'Display_result')
            ->queryRow();
        //var_dump($plugin_settings["value"]);
        return $plugin_settings;
    }

    public function checkPluginSettings2()
    {
        $plugin_settings = Yii::app()->db->createCommand("SELECT L.key, L.value FROM {{plugin_settings}} L WHERE L.key=:res")
            ->bindValue(':res', 'Not_finished')
            ->queryRow();
        //var_dump($plugin_settings["value"]);
        return $plugin_settings;
    }

    public function checkNewGroupOrder($surveyId)
    {
        $new_group = Yii::app()->db->createCommand("SELECT gid, group_order FROM {{groups}} WHERE sid=:sid ORDER BY gid")
            ->bindValue(':sid', $surveyId)
            ->queryAll();
        return ($new_group);
    }

    public function request_table_summary($surveyId)
    {
        $sum_table = Yii::app()->db->createCommand("SELECT group_order FROM {{groups}} WHERE sid=:sid AND group_name=:group_name")
            ->bindValue(':sid', $surveyId)
            ->bindValue(':group_name', "Récapitulatif")
            ->queryRow();
        return ($sum_table);
    }

    public function update_new_group_order($group_order, $gid)
    {
        Yii::app()->db->createCommand("UPDATE {{groups}} SET group_order=:group_order WHERE gid=:gid")
            ->bindValue(':group_order', $group_order)
            ->bindValue(':gid', $gid)
            ->execute();
    }

    public function update_sum($new_order, $surveyId)
    {
        Yii::app()->db->createCommand("UPDATE {{groups}} SET group_order=:group_order WHERE sid=:sid AND group_name=:group_name")
            ->bindValue(':group_order', $new_order)
            ->bindValue(':sid', $surveyId)
            ->bindValue(':group_name', "Récapitulatif")
            ->execute();
    }

}