<?php

class UrlPatternProcessor
{
    const GLOBAL_FOUND = 'global_found';
    const GLOBAL_NOT_FOUND = 'global_not_found';
    const NAME_FOUND = 'name_found';
    const NAME_NOT_FOUND = 'name_not_found';

    const LINE_DELIMITER = "\n";
    const GLOBAL_SEARCH_KEY = "_GLOBAL_SEARCH_KEY_";

    protected $patternListId;
    protected $userName;
    protected $data;
    protected $formattedData;
    protected $db;

    public function __construct($db, $ruleLabel, $userName = '')
    {
        $this->db = $db;
        $this->patternListId = 0;
        $this->userName = str_replace('@', '', $userName);

        $this->getPatternListId($ruleLabel);
        $this->loadDataFromDB();
        $this->transformData();
    }

    protected function getPatternListId($ruleLabel)
    {
        $query = "SELECT p.urlpattern_list_id
            FROM liv2_rules_to_urlpattern p
            INNER JOIN liv2_rules r ON r.rule_id = p.rule_id
            WHERE r.rule_label = '{$ruleLabel}'
            LIMIT 1";

        $row = $this->db->getARow($query);

        if (is_null($row)) {
            throw new \Exception('unable to find record in db: ' . $query);
        }

        if (empty($row['urlpattern_list_id'])) {
            throw new \Exception('empty data in urlpattern_list_id column');
        }

        $this->patternListId = $row['urlpattern_list_id'];
    }

    protected function loadDataFromDB()
    {
        $query = "SELECT urlpattern_list_text 
            FROM liv2_rules_urlpattern_list 
            WHERE urlpattern_list_id = {$this->patternListId} 
            LIMIT 1";

        $row = $this->db->getARow($query);

        if (is_null($row)) {
            throw new \Exception('unable to find record in db: ' . $query);
        }

        if (empty($row['urlpattern_list_text'])) {
            throw new \Exception('empty data in urlpattern_list_text column');
        }

        $this->data = $row['urlpattern_list_text'];
    }

    protected function saveDataToDB()
    {
        $value = $this->transformBackToString();

        if (empty($value)) {
            throw new \Exception('unable to transformBackToString, result is empty');
        }

        $query = "UPDATE liv2_rules_urlpattern_list 
            SET urlpattern_list_text = '{$value}'
            WHERE urlpattern_list_id = {$this->patternListId} ";

        $this->db->execQuery($query);
    }


    protected function transformBackToString()
    {
        $result = array();

        if (array_key_exists(self::GLOBAL_SEARCH_KEY, $this->formattedData)) {
            $result[] = implode(self::LINE_DELIMITER, $this->formattedData[self::GLOBAL_SEARCH_KEY]);
        }

        foreach ($this->formattedData as $userName => $urlLines) {
            if ($userName == self::GLOBAL_SEARCH_KEY) {
                continue;
            }

            $oneRecord = '@' . $userName . self::LINE_DELIMITER;
            $oneRecord .= implode(self::LINE_DELIMITER, $urlLines);

            $result[] = $oneRecord;
        }

        return implode(self::LINE_DELIMITER, $result);
    }

    protected function transformData()
    {
        $isFirstLetterName = ($this->data[0] == '@');

        $exploded = explode('@', $this->data);

        $result = array();

        foreach ($exploded as $i => $record) {
            $record = trim($record);

            if (empty($record)) {
                continue;
            }

            $explodedRecord = explode(self::LINE_DELIMITER, $record);

            if ($i == 0 and !$isFirstLetterName) {
                $result[self::GLOBAL_SEARCH_KEY] = $explodedRecord;
            } else {
                $result[array_shift($explodedRecord)] = array_map('trim', $explodedRecord);
            }
        }

        $this->formattedData = $result;
    }

    private function getSearchStatus()
    {
        if (empty($this->userName)) {
            return array_key_exists(self::GLOBAL_SEARCH_KEY, $this->formattedData)
                ? self::GLOBAL_FOUND
                : self::GLOBAL_NOT_FOUND;
        }

        return array_key_exists($this->userName, $this->formattedData)
            ? self::NAME_FOUND
            : self::NAME_NOT_FOUND;
    }


    public function insert($text)
    {
        switch($this->getSearchStatus()) {
            case self::GLOBAL_FOUND:
                $this->formattedData[self::GLOBAL_SEARCH_KEY][] = $text;
                break;
            case self::GLOBAL_NOT_FOUND:
                $this->formattedData[self::GLOBAL_SEARCH_KEY] = array($text);
                break;
            case self::NAME_FOUND:
                $this->formattedData[$this->userName][] = $text;
                break;
            case self::NAME_NOT_FOUND:
                $this->formattedData[$this->userName][] = $text;
                break;
            default:
                throw new \Exception('Unable to getSearchStatus');
                break;
        }

        $this->saveDataToDB();
    }

    public function update($oldText, $newText)
    {
        switch($this->getSearchStatus()) {
            case self::GLOBAL_FOUND:
                $index = array_search($oldText, $this->formattedData[self::GLOBAL_SEARCH_KEY]);

                if ($index !== false) {
                    $this->formattedData[self::GLOBAL_SEARCH_KEY][$index] = $newText;
                }
                break;
            case self::NAME_FOUND:
                $index = array_search($oldText, $this->formattedData[$this->userName]);

                if ($index !== false) {
                    $this->formattedData[$this->userName][$index] = $newText;
                }
                break;
            case self::GLOBAL_NOT_FOUND:
                break;
            case self::NAME_NOT_FOUND:
                break;
            default:
                throw new \Exception('Unable to getSearchStatus');
                break;
        }

        $this->saveDataToDB();
    }

    public function remove($text)
    {
        switch($this->getSearchStatus()) {
            case self::GLOBAL_FOUND:
                $index = array_search($text, $this->formattedData[self::GLOBAL_SEARCH_KEY]);

                if ($index !== false) {
                    unset($this->formattedData[self::GLOBAL_SEARCH_KEY][$index]);
                }

                if (count($this->formattedData[self::GLOBAL_SEARCH_KEY]) == 0) {
                    unset($this->formattedData[self::GLOBAL_SEARCH_KEY]);
                }
                break;
            case self::NAME_FOUND:
                $index = array_search($text, $this->formattedData[$this->userName]);

                if ($index !== false) {
                    unset($this->formattedData[$this->userName][$index]);
                }

                if (count($this->formattedData[$this->userName]) == 0) {
                    unset($this->formattedData[$this->userName]);
                }
                break;
            case self::GLOBAL_NOT_FOUND:
                break;
            case self::NAME_NOT_FOUND:
                break;
            default:
                throw new \Exception('Unable to getSearchStatus');
                break;
        }

        $this->saveDataToDB();
    }
}
