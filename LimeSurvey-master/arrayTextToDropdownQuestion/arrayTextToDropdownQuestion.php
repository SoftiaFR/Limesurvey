<?php

class arrayTextToDropdownQuestion extends PluginBase {

    protected $storage = 'DbStorage';
    static protected $name = 'arrayTextToDropdownQuestion';
    static protected $description = 'Use array text question type to show multiple dropdown answers';

    public function __construct(PluginManager $manager, $id) {
        parent::__construct($manager, $id);

        // This event is fired when arriving on the Survey Settings
        $this->subscribe('beforeSurveySettings');

        // This event is fired when saving the Survey Settings
        $this->subscribe('newSurveySettings');

        // This event is fired each time a question renders
        $this->subscribe('beforeQuestionRender');
        
        // This event is fired at the plugin's load
        $this->subscribe('afterPluginLoad');
    }
    
    public function afterPluginLoad()
    {        
        if(strpos($_SERVER["PATH_INFO"], "/subquestions/") == true)
        {
            $url = explode("/qid/", $_SERVER["PATH_INFO"]);
            //var_dump($url[1]);var_dump("<br><br>");
            
            $question_type = Yii::app()->db->createCommand("SELECT type FROM {{questions}} WHERE qid=" . $url[1])->queryAll();
            
            if($question_type[0]["type"] == ";")
            {
                echo "
                <script>
                    window.onload =function()
                    {
                        // id may change with limesurvey langage settings
                        // hide second dimension
                        document.getElementById('answers_fr_0').style.display  = 'none';
                        

                        document.getElementById('tabpage_fr').getElementsByClassName(\"ui-widget-header\")[0].style.display  = 'none';
                        document.getElementById('tabpage_fr').getElementsByClassName(\"ui-widget-header\")[1].style.display  = 'none';
                        document.getElementById('tabpage_fr').getElementsByClassName(\"action-buttons\")[0].style.display  = 'none';
                        document.getElementById('tabpage_fr').getElementsByClassName(\"action-buttons\")[1].style.display  = 'none';
         

                        // 3 dropdowns  
                        document.getElementsByClassName('subquestion-actions')[1].style.visibility = 'collapse';
                        document.getElementsByClassName('col-md-1')[7].style.visibility = 'hidden';
                        
                        var rows = document.getElementById('answers_fr_1').getElementsByTagName(\"tbody\")[0].getElementsByTagName(\"tr\").length;
                        if (rows <2)
                        {

                            document.getElementById('answers_fr_1').getElementsByTagName(\"tbody\")[0].getElementsByTagName(\"tr\")[0].
                                getElementsByTagName(\"td\")[4].getElementsByTagName(\"span\")[2].click();
                            document.getElementById('answers_fr_1').getElementsByTagName(\"tbody\")[0].getElementsByTagName(\"tr\")[0].
                                getElementsByTagName(\"td\")[4].getElementsByTagName(\"span\")[2].click();    
                                
                            setTimeout(function(){
                            
                                console.log(document.getElementsByClassName('subquestion-actions')[1]);
                                console.log(document.getElementsByClassName('subquestion-actions')[2]);
                                console.log(document.getElementsByClassName('subquestion-actions')[3]);
                                
                                document.getElementsByClassName('subquestion-actions')[1].style.visibility = 'collapse';
                                document.getElementsByClassName('subquestion-actions')[2].style.visibility = 'collapse';
                                document.getElementsByClassName('subquestion-actions')[3].style.visibility = 'collapse';
                                
                                document.getElementById('answers_fr_1').getElementsByTagName(\"tbody\")[0].getElementsByTagName(\"tr\")[2].
                                getElementsByTagName(\"td\")[1].getElementsByTagName(\"input\")[0].value = \"SQ003\";

                            }, 1000); //840);
                        }
                        else if (rows == 3)
                        {
                            document.getElementsByClassName('subquestion-actions')[1].style.visibility = 'collapse';
                            document.getElementsByClassName('subquestion-actions')[2].style.visibility = 'collapse';
                            document.getElementsByClassName('subquestion-actions')[3].style.visibility = 'collapse';
                        }
                    }    
                </script>";
            }
            
            
        }
            
    }

    public function beforeSurveySettings() {

        $event = $this->event;
        $aSettings = array();
        $oSurvey = Survey::model()->findByPk($event->get('survey'));

        $aoQuestionArrayText = Question::model()->with('groups')->findAll(array(
            'condition' => "t.sid=:sid and type=:type and parent_qid=0",
            'params' => array(':sid' => $oSurvey->sid, ':type' => ';')
        ));

        $aDropDownType = $this->getDropdownType();

        foreach ($aoQuestionArrayText as $oQuestionArrayText) {
            
            $aoSubQuestionX = Question::model()->findAll(array(
                'condition' => "parent_qid=:parent_qid",
                'params' => array(":parent_qid" => $oQuestionArrayText->qid)
            ));

            $aSettings["info-{$oQuestionArrayText->qid}"] = array(
                'type' => 'info',
                'content' => "<p><span class='label label-primary'>{$oQuestionArrayText->title}</span>" . viewHelper::flatEllipsizeText($oQuestionArrayText->question, true, 80) . "</p>",
                'class' => 'questiontitle'
            );

           
            foreach ($aoSubQuestionX as $oSubQuestionX) {
                if($oSubQuestionX->scale_id ==1)
                {
                    $aSettings["question-{$oSubQuestionX->qid}"] = array(
                        'type' => 'select',
                        'label' => "<span class='label label-info'>{$oSubQuestionX->title}</span>" . viewHelper::flatEllipsizeText($oSubQuestionX->question, true, 80),
                        'options' => $aDropDownType,
                        'selectOptions' => array(
                            'placeholder' => gT('None'),
                        ),
                        'htmlOptions' => array(
                            'empty' => gT('None'),
                        ),
                        'current' => $this->getActualValue($oSubQuestionX->qid),
                    );
                }
            }
        }

        // Set a key/value pair to be used by plugins handling this event.
        if (!empty($aSettings)) {
            $event->set("surveysettings.{$this->id}", array(
                'name' => get_class($this),
                'settings' => $aSettings,
            ));
        }
    }

    public function newSurveySettings() {
        $event = $this->event;

        $aSettings = $event->get('settings');
        
        

        if (!empty($aSettings)) {
            foreach ($aSettings as $name => $value) {

                $aSetting = explode("-", $name);

                if ($aSetting[0] == "question" && isset($aSetting[1])) {
                    $iQid = intval($aSetting[1]);

                    if ($value == $this->getDefaultValue($aSetting[1])) {
                        QuestionAttribute::model()->deleteAll("qid=:qid and attribute=:attribute", array(':qid' => $iQid, ":attribute" => 'arrayTextToDropdown'));
                    } 
                    else {

                        $oAttribute = QuestionAttribute::model()->find("qid=:qid and attribute=:attribute", array(':qid' => $iQid, ":attribute" => 'arrayTextToDropdown'));

                        if (!$oAttribute) {
                            
                            $oAttribute = new QuestionAttribute;
                            $oAttribute->qid = $iQid;
                            $oAttribute->attribute = 'arrayTextToDropdown';
                        }

                        $oAttribute->value = $value;
                        $oAttribute->save();
                    }
                } else {
                    $default = $event->get($name, null, null, isset($this->settings[$name]['default']) ? $this->settings[$name]['default'] : null);
                    $this->set($name, $value, 'Survey', $event->get('survey'), $default);
                }
            }
        }
    }

    public function beforeQuestionRender() {
        $oEvent = $this->getEvent();
        $sType = $oEvent->get('type');
        if ($sType == ";") {
            $aoSubQuestionX = Question::model()->findAll(array(
                'condition' => "parent_qid=:parent_qid and scale_id=:scale_id",
                'params' => array(":parent_qid" => $oEvent->get('qid'), ":scale_id" => 1),
                'index' => 'qid',
            ));
            $oCriteria = new CDbCriteria;
            $oCriteria->condition = "attribute='arrayTextToDropdown'";
            $oCriteria->addInCondition("qid", CHtml::listData($aoSubQuestionX, 'qid', 'qid'));
            $oExistingAttribute = QuestionAttribute::model()->findAll($oCriteria);

            if (count($oExistingAttribute)) {
                $aSubQuestionsY = Question::model()->findAll(array(
                    'condition' => "parent_qid=:parent_qid and scale_id=:scale_id",
                    'params' => array(":parent_qid" => $oEvent->get('qid'), ":scale_id" => 0),
                    'select' => 'title',
                ));
                Yii::setPathOfAlias('archon810', dirname(__FILE__) . "/vendor/archon810/smartdomdocument/src");
                Yii::import('archon810.SmartDOMDocument');
                $dom = new \archon810\SmartDOMDocument();
                $dom->loadHTML("<!DOCTYPE html>" . $oEvent->get('answers'));
                
                $zz = true;
                foreach ($oExistingAttribute as $oAttribute) {
                    $oQuestionX = $aoSubQuestionX[$oAttribute->qid];
                    foreach ($aSubQuestionsY as $aSubQuestionY) {
                        $sAnswerId = "answer{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}{$aSubQuestionY->title}_{$oQuestionX->title}";
                        $inputDom = $dom->getElementById($sAnswerId);

                        if($zz == true){

                        $th_ToHide = $dom->getElementsByTagName("th")[3]->setAttribute('style', "display : none");
                        $td_ToHide = $dom->getElementsByTagName("td")[0]->setAttribute('style', "display : none");
                        
//                            $element_td_ToHide = $inputDom->parentNode->parentNode->parentNode->previousSibling->firstChild->firstChild;
//                            $element_td_ToHide->setAttribute('style', "display : none");  
//                            $element_th_ToHide = $inputDom->parentNode->previousSibling->previousSibling
//                                    ->previousSibling->previousSibling->previousSibling->previousSibling;
//                            $element_th_ToHide->setAttribute('style', "display : none");
//                            
                            $element_div = $inputDom->parentNode->parentNode->parentNode->parentNode->parentNode;
                            $element_div->setAttribute('class', "col-xs-10 col-md-offset-1");
                            
                            $zz=false;
                        }

                        if (!is_null($inputDom)) {
                            if (substr($oAttribute->value, 0, 5) === "label" && ctype_digit(substr($oAttribute->value, 5))) {
                                if ($sLabelHtml = $this->getLabelHtml(substr($oAttribute->value, 5), $inputDom)) {
                                    $newDoc = $dom->createDocumentFragment();
                                    $newDoc->appendXML($sLabelHtml);
                                    $inputDom->parentNode->replaceChild($newDoc, $inputDom);
                                    
                                }
                            }
                        }
                    }
                }
                $newHtml = $dom->saveHTMLExact();
                
                $oEvent->set('answers', $newHtml);
                $inputDom2 = $dom->getElementById($sAnswerId);
            }
        }
    }

    public function getDropdownType() {
        $aDropDownType = array();
        $aDropDownType[gT('None')] = strip_tags(gT('None'));

        if (Permission::model()->hasGlobalPermission('labelsets', 'read')) {

            $oLabels = LabelSet::model()->findAll(array("order" => "label_name"));
            if (count($oLabels)) {
                $aDropDownType[gT("Labels Sets")] = array();

                foreach ($oLabels as $oLabel) {
                    $aDropDownType[gT("Labels Sets")]['label' . $oLabel->lid] = strip_tags($oLabel->label_name);
                }
            }
        }

        return $aDropDownType;
    }

    public function getActualValue($iQid) {
        $oAttribute = QuestionAttribute::model()->find("qid=:qid and attribute=:attribute", array(':qid' => $iQid, ":attribute" => 'arrayTextToDropdown'));
        if ($oAttribute) {
            return $oAttribute->value;
        } else {
            return $this->getDefaultValue($iQid);
        }
    }

    public function getDefaultValue($iQid) {
        return '';
    }

    public function getLabelHtml($iLid, $inputDom) {

        if (LabelSet::model()->find("lid=:lid", array(":lid" => $iLid))) {
            $oLabelsSets = Label::model()->findAll(array("condition" => "lid=:lid", "params" => array(":lid" => $iLid)));
            if (!$oLabelsSets) {
                $oLabelsSets = Label::model()->findAll(array("condition" => "lid=:lid", "params" => array(":lid" => $iLid)));
            }
            if (!$oLabelsSets) {
                $oLabelsSets = Label::model()->findAll(array("condition" => "lid=:lid", "params" => array(":lid" => $iLid)));
            }
            if (!$oLabelsSets) {
                $oLabelsSets = Label::model()->findAll(array("condition" => "lid=:lid", "params" => array(":lid" => $iLid)));
            }
            if ($oLabelsSets && count($oLabelsSets)) {
                $data = CHtml::listData($oLabelsSets, 'code', 'title');
                $htmlOptions = array();
                if ($inputDom->getAttribute("value") == "") {
                    $htmlOptions['empty'] = gT('Please choose...');
                } elseif ($this->event->get('man_class') != "mandatory" && SHOW_NO_ANSWER) {
                    $data[''] = gT('No answer');
                }
                $htmlOptions['id'] = 'answer' . $inputDom->getAttribute("name");
                $htmlOptions['class'] = 'form-control';
                $newHtml = CHtml::dropDownList(
                                $inputDom->getAttribute("name"), $inputDom->getAttribute("value"), $data, $htmlOptions
                );
                return CHtml::tag("div", array('class' => 'select-item'), $newHtml);
            }
        }
    }

}
