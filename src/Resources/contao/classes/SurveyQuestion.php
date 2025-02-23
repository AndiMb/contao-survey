<?php

/*
 * @copyright  Helmut Schottmüller 2005-2018 <http://github.com/hschottm>
 * @author     Helmut Schottmüller (hschottm)
 * @package    contao-survey
 * @license    LGPL-3.0+, CC-BY-NC-3.0
 * @see	      https://github.com/hschottm/survey_ce
 */

namespace Hschottm\SurveyBundle;

use Contao\Backend;
use Contao\Database;
use Contao\FrontendTemplate;
use Contao\StringUtil;

/**
 * Class SurveyQuestion.
 *
 * Provide methods to handle import and export of member data.
 *
 * @copyright  Helmut Schottmüller 2009-2010
 * @author     Helmut Schottmüller <contao@aurealis.de>
 */
abstract class SurveyQuestion extends Backend
{
    protected $arrData;
    protected $arrStatistics;

    /**
     * Import String library.
     *
     * @param mixed $question_id
     */
    public function __construct($question_id = 0)
    {
        parent::__construct();
        $this->loadLanguageFile('tl_survey_question');
        $this->loadLanguageFile('tl_survey_result');
        $this->objQuestion = null;
        $this->arrStatistics = [];
        $this->arrStatistics['answered'] = 0;
        $this->arrStatistics['skipped'] = 0;
        if ($question_id > 0) {
            $objQuestion = Database::getInstance()->prepare('SELECT tl_survey_question.*, tl_survey_page.title pagetitle, tl_survey_page.pid parentID FROM tl_survey_question, tl_survey_page WHERE tl_survey_question.pid = tl_survey_page.id AND tl_survey_question.id = ?')
                ->execute($question_id);
            if ($objQuestion->numRows) {
                $this->data = $objQuestion->fetchAssoc();
            }
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'data':
                if (\is_array($value)) {
                    $this->arrData = &$value;
                }
                break;
            default:
                $this->$name = $value;
                break;
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'statistics':
                if (\count($this->arrStatistics) <= 2) {
                    $this->calculateStatistics();
                }

                return $this->arrStatistics;
                break;
            case 'id':
            case 'title':
            case 'question':
            case 'questiontype':
                return $this->arrData[$name];
                break;
            case 'titlebgcolor':
                return '#C0C0C0';
            case 'titlecolor':
                return '#000000';
            case 'otherbackground':
                return '#FFFFCC';
            case 'othercolor':
                return '#000000';
            default:
                return $this->$name;
                break;
        }
    }

    public function getResultData(): array
    {
        $result = [];
        if (isset($this->statistics['answers']) && \is_array($this->statistics['answers'])) {
            $result['statistics'] = $this->statistics;
            $result['answers'] = $this->statistics['answers'];
        }
        return $result;
    }

    public function getAnswersAsHTML()
    {
        if (!empty($resultData = $this->getResultData())) {
            $template = new FrontendTemplate('survey_answers_default');
            $template->setData($resultData);
            return $template->parse();
        }
    }

    public static function createInstance(int $questionId, string $questionType = null): ?self
    {
        if (null === $questionType) {
            $questionModel = SurveyQuestionModel::findByPk($questionId);
            if (!$questionModel) {
                return null;
            }
            $questionType = $questionModel->type;
        }

        $class = 'Hschottm\\SurveyBundle\\SurveyQuestion'.ucfirst($questionType);
        if (!class_exists($class)) {
            return null;
        }

        return new $class($questionId);
    }

    public function clearStatistics()
    {
        $this->arrStatistics = [];
    }

    public function exportDataToExcel(&$exporter, $sheet, &$row)
    {
        // overwrite in parent classes
        return [];
    }

    abstract protected function calculateStatistics();

    protected function calculateAnsweredSkipped(&$objResult)
    {
        $this->arrStatistics = [];
        $this->arrStatistics['answered'] = 0;
        $this->arrStatistics['skipped'] = 0;
        while ($objResult->next()) {
            $id = (\strlen($objResult->pin)) ? $objResult->pin : $objResult->uid;
            $this->arrStatistics['participants'][$id][] = $objResult->row();
            $this->arrStatistics['answers'][] = $objResult->result;
            if (\strlen($objResult->result)) {
                ++$this->arrStatistics['answered'];
            } else {
                ++$this->arrStatistics['skipped'];
            }
        }
    }

    public function resultAsString($res)
  	{
  		return $res;
  	}
}
