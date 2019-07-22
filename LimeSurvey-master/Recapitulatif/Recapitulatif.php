<?php

include 'Summary.php';


class Recapitulatif extends PluginBase
{
    protected $storage = 'DbStorage';
    static protected $description = "Ajout d'un récapitulatif en fin de questionnaire";
    static protected $name = 'Récapitulatif';

    protected $isActivate;
    protected $plugin_setting_not_finished;
    protected $new_group;
    protected $sum_table;

    protected $settings = array(
        'Display_result' => array(
            'type' => 'checkbox',
            'label' => 'Afficher les réponses',
            'default' => '1',
        ),
        'Not_finished' => array(
            'type' => 'checkbox',
            'label' => 'Afficher que les questions en suspens',
            'default' => '0',
        ),
    );

    public function __construct(PluginManager $manager, $id)
    {
        parent::__construct($manager, $id);

        //$this->subscribe('afterSurveyComplete', 'insertSummary');
        $this->subscribe('beforeSurveySave', 'insertSummary');
        //$this->subscribe('beforeQuestionRender');
        $this->subscribe('afterFindSurvey');
       
    }
    

    public function afterFindSurvey()
    {
        $url =  "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        $urlArray = explode("/index.php/", $url);

        $event = $this->event;
        $surveyId = $event->get('surveyid');
        
        if($urlArray[1]==$surveyId)
        {
            echo "<script>
                url = window.location.href;
                var arrayOfStrings = url.split('/index.php/');
                window.onload =function()
                {
                    if(parseInt(arrayOfStrings[1]))
                    {
                        if(document.getElementById('index-menu') && document.getElementsByClassName(\"dropdown-menu\")[0].lastChild.getElementsByTagName(\"a\")[0].innerHTML == 'Récapitulatif'){
                            document.getElementById('navigator-container').getElementsByTagName(\"div\")[2].classList.add(\"col-xs-4\");
                            document.getElementById('navigator-container').getElementsByTagName(\"div\")[2].classList.remove(\"col-xs-6\");
                            document.getElementById('navigator-container').getElementsByTagName(\"div\")[3].classList.add(\"col-xs-4\");
                            document.getElementById('navigator-container').getElementsByTagName(\"div\")[3].classList.remove(\"col-xs-6\");
                        
                            var newContent = '<div class=\"col-xs-4 save-all text-center\"><a type=\"submit\" id=\"moveRecapbtn\" onclick=\"goRecap()\" value=\"moveRecap\" name=\"moveRecap\" class=\"submit button btn btn-lg  btn-primary\">Récapitulatif</a></div>';

                            document.getElementById('navigator-container').getElementsByTagName(\"div\")[2].insertAdjacentHTML('afterend',newContent);
                        }
                    }

                    // si ARRIVEE via Recapitulative clic question
                    if(localStorage.getItem(\"saveTitleQuestionCle\") != null)
                    {
                        var saveTitleQuestion = localStorage.getItem(\"saveTitleQuestionCle\");
                        document.getElementById(saveTitleQuestion).scrollIntoView();
                        localStorage.removeItem(\"saveTitleQuestionCle\");
                    }

                }
                
                // DEPART clic sur bouton Recap
                function goRecap()
                {
                    localStorage.removeItem(\"PrecedentCle\");
                    var concadoc = document.getElementById(\"limesurvey\").getElementsByTagName(\"input\")[1].value;
                    var res = concadoc.split(\"X\");
                    localStorage.setItem('PrecedentCle', res[1]);
                                     
                    document.getElementsByClassName(\"dropdown-menu\")[0].lastChild.getElementsByTagName(\"a\")[0].click();

                }
                
             </script>";
         }

    }


    private function checkSetting($settingName)
    {
        $pluginsettings = $this->getPluginSettings(true);
        // Logging will done if setted to true
        return $pluginsettings[$settingName]['current'];
    }
    
    public function insertSummary()
    {
        $url =  "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        $urlArray = explode("/surveyid/", $url);
        
        $check_survey_settings = Yii::app()->db->createCommand("SELECT allowprev, questionindex FROM {{surveys}} WHERE sid = :sid")
                    ->bindValue(':sid', $urlArray[1])
                    ->queryAll();

        if($check_survey_settings[0]["allowprev"]== "Y" && $check_survey_settings[0]["questionindex"]== "2"){
        
            $sum = new Summary();
            $sum->explodeSurveyId($_SERVER["PATH_INFO"]);
            //$sum->createSummary();

            if ($sum->getSurveyId() != "" && $sum->getSurveyId() != null) {
                $this->isActivate = $sum->checkPluginSettings();
                $this->plugin_setting_not_finished = $sum->checkPluginSettings2();

                $sum->checkTableExist($sum->getSurveyId());
                if ($sum->getTableExist() == !true) {
                    //echo "<script>alert(" . $sum->getSurveyId() . ");</script>";

                    $sum->request_group_id();
                    $sum->request_group_order($sum->getSurveyId());
                    $sum->request_question_id($sum->getSurveyId());

                    $sum->createCodeQuestion();
                    $codeQuestion = $sum->getCodeQuestion();

                    //$group_order = $sum->getGroupOrder()['MAX(group_order)'];     MYSQL
                    //$group_order = $sum->getGroupOrder()["max"];  POSTGRES
                    $group_order = $sum->getGroupOrder()["maxgo"];
                    $group_order++;
                    $new_group = $group_order;

                    //echo "<script>alert(" . $group_order . ");</script>";
                    $sum->createSummary($this->isActivate['value'], $this->plugin_setting_not_finished['value']);
                    $summary = $sum->getSummary();

                    $sum->changeIntoGroups($sum->getSurveyId(), $group_order, 'insert');
                    $sum->request_group_id();
                    $gid = $sum->getGroupId()['gid'];
                    $sum->changeIntoQuestions($sum->getSurveyId(), $gid, $codeQuestion, $summary, 'insert');
                } else {
                    $sum->createSummary($this->isActivate['value'], $this->plugin_setting_not_finished['value']);
                    $summary = $sum->getSummary();
                    $sum->changeIntoQuestions($sum->getSurveyId(), null, null, $summary, 'update');

                    $this->sum_table = $sum->request_table_summary($sum->getSurveyId());
                    //echo "<script>alert(" . $this->new_group['group_order'] . ");</script>";
                    $this->new_group = $sum->checkNewGroupOrder($sum->getSurveyId());
                    $new_max_group = 0;
                    foreach ($this->new_group as $key => $group)
                    {
                        if ($this->sum_table['group_order'] < $key)
                        {
                            --$key;
                            $new_max_group++;
                            $sum->update_new_group_order($key, $group['gid']);
                        }
                    }
                    if ($new_max_group != 0)
                    {
                        $sum->update_sum($this->sum_table['group_order'] + $new_max_group, $sum->getSurveyId());
                    }
                }
                //echo "<script>alert('Je suis lancée');</script>";
            }
        }
    }
}